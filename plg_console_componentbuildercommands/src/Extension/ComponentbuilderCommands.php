<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    30th April, 2015
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace VDM\Plugin\Console\ComponentbuilderCommands\Extension;

// The power autoloader for this project (JPATH_ADMINISTRATOR) area.
$power_autoloader = JPATH_ADMINISTRATOR . '/components/com_componentbuilder/src/Helper/PowerloaderHelper.php';
if (file_exists($power_autoloader))
{
	require_once $power_autoloader;
}

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Application\ApplicationEvents;
use Joomla\Console\Application as ConsoleApplication;
use Joomla\CMS\Console\Loader\WritableLoaderInterface;
use Joomla\Event\Event;
use Joomla\DI\Container;
use VDM\Joomla\Componentbuilder\Console\Package\Init;
use VDM\Joomla\Componentbuilder\Console\Package\Get;
use VDM\Joomla\Componentbuilder\Console\Package\Reset;
use VDM\Joomla\Componentbuilder\Console\Package\Pull;
use VDM\Joomla\Componentbuilder\Console\Package\Push;
use VDM\Joomla\Data\Power\Item;
use VDM\Joomla\Componentbuilder\Console\Compiler;
use VDM\Joomla\Componentbuilder\Factory as ComponentbuilderFactory;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Console - ComponentbuilderCommands plugin.
 *
 * @package   ComponentbuilderCommands
 * @since     6.0.0
 */
final class ComponentbuilderCommands extends CMSPlugin implements SubscriberInterface
{
	/**
	 * The target package classes
	 *
	 * @var   array
	 * @since 5.1.4
	 */
	protected array $packageClasses = [
		'init' => Init::class,
		'get' => Get::class,
		'reset' => Reset::class,
		'pull' => Pull::class,
		'push' => Push::class
	];

	/**
	 * Returns an array of events this plugin is subscribing to.
	 *
	 * @return array
	 * @since 4.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		// Return an array of event names and corresponding callback methods
		return [
			ApplicationEvents::BEFORE_EXECUTE => 'registerCommands',
		];
	}

	/**
	 * Registers CLI commands with the application container.
	 *
	 * This method is called just before the console application executes,
	 * giving us access to the container for command registration.
	 *
	 * @param   Event  $event  The application event
	 *
	 * @return  void
	 * @since   5.1.4
	 */
	public function registerCommands(Event $event): void
	{
		// Get the DI container from the application
		$container = $this->getContainer_($event);

		if ($container === null)
		{
			return;
		}

		$this->registerPackageCommands($container);
	}

	/**
	 * Gets the DI container from the console application.
	 *
	 * Ensures that the getContainer() method exists AND is publicly callable
	 * before invoking it. Falls back to Joomla's global container when needed.
	 *
	 * @param   Event  $event  The application event
	 *
	 * @return  Container|null  The DI container or null if not available
	 * @since   5.1.4
	 */
	protected function getContainer_(Event $event): ?Container
	{
		$app = $event->getApplication();

		// Console context with a publicly callable getContainer()
		if ($app instanceof ConsoleApplication && is_callable([$app, 'getContainer']))
		{
			try
			{
				$method = new \ReflectionMethod($app, 'getContainer');

				if ($method->isPublic())
				{
					return $app->getContainer();
				}
			}
			catch (\ReflectionException $e)
			{
				// Reflection failure -> fallback handled below
			}
		}

		return Factory::getContainer() ?? null;
	}

	/**
	 * Registers the plugin's package CLI commands.
	 *
	 * Override or modify this method to register your specific commands.
	 *
	 * @param   Container     $container  The DI container
	 *
	 * @return  void
	 * @since   5.1.4
	 */
	protected function registerPackageCommands(Container $container): void
	{
 		$loader = $container->get(WritableLoaderInterface::class);
		$item = new Item();

		// we first load the compiler command
		$serviceId = "componentbuilder.compile.component.command";
		$command = "componentbuilder:compile:component";
		$container->share(
			$serviceId,
			static function (Container $container) use ($command, $item) {
				return new Compiler($command, $item);
			}, true
		);
		$loader->add($command, $serviceId);

		// now load the packager commands
		foreach (ComponentbuilderFactory::getSuperpowers() as $entity)
		{
			foreach ($this->packageClasses as $action => $class)
			{
				$serviceId = "componentbuilder.{$action}.{$entity}.command";
				$command = "componentbuilder:{$action}:{$entity}";
				$container->share(
					$serviceId,
					static function (Container $container) use ($item, $class, $entity, $command) {
						return new $class($command, $entity, $item);
					}, true
				);

				$loader->add($command, $serviceId);
			}
		}
	}
}
