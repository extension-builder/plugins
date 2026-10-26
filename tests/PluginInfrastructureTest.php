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


use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Application\ConsoleApplication;
use Joomla\CMS\Console\Loader\WritableContainerLoader;
use Joomla\CMS\Console\Loader\WritableLoaderInterface;
use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\ApiRouter;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use ReflectionMethod;
use ReflectionProperty;
use SimpleXMLElement;
use VDM\Joomla\Componentbuilder\Console\Compiler;
use VDM\Joomla\Componentbuilder\Factory as ComponentbuilderFactory;


/**
 * Exercise generated providers, native installer loading and registered events.
 *
 * @since 6.2.0
 */
#[CoversNothing]
final class PluginInfrastructureTest extends PluginTestCase
{
	/**
	 * Joomla boots each real provider and registers its explicit subscriptions.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testNativeProvidersBootAndRegisterWithoutDeprecatedConstruction(): void
	{
		$generated = $this->compileSuppliedPlugins();
		$container = new Container();
		$database = $this->createStub(DatabaseInterface::class);
		$container->share(DispatcherInterface::class, $this->dispatcher);
		$container->share(DatabaseInterface::class, $database);
		$this->setJoomlaContainer($container);
		(new ReflectionProperty(CMSApplication::class, 'container'))->setValue($this->application, $container);
		$loadExtension = new ReflectionMethod(CMSApplication::class, 'loadExtension');
		$this->application->method('bootPlugin')->willReturnCallback(function (string $name, string $group) use ($loadExtension, $generated): PluginInterface
		{
			return $loadExtension->invoke($this->application, PluginInterface::class, $name . ':' . $group,
				$generated[$group . ':' . $name]['folder']);
		});
		$extensions = ExtensionHelper::$extensions;
		$deprecations = [];
		set_error_handler(static function (int $severity, string $message) use (&$deprecations): bool
		{
			$deprecations[] = $message;
			return true;
		}, E_DEPRECATED | E_USER_DEPRECATED);

		try
		{
			foreach ($generated as $fixture)
			{
				$group = $fixture['plugin']->group;
				$name = $fixture['plugin']->context_name;
				$record = (object) ['type' => $group, 'name' => $name, 'params' => '{"fixture":"native"}', 'id' => 1];
				(new ReflectionProperty(PluginHelper::class, 'plugins'))->setValue(null, [$record]);
				$this->assertTrue(PluginHelper::importPlugin($group, $name, true, $this->dispatcher));
				$plugin = ExtensionHelper::$extensions[PluginInterface::class][$name . ':' . $group];
				$this->assertInstanceOf($fixture['class'], $plugin);
				$this->assertInstanceOf(SubscriberInterface::class, $plugin);
				$this->assertSame($this->application, (new ReflectionMethod($plugin, 'getApplication'))->invoke($plugin));
				$this->assertSame('native', (new ReflectionProperty($plugin, 'params'))->getValue($plugin)->get('fixture'));

				foreach ($plugin::getSubscribedEvents() as $event => $handler)
				{
					$this->assertContains([$plugin, $handler], $this->dispatcher->getListeners($event));
				}
				if ($name === 'componentbuilderlanguagepackaging')
				{
					$this->assertSame($database, (new ReflectionMethod($plugin, 'getDatabase'))->invoke($plugin));
				}
			}
		}
		finally
		{
			restore_error_handler();
			ExtensionHelper::$extensions = $extensions;
		}

		$this->assertSame([], $deprecations);
	}

	/**
	 * The real Joomla loader accepts every installer without a legacy adapter.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testInstallerScriptsLoadNativelyAndPreserveLifecycleResults(): void
	{
		$generated = $this->compileSuppliedPlugins();
		$messages = [];
		$this->application->method('enqueueMessage')->willReturnCallback(static function (string $message, string $type) use (&$messages): void
		{
			$messages[] = [$message, $type];
		});
		$deprecations = [];
		set_error_handler(static function (int $severity, string $message) use (&$deprecations): bool
		{
			$deprecations[] = $message;
			return true;
		}, E_DEPRECATED | E_USER_DEPRECATED);

		try
		{
			foreach ($generated as $fixture)
			{
				$script = $fixture['folder'] . '/script.php';
				$this->assertCount(1, $fixture['plugin']->remove_file_paths);
				$obsolete = $fixture['site'] . $fixture['plugin']->remove_file_paths[0];
				$relativeObsolete = substr($obsolete, strlen($this->temporaryPath()) + 1);
				$this->writeTemporaryFile($relativeObsolete, 'obsolete plugin entry point');
				$installer = $this->createStub(Installer::class);
				$installer->method('getPath')->willReturn(dirname($script));
				$adapter = $this->createStub(InstallerAdapter::class);
				$adapter->method('getManifest')->willReturn(new SimpleXMLElement('<extension><scriptfile>script.php</scriptfile></extension>'));
				(new ReflectionProperty(InstallerAdapter::class, 'container'))->setValue($adapter, new Container());
				(new ReflectionProperty($adapter, 'parent'))->setValue($adapter, $installer);
				(new ReflectionMethod(InstallerAdapter::class, 'setupScriptfile'))->invoke($adapter);
				$native = $installer->manifestClass;
				$this->assertInstanceOf(InstallerScriptInterface::class, $native);
				$this->assertSame(realpath($script), (new ReflectionMethod($native, 'preflight'))->getFileName());
				$this->assertTrue($native->preflight('update', $adapter));
				$this->assertFileDoesNotExist($obsolete);
				$this->assertTrue($native->install($adapter));
				$this->assertTrue($native->update($adapter));
				$this->assertTrue($native->uninstall($adapter));
				$this->assertTrue($native->postflight('update', $adapter));
				$this->assertStringContainsString("isCompatible('6.0.0')", file_get_contents($script));
				if (basename(dirname($script)) === 'plg_console_componentbuildercommands')
				{
					$this->writeTemporaryFile($relativeObsolete, 'obsolete plugin entry point');
					$native = new ($native::class)();
					$this->assertFalse($native->preflight('install', $adapter));
					$this->assertFileExists($obsolete);
					$this->assertSame('error', $messages[0][1]);
					$this->assertStringContainsString('JCB must first be installed', $messages[0][0]);
					$this->createTemporaryDirectory(substr($fixture['site'], strlen($this->temporaryPath()) + 1)
						. '/administrator/components/com_componentbuilder');
					$this->assertTrue($native->preflight('install', $adapter));
					$this->assertFileDoesNotExist($obsolete);
				}
			}
		}
		finally
		{
			restore_error_handler();
		}

		$this->assertCount(1, $messages);
		$this->assertSame([], $deprecations);
	}

	/**
	 * Generate all supplied authored definitions before strict runtime checks begin.
	 *
	 * @return array Generated plugin trees keyed by group and element.
	 * @since 6.2.0
	 */
	private function compileSuppliedPlugins(): array
	{
		$generated = [];
		foreach (glob(JCB_PLUGINS_ROOT . '/plg_*/src/Extension/*.php') as $file)
		{
			$fixture = $this->compiledPlugin($file);
			$generated[$fixture['plugin']->group . ':' . $fixture['plugin']->context_name] = $fixture;
		}
		$this->assertCount(18, $generated);

		return $generated;
	}

	/**
	 * The API subscriber registers authenticated CRUD and GUID routes.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testApiSubscriberRetainsRoutesAndAuthenticationDefaults(): void
	{
		$class = $this->compiledPlugin('ComponentbuilderApiRoutes')['class'];
		$plugin = new $class([]);
		$plugin->setApplication($this->application);
		$this->dispatcher->addSubscriber($plugin);
		$router = new ApiRouter($this->application);
		$this->dispatcher->dispatch('onBeforeApiRoute', new BeforeApiRouteEvent('onBeforeApiRoute', [
			'subject' => $this->application,
			'router' => $router,
		]));
		$routes = $router->getRoutes();
		$this->assertCount(8, $routes);
		$matched = [];
		foreach ($routes as $route)
		{
			$this->assertSame('com_componentbuilder', $route->getDefaults()['component']);
			if (in_array('GET', $route->getMethods(), true))
			{
				$this->assertFalse($route->getDefaults()['public']);
			}
			if (preg_match($route->getRegex(), 'v1/componentbuilder/joomla_components/guid/2d8f2ef2-5f8c-420f-a3c9-c252315b10ab'))
			{
				$matched[$route->getMethods()[0]] = $route->getController();
			}
		}
		$this->assertSame([
			'GET' => 'joomla_component.displayItem',
			'PATCH' => 'joomla_component.edit',
			'DELETE' => 'joomla_component.delete',
		], $matched);
	}

	/**
	 * Native application dispatch still registers every package CLI command.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testConsoleSubscriberRegistersUsableCompilerAndPackageCommands(): void
	{
		$container = new Container();
		$loader = new WritableContainerLoader($container, []);
		$container->share(WritableLoaderInterface::class, $loader);
		$this->setJoomlaContainer($container);
		$class = $this->compiledPlugin('ComponentbuilderCommands')['class'];
		$plugin = new $class([]);
		$plugin->setApplication($this->application);
		$this->dispatcher->addSubscriber($plugin);
		$application = $this->createStub(ConsoleApplication::class);
		$this->dispatcher->dispatch(ApplicationEvents::BEFORE_EXECUTE, new ApplicationEvent(ApplicationEvents::BEFORE_EXECUTE, $application));

		$this->assertNotEmpty($this->dispatcher->getListeners(ApplicationEvents::BEFORE_EXECUTE));
		$this->assertNotEmpty($loader->getNames());
		$this->assertInstanceOf(Compiler::class, $loader->get('componentbuilder:compile:component'));
		$expected = ['componentbuilder:compile:component'];
		foreach (ComponentbuilderFactory::getSuperpowers() as $entity)
		{
			foreach (['init', 'get', 'reset', 'pull', 'push'] as $action)
			{
				$name = 'componentbuilder:' . $action . ':' . $entity;
				$this->assertTrue($loader->has($name));
				$expected[] = $name;
			}
		}
		$this->assertSame($expected, $loader->getNames());
	}
}
