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
use Joomla\Event\Dispatcher;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;


/**
 * Every maintained plugin body must pass the actual Joomla 6 compiler.
 *
 * @since 6.2.0
 */
#[CoversNothing]
final class CompilerIntegrationTest extends PluginTestCase
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
		$fixture = $this->compiledPlugin($file);
		if ($fixture['plugin']->group === 'extension')
		{
			preg_match_all('/public function (jcb_ce_[a-zA-Z0-9_]+)\(/', file_get_contents($file), $hooks);
			$this->assertNotEmpty($hooks[1]);
			$this->assertSame(array_combine($hooks[1], $hooks[1]), $fixture['class']::getSubscribedEvents());
		}
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
		$class = $this->compiledPlugin($file)['class'];
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

}
