<?php
/**
 * @package    Joomla.Component.Builder.Tests
 * @created    26th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace VDM\Plugin\Tests;


use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\ComponentRecord;
use Joomla\CMS\Event\Model\BeforeSaveEvent;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Language;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\Dispatcher;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\Plugin\Extension;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Every maintained plugin body must pass the actual Joomla 6 compiler.
 *
 * @since 6.2.0
 */
#[CoversNothing]
final class CompilerIntegrationTest extends ArchitectureTestCase
{
	/**
	 * Use every supplied plugin source as a compiler input, without remote reads.
	 *
	 * @return iterable
	 * @since 6.2.0
	 */
	public static function plugins(): iterable
	{
		foreach (glob(JCB_PLUGINS_ROOT . '/plg_*/src/Extension/*.php') as $file)
		{
			yield basename($file, '.php') => [$file];
		}
	}

	/**
	 * Preserve the supplied body, resolve Power imports, and execute the emitted map.
	 *
	 * @param string $file Maintained plugin source.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	#[DataProvider('plugins')]
	public function testPluginDefinitionCompilesWithoutLegacyListenerAdapters(string $file): void
	{
		$this->compilePlugin($file);
	}

	/**
	 * Discover the actual content plugins and their maintained form XML.
	 *
	 * @return iterable
	 * @since 6.2.0
	 */
	public static function contentPlugins(): iterable
	{
		foreach (glob(JCB_PLUGINS_ROOT . '/plg_content_*/src/Extension/*.php') as $file)
		{
			foreach (glob(dirname($file, 3) . '/forms/*.xml') as $form)
			{
				if (basename($form) !== 'config.xml')
				{
					yield basename($file, '.php') => [$file, $form];
				}
			}
		}
	}

	/**
	 * Dispatch native events into the compiled class with its unchanged form assets.
	 *
	 * @param string $file Plugin source passed through the compiler.
	 * @param string $formFile Maintained form XML loaded by the generated handler.
	 * @return void
	 * @since 6.2.0
	 */
	#[DataProvider('contentPlugins')]
	public function testCompiledContentPluginsExecuteNativeHandlers(string $file, string $formFile): void
	{
		$class = $this->compilePlugin($file);
		$components = new ReflectionProperty(ComponentHelper::class, 'components');
		$paths = new ReflectionProperty(FormHelper::class, 'paths');
		$previousComponents = $components->getValue();
		$previousPaths = $paths->getValue();
		$components->setValue(null, [
			'com_componentbuilder' => new ComponentRecord([
				'option' => 'com_componentbuilder',
				'enabled' => 1,
				'params' => '{"editor":"codemirror"}',
			]),
		]);
		$paths->setValue(null, null);

		try
		{
			$dispatcher = new Dispatcher();
			$application = $this->createStub(CMSApplication::class);
			$application->method('getLanguage')->willReturn(new Language('en-GB'));
			$application->method('getDispatcher')->willReturn($dispatcher);
			$this->setJoomlaApplication($application);
			$plugin = new $class(['params' => new Registry(['plugin' => 23])]);
			$plugin->setApplication($application);
			$dispatcher->addSubscriber($plugin);
			$form = new Form('com_componentbuilder.' . basename($formFile, '.xml'));
			$this->assertTrue($form->load('<form><field name="existing" type="text" /></form>'));
			$event = new PrepareFormEvent('onContentPrepareForm', ['subject' => $form, 'data' => []]);
			$dispatcher->dispatch('onContentPrepareForm', $event);

			$expected = simplexml_load_file($formFile);
			$xml = $form->getXml();
			$this->assertCount(1, $xml->xpath('//field[@name="existing"]'));
			$this->assertCount(count($expected->xpath('//field')) + 1, $xml->xpath('//field'));
			foreach ($expected->xpath('//fieldset') as $fieldset)
			{
				$this->assertCount(1, $xml->xpath('//fieldset[@name="' . $fieldset['name'] . '"]'));
			}
			foreach ($xml->xpath('//field[@type="editor"]') as $editor)
			{
				$this->assertSame('codemirror|none', (string) $editor['editor']);
			}
			$this->assertSame($form, $event->getForm());
			$this->assertArrayNotHasKey('result', $event->getArguments());

			$unrelated = new Form('com_content.article');
			$this->assertTrue($unrelated->load('<form><field name="existing" type="text" /></form>'));
			$before = $unrelated->getXml()->asXML();
			$dispatcher->dispatch('onContentPrepareForm', new PrepareFormEvent('onContentPrepareForm', ['subject' => $unrelated, 'data' => []]));
			$this->assertSame($before, $unrelated->getXml()->asXML());

			if (isset($class::getSubscribedEvents()['onContentBeforeSave']))
			{
				$item = (object) ['params' => '{"privacy":{"activate":1},"other":"keep"}'];
				$save = new BeforeSaveEvent('onContentBeforeSave', ['context' => 'com_componentbuilder.admin_view', 'subject' => $item, 'isNew' => false, 'data' => []]);
				$dispatcher->dispatch('onContentBeforeSave', $save);
				$this->assertSame(['privacy' => ['activate' => 1], 'other' => 'keep'], json_decode($item->params, true, 512, JSON_THROW_ON_ERROR));
				$this->assertSame($item, $save->getItem());
				$this->assertSame([], $save->getArgument('result', []));
			}
		}
		finally
		{
			$components->setValue(null, $previousComponents);
			$paths->setValue(null, $previousPaths);
		}
	}

	/**
	 * Render supplied PHP through the real extension and Joomla Power pipeline.
	 *
	 * The generated tree is a focused source fixture, not a maintained database export.
	 *
	 * @param string $file Supplied plugin source.
	 * @return class-string Loaded generated plugin class.
	 * @since 6.2.0
	 */
	private function compilePlugin(string $file): string
	{
		$source = file_get_contents($file);
		$parser = new Parser();
		$this->assertSame(1, preg_match('/namespace\s+([^;]+);/', $source, $match));
		$originalClass = $match[1] . '\\' . basename($file, '.php');
		$plugin = (object) [
			'comment' => '/** Maintained JCB plugin definition. */',
			'class_name' => basename($file, '.php'),
			'extends' => '\\Joomla\\CMS\\Plugin\\CMSPlugin',
			'header' => implode(PHP_EOL, $parser->getUseStatements($source)),
			'main_class_code' => $parser->getClassCode($source),
		];
		$original = clone $plugin;
		$power = $this->createStub(PowerInterface::class);
		$power->method('get')->willReturnMap([
			['c06c5116-6b9d-487c-9b09-5094ec4506a3', 0, (object) ['_namespace' => 'Joomla\\Event', 'class_name' => 'SubscriberInterface', 'type' => 'interface']],
		]);
		$code = $this->renderer(Extension::class, ['power' => $power])->get($plugin);
		$this->assertStringContainsString($plugin->main_class_code, $code);
		$this->assertEquals($original, $plugin);
		$namespace = __NAMESPACE__ . '\\Compiled\\Run' . bin2hex(random_bytes(8));
		$injector = new Injector($power, new Extractor($this->createStub(DatabaseInterface::class), 6), $parser, $this->renderer(Placeholder::class));
		$code = $injector->power('<?php' . PHP_EOL . 'namespace ' . $namespace . ';' . PHP_EOL . $plugin->header . PHP_EOL . $code);
		$this->assertStringNotContainsString('Joomla___c06c5116_6b9d_487c_9b09_5094ec4506a3___Power', $code);
		$folder = basename(dirname($file, 3));
		$generatedFile = $this->writeTemporaryFile($folder . '/src/Extension/' . $plugin->class_name . '.php', $code);
		foreach (glob(dirname($file, 3) . '/forms/*.xml') as $form)
		{
			$this->writeTemporaryFile($folder . '/forms/' . basename($form), file_get_contents($form));
		}
		require $generatedFile;
		$class = $namespace . '\\' . $plugin->class_name;
		$this->assertTrue(is_subclass_of($class, SubscriberInterface::class));
		$this->assertSame($originalClass::getSubscribedEvents(), $class::getSubscribedEvents());

		return $class;
	}
}
