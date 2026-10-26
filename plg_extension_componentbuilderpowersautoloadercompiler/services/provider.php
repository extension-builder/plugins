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

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\DI\ServiceProviderInterface;
use Joomla\DI\Container;
use VDM\Plugin\Extension\ComponentbuilderPowersAutoloaderCompiler\Extension\ComponentbuilderPowersAutoloaderCompiler;

return new class () implements ServiceProviderInterface {
	/**
	* Registers the service provider with a DI container.
	*
	* @param   Container  $container  The DI container.
	*
	* @return  void
	* @since   4.3.0
	*/
	public function register(Container $container)
	{
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$plugin = new ComponentbuilderPowersAutoloaderCompiler(
					$container->get(DispatcherInterface::class),
					(array) PluginHelper::getPlugin('extension', 'componentbuilderpowersautoloadercompiler')
				);
				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};
