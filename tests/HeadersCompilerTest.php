<?php
/**
 * @package    Joomla.Component.Builder.Tests
 * @created    27th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace VDM\Plugin\Tests;


use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Language\LanguageFactory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Joomla\DI\Container;
use Joomla\Event\Dispatcher;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Factory as CompilerFactory;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaSix\Event;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaSix\Header;


/**
 * Header-selected imports and Powers must survive native plugin generation.
 *
 * @since 6.2.0
 */
#[CoversNothing]
final class HeadersCompilerTest extends PluginTestCase
{
	/**
	 * Test raw database params and arrays decoded by an earlier compiler plugin.
	 *
	 * @return iterable
	 * @since 6.2.0
	 */
	public static function representations(): iterable
	{
		yield 'database JSON' => [true];
		yield 'decoded array' => [false];
	}

	/**
	 * Boot the emitted provider and execute the real JCB header and Power hooks.
	 * Process isolation resets Joomla's function-static plugin import cache.
	 *
	 * @param bool $encode Whether params retain their database JSON encoding.
	 * @return void
	 * @since 6.2.0
	 */
	#[DataProvider('representations')]
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testGeneratedSubscriptionsPreserveHeadersAndLinkedPowers(bool $encode): void
	{
		$pluginsProperty = new ReflectionProperty(PluginHelper::class, 'plugins');
		$previousPlugins = $pluginsProperty->getValue();
		$pluginsProperty->setValue(null, [(object) ['type' => 'extension', 'name' => 'componentbuilderheaderscompiler', 'params' => '{}']]);
		$dispatcher = new Dispatcher();
		$fixture = $this->compiledPlugin('ComponentbuilderHeadersCompiler');
		$this->assertStringNotContainsString('function getSubscribedEvents', $fixture['plugin']->main_class_code);
		$application = $this->createStub(CMSApplication::class);
		$application->method('getDispatcher')->willReturn($dispatcher);
		$this->setJoomlaApplication($application);
		$container = new Container();
		$container->share(DispatcherInterface::class, $dispatcher);
		$container->share(LanguageFactoryInterface::class, new LanguageFactory());
		$query = $this->createStub(QueryInterface::class);
		foreach (['select', 'from', 'where'] as $method)
		{
			$query->method($method)->willReturnSelf();
		}
		$db = $this->createStub(DatabaseInterface::class);
		$db->method('getQuery')->willReturn($query);
		$db->method('getNumRows')->willReturn(1);
		$db->method('loadObject')->willReturn((object) ['guid' => '123e4567-e89b-12d3-a456-426614174000', 'namespace' => 'VDM.Joomla.Componentbuilder.Search.Factory']);
		$db->method('quoteName')->willReturnCallback(static fn($name, $as = null) => $name);
		$db->method('quote')->willReturnCallback(static fn($name, $escape = true) => "'" . $name . "'");
		$container->share(DatabaseInterface::class, $db);
		$this->setJoomlaContainer($container);
		$provider = require $fixture['folder'] . '/services/provider.php';
		$pluginContainer = new Container($container);
		$provider->register($pluginContainer);
		$plugin = $pluginContainer->get(PluginInterface::class);
		$this->assertInstanceOf($fixture['class'], $plugin);
		$application->method('bootPlugin')->willReturn($plugin);
		$power = new class
		{
			/**
			 * Selected roots passed to the compiler Power loader.
			 *
			 * @var array
			 * @since 6.2.0
			 */
			public array $loaded = [];

			/**
			 * Record the Power catalogue boundary without fetching repositories.
			 *
			 * @param array $guids Selected GUIDs and build modes.
			 * @return void
			 * @since 6.2.0
			 */
			public function load(array $guids): void
			{
				$this->loaded = $guids;
			}
		};
		$compiler = new Container();
		$compiler->share('Component.Placeholder', new class
		{
			/**
			 * The catalogue fixture uses complete namespaces without placeholders.
			 *
			 * @return array
			 * @since 6.2.0
			 */
			public function get(): array
			{
				return [];
			}
		});
		$compiler->share('Power', $power);
		$factory = new ReflectionProperty(CompilerFactory::class, 'container');
		$previousCompiler = $factory->getValue();
		$factory->setValue(null, $compiler);
		try
		{
			$event = new Event(new Registry(['compiler_plugin' => ['componentbuilderheaderscompiler']]));
			foreach (['jcb_ce_onBeforeModelViewData', 'jcb_ce_onBeforeModelCustomViewData',
				'jcb_ce_onBeforeModelDynamicGetData', 'jcb_ce_onBeforeModelComponentData',
				'jcb_ce_setClassHeader', 'jcb_ce_onBeforeGetComponentData', 'jcb_ce_onAfterGetComponentData'] as $name)
			{
				$this->assertTrue($dispatcher->hasListener([$plugin, $name], $name), $name);
			}
			$event->trigger('jcb_ce_onBeforeGetComponentData');
			$params = ['custom_admin_view_headers' => [
				'add_custom_admin_view_html' => 1,
				'custom_admin_view_html' => 'use Joomla\\CMS\\Form\\Form;',
				'add_custom_admin_view' => 1,
				'custom_admin_view' => 'use Joomla\\CMS\\Session\\Session;',
				'add_ajax_model' => 1,
				'power_ajax_model' => [['power' => '123e4567-e89b-12d3-a456-426614174000', 'build' => 1, 'as' => 'SearchFactory']],
			]];
			$params = $encode ? json_encode($params) : $params;
			$view = (object) ['code' => 'compiler', 'params' => $params];
			$id = 1;
			$table = 'custom_admin_view';
			$event->trigger('jcb_ce_onBeforeModelCustomViewData', [&$view, &$id, &$table]);
			$event->trigger('jcb_ce_onAfterGetComponentData');
			$config = $this->config();
			$config->set('build_target', 'admin');
			$config->set('uikit', 0);
			$header = $this->renderer(Header::class, ['event' => $event]);
			foreach ([
				['custom.admin.view.html', 'compiler', 'use Joomla\\CMS\\Form\\Form;'],
				['custom.admin.view', 'compiler', 'use Joomla\\CMS\\Session\\Session;'],
				['ajax.admin.model', 'ajax', 'use VDM\\Joomla\\Componentbuilder\\Search\\Factory as SearchFactory;'],
			] as [$context, $viewName, $expected])
			{
				$rendered = $header->get($context, $viewName);
				$this->assertStringContainsString($expected, $rendered);
			}
			$this->assertSame(['123e4567-e89b-12d3-a456-426614174000' => 1], $power->loaded);
		}
		finally
		{
			$pluginsProperty->setValue(null, $previousPlugins);
			$factory->setValue(null, $previousCompiler);
		}
	}
}
