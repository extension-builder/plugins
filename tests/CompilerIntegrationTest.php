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


use Joomla\Database\DatabaseInterface;
use Joomla\Event\SubscriberInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
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
	 * Preserve the real stored body, resolve Power imports, and execute the emitted map.
	 *
	 * @param string $file Maintained plugin source.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	#[DataProvider('plugins')]
	public function testPluginDefinitionCompilesWithoutLegacyListenerAdapters(string $file): void
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
		$namespace = __NAMESPACE__ . '\\Compiled';
		$injector = new Injector($power, new Extractor($this->createStub(DatabaseInterface::class), 6), $parser, $this->renderer(Placeholder::class));
		$code = $injector->power('<?php' . PHP_EOL . 'namespace ' . $namespace . ';' . PHP_EOL . $plugin->header . PHP_EOL . $code);
		$this->assertStringNotContainsString('Joomla___c06c5116_6b9d_487c_9b09_5094ec4506a3___Power', $code);
		$generatedFile = $this->writeTemporaryFile($plugin->class_name . '.php', $code);
		require $generatedFile;
		$class = $namespace . '\\' . $plugin->class_name;
		$this->assertTrue(is_subclass_of($class, SubscriberInterface::class));
		$this->assertSame($originalClass::getSubscribedEvents(), $class::getSubscribedEvents());
	}
}
