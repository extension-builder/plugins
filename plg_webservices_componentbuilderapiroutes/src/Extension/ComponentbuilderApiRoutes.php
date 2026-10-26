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
namespace VDM\Plugin\Webservices\ComponentbuilderApiRoutes\Extension;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Webservices - ComponentbuilderApiRoutes plugin.
 *
 * @package   ComponentbuilderApiRoutes
 * @since     1.0.0
 */
final class ComponentbuilderApiRoutes extends CMSPlugin
{
/**
	 * Register the JSON:API routes of com_componentbuilder.
	 *
	 * @param   \Joomla\CMS\Event\Application\BeforeApiRouteEvent  $event  The event.
	 *
	 * @return  void
	 *
	 * @since   5.0.0
	 */
	public function onBeforeApiRoute(\Joomla\CMS\Event\Application\BeforeApiRouteEvent $event): void
	{
		// Take the API router from the event.
		$router = $event->getRouter();

		// Register the JSON:API routes of com_componentbuilder.
		$defaults = ['component' => 'com_componentbuilder'];
		$getDefaults = ['public' => false, 'component' => 'com_componentbuilder'];

		// The routes of the joomla_components resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_components', 'joomla_components.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_components/:id', 'joomla_component.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_components/guid/:guid', 'joomla_component.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/joomla_components', 'joomla_component.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/joomla_components/:id', 'joomla_component.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/joomla_components/guid/:guid', 'joomla_component.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/joomla_components/:id', 'joomla_component.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/joomla_components/guid/:guid', 'joomla_component.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the joomla_modules resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_modules', 'joomla_modules.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_modules/:id', 'joomla_module.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_modules/guid/:guid', 'joomla_module.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/joomla_modules', 'joomla_module.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/joomla_modules/:id', 'joomla_module.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/joomla_modules/guid/:guid', 'joomla_module.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/joomla_modules/:id', 'joomla_module.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/joomla_modules/guid/:guid', 'joomla_module.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the joomla_plugins resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_plugins', 'joomla_plugins.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_plugins/:id', 'joomla_plugin.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_plugins/guid/:guid', 'joomla_plugin.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/joomla_plugins', 'joomla_plugin.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/joomla_plugins/:id', 'joomla_plugin.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/joomla_plugins/guid/:guid', 'joomla_plugin.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/joomla_plugins/:id', 'joomla_plugin.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/joomla_plugins/guid/:guid', 'joomla_plugin.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the joomla_powers resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_powers', 'joomla_powers.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_powers/:id', 'joomla_power.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_powers/guid/:guid', 'joomla_power.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/joomla_powers', 'joomla_power.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/joomla_powers/:id', 'joomla_power.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/joomla_powers/guid/:guid', 'joomla_power.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/joomla_powers/:id', 'joomla_power.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/joomla_powers/guid/:guid', 'joomla_power.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the powers resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/powers', 'powers.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/powers/:id', 'power.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/powers/guid/:guid', 'power.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/powers', 'power.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/powers/:id', 'power.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/powers/guid/:guid', 'power.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/powers/:id', 'power.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/powers/guid/:guid', 'power.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the admin_views resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/admin_views', 'admin_views.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/admin_views/:id', 'admin_view.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/admin_views/guid/:guid', 'admin_view.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/admin_views', 'admin_view.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/admin_views/:id', 'admin_view.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/admin_views/guid/:guid', 'admin_view.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/admin_views/:id', 'admin_view.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/admin_views/guid/:guid', 'admin_view.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the custom_admin_views resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/custom_admin_views', 'custom_admin_views.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/custom_admin_views/:id', 'custom_admin_view.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/custom_admin_views/guid/:guid', 'custom_admin_view.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/custom_admin_views', 'custom_admin_view.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/custom_admin_views/:id', 'custom_admin_view.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/custom_admin_views/guid/:guid', 'custom_admin_view.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/custom_admin_views/:id', 'custom_admin_view.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/custom_admin_views/guid/:guid', 'custom_admin_view.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the site_views resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/site_views', 'site_views.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/site_views/:id', 'site_view.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/site_views/guid/:guid', 'site_view.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/site_views', 'site_view.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/site_views/:id', 'site_view.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/site_views/guid/:guid', 'site_view.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/site_views/:id', 'site_view.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/site_views/guid/:guid', 'site_view.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the templates resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/templates', 'templates.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/templates/:id', 'template.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/templates/guid/:guid', 'template.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/templates', 'template.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/templates/:id', 'template.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/templates/guid/:guid', 'template.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/templates/:id', 'template.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/templates/guid/:guid', 'template.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the layouts resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/layouts', 'layouts.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/layouts/:id', 'layout.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/layouts/guid/:guid', 'layout.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/layouts', 'layout.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/layouts/:id', 'layout.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/layouts/guid/:guid', 'layout.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/layouts/:id', 'layout.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/layouts/guid/:guid', 'layout.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the dynamic_gets resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/dynamic_gets', 'dynamic_gets.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/dynamic_gets/:id', 'dynamic_get.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/dynamic_gets/guid/:guid', 'dynamic_get.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/dynamic_gets', 'dynamic_get.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/dynamic_gets/:id', 'dynamic_get.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/dynamic_gets/guid/:guid', 'dynamic_get.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/dynamic_gets/:id', 'dynamic_get.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/dynamic_gets/guid/:guid', 'dynamic_get.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the custom_codes resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/custom_codes', 'custom_codes.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/custom_codes/:id', 'custom_code.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/custom_codes', 'custom_code.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/custom_codes/:id', 'custom_code.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/custom_codes/:id', 'custom_code.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the class_extendings resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/class_extendings', 'class_extendings.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/class_extendings/:id', 'class_extends.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/class_extendings/guid/:guid', 'class_extends.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/class_extendings', 'class_extends.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/class_extendings/:id', 'class_extends.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/class_extendings/guid/:guid', 'class_extends.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/class_extendings/:id', 'class_extends.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/class_extendings/guid/:guid', 'class_extends.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the class_properties resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/class_properties', 'class_properties.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/class_properties/:id', 'class_property.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/class_properties/guid/:guid', 'class_property.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/class_properties', 'class_property.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/class_properties/:id', 'class_property.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/class_properties/guid/:guid', 'class_property.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/class_properties/:id', 'class_property.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/class_properties/guid/:guid', 'class_property.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the class_methods resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/class_methods', 'class_methods.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/class_methods/:id', 'class_method.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/class_methods/guid/:guid', 'class_method.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/class_methods', 'class_method.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/class_methods/:id', 'class_method.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/class_methods/guid/:guid', 'class_method.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/class_methods/:id', 'class_method.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/class_methods/guid/:guid', 'class_method.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the placeholders resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/placeholders', 'placeholders.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/placeholders/:id', 'placeholder.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/placeholders', 'placeholder.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/placeholders/:id', 'placeholder.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/placeholders/:id', 'placeholder.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the libraries resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/libraries', 'libraries.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/libraries/:id', 'library.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/libraries/guid/:guid', 'library.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/libraries', 'library.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/libraries/:id', 'library.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/libraries/guid/:guid', 'library.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/libraries/:id', 'library.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/libraries/guid/:guid', 'library.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the snippets resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/snippets', 'snippets.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/snippets/:id', 'snippet.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/snippets/guid/:guid', 'snippet.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/snippets', 'snippet.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/snippets/:id', 'snippet.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/snippets/guid/:guid', 'snippet.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/snippets/:id', 'snippet.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/snippets/guid/:guid', 'snippet.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the validation_rules resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/validation_rules', 'validation_rules.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/validation_rules/:id', 'validation_rule.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/validation_rules', 'validation_rule.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/validation_rules/:id', 'validation_rule.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/validation_rules/:id', 'validation_rule.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the fields resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/fields', 'fields.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/fields/:id', 'field.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/fields/guid/:guid', 'field.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/fields', 'field.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/fields/:id', 'field.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/fields/guid/:guid', 'field.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/fields/:id', 'field.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/fields/guid/:guid', 'field.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the fieldtypes resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/fieldtypes', 'fieldtypes.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/fieldtypes/:id', 'fieldtype.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/fieldtypes/guid/:guid', 'fieldtype.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/fieldtypes', 'fieldtype.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/fieldtypes/:id', 'fieldtype.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/fieldtypes/guid/:guid', 'fieldtype.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/fieldtypes/:id', 'fieldtype.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/fieldtypes/guid/:guid', 'fieldtype.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the language_translations resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/language_translations', 'language_translations.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/language_translations/:id', 'language_translation.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/language_translations', 'language_translation.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/language_translations/:id', 'language_translation.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/language_translations/:id', 'language_translation.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the languages resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/languages', 'languages.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/languages/:id', 'language.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/languages', 'language.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/languages/:id', 'language.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/languages/:id', 'language.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the servers resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/servers', 'servers.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/servers/:id', 'server.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/servers', 'server.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/servers/:id', 'server.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/servers/:id', 'server.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the repositories resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/repositories', 'repositories.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/repositories/:id', 'repository.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/repositories/guid/:guid', 'repository.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/repositories', 'repository.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/repositories/:id', 'repository.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/repositories/guid/:guid', 'repository.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/repositories/:id', 'repository.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/repositories/guid/:guid', 'repository.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the help_documents resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/help_documents', 'help_documents.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/help_documents/:id', 'help_document.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/help_documents', 'help_document.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/help_documents/:id', 'help_document.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/help_documents/:id', 'help_document.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the admins_fields resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/admins_fields', 'admins_fields.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/admins_fields/:id', 'admin_fields.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/admins_fields', 'admin_fields.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/admins_fields/:id', 'admin_fields.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/admins_fields/:id', 'admin_fields.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the admins_fields_conditions resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/admins_fields_conditions', 'admins_fields_conditions.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/admins_fields_conditions/:id', 'admin_fields_conditions.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/admins_fields_conditions', 'admin_fields_conditions.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/admins_fields_conditions/:id', 'admin_fields_conditions.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/admins_fields_conditions/:id', 'admin_fields_conditions.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the admins_fields_relations resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/admins_fields_relations', 'admins_fields_relations.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/admins_fields_relations/:id', 'admin_fields_relations.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/admins_fields_relations', 'admin_fields_relations.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/admins_fields_relations/:id', 'admin_fields_relations.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/admins_fields_relations/:id', 'admin_fields_relations.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the admins_custom_tabs resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/admins_custom_tabs', 'admins_custom_tabs.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/admins_custom_tabs/:id', 'admin_custom_tabs.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/admins_custom_tabs', 'admin_custom_tabs.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/admins_custom_tabs/:id', 'admin_custom_tabs.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/admins_custom_tabs/:id', 'admin_custom_tabs.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the components_admin_views resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_admin_views', 'components_admin_views.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_admin_views/:id', 'component_admin_views.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/components_admin_views', 'component_admin_views.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/components_admin_views/:id', 'component_admin_views.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/components_admin_views/:id', 'component_admin_views.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the components_site_views resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_site_views', 'components_site_views.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_site_views/:id', 'component_site_views.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/components_site_views', 'component_site_views.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/components_site_views/:id', 'component_site_views.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/components_site_views/:id', 'component_site_views.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the components_custom_admin_views resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_custom_admin_views', 'components_custom_admin_views.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_custom_admin_views/:id', 'component_custom_admin_views.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/components_custom_admin_views', 'component_custom_admin_views.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/components_custom_admin_views/:id', 'component_custom_admin_views.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/components_custom_admin_views/:id', 'component_custom_admin_views.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the components_updates resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_updates', 'components_updates.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_updates/:id', 'component_updates.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/components_updates', 'component_updates.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/components_updates/:id', 'component_updates.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/components_updates/:id', 'component_updates.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the components_mysql_tweaks resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_mysql_tweaks', 'components_mysql_tweaks.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_mysql_tweaks/:id', 'component_mysql_tweaks.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/components_mysql_tweaks', 'component_mysql_tweaks.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/components_mysql_tweaks/:id', 'component_mysql_tweaks.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/components_mysql_tweaks/:id', 'component_mysql_tweaks.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the components_custom_admin_menus resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_custom_admin_menus', 'components_custom_admin_menus.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_custom_admin_menus/:id', 'component_custom_admin_menus.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/components_custom_admin_menus', 'component_custom_admin_menus.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/components_custom_admin_menus/:id', 'component_custom_admin_menus.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/components_custom_admin_menus/:id', 'component_custom_admin_menus.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the components_routers resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_routers', 'components_routers.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_routers/:id', 'component_router.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/components_routers', 'component_router.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/components_routers/:id', 'component_router.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/components_routers/:id', 'component_router.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the components_config resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_config', 'components_config.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_config/:id', 'component_config.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/components_config', 'component_config.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/components_config/:id', 'component_config.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/components_config/:id', 'component_config.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the components_dashboard resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_dashboard', 'components_dashboard.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_dashboard/:id', 'component_dashboard.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/components_dashboard', 'component_dashboard.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/components_dashboard/:id', 'component_dashboard.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/components_dashboard/:id', 'component_dashboard.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the components_files_folders resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_files_folders', 'components_files_folders.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_files_folders/:id', 'component_files_folders.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/components_files_folders', 'component_files_folders.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/components_files_folders/:id', 'component_files_folders.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/components_files_folders/:id', 'component_files_folders.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the components_placeholders resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_placeholders', 'components_placeholders.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_placeholders/:id', 'component_placeholders.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/components_placeholders', 'component_placeholders.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/components_placeholders/:id', 'component_placeholders.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/components_placeholders/:id', 'component_placeholders.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the components_plugins resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_plugins', 'components_plugins.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_plugins/:id', 'component_plugins.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/components_plugins', 'component_plugins.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/components_plugins/:id', 'component_plugins.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/components_plugins/:id', 'component_plugins.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the components_modules resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_modules', 'components_modules.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/components_modules/:id', 'component_modules.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/components_modules', 'component_modules.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/components_modules/:id', 'component_modules.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/components_modules/:id', 'component_modules.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the snippet_types resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/snippet_types', 'snippet_types.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/snippet_types/:id', 'snippet_type.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/snippet_types/guid/:guid', 'snippet_type.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/snippet_types', 'snippet_type.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/snippet_types/:id', 'snippet_type.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/snippet_types/guid/:guid', 'snippet_type.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/snippet_types/:id', 'snippet_type.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/snippet_types/guid/:guid', 'snippet_type.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the libraries_config resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/libraries_config', 'libraries_config.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/libraries_config/:id', 'library_config.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/libraries_config', 'library_config.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/libraries_config/:id', 'library_config.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/libraries_config/:id', 'library_config.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the libraries_files_folders_urls resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/libraries_files_folders_urls', 'libraries_files_folders_urls.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/libraries_files_folders_urls/:id', 'library_files_folders_urls.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/libraries_files_folders_urls', 'library_files_folders_urls.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/libraries_files_folders_urls/:id', 'library_files_folders_urls.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/libraries_files_folders_urls/:id', 'library_files_folders_urls.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the joomla_modules_updates resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_modules_updates', 'joomla_modules_updates.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_modules_updates/:id', 'joomla_module_updates.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/joomla_modules_updates', 'joomla_module_updates.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/joomla_modules_updates/:id', 'joomla_module_updates.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/joomla_modules_updates/:id', 'joomla_module_updates.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the joomla_modules_files_folders_urls resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_modules_files_folders_urls', 'joomla_modules_files_folders_urls.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_modules_files_folders_urls/:id', 'joomla_module_files_folders_urls.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/joomla_modules_files_folders_urls', 'joomla_module_files_folders_urls.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/joomla_modules_files_folders_urls/:id', 'joomla_module_files_folders_urls.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/joomla_modules_files_folders_urls/:id', 'joomla_module_files_folders_urls.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the joomla_plugin_groups resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_plugin_groups', 'joomla_plugin_groups.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_plugin_groups/:id', 'joomla_plugin_group.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_plugin_groups/guid/:guid', 'joomla_plugin_group.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/joomla_plugin_groups', 'joomla_plugin_group.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/joomla_plugin_groups/:id', 'joomla_plugin_group.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/joomla_plugin_groups/guid/:guid', 'joomla_plugin_group.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/joomla_plugin_groups/:id', 'joomla_plugin_group.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/joomla_plugin_groups/guid/:guid', 'joomla_plugin_group.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
		]);

		// The routes of the joomla_plugins_updates resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_plugins_updates', 'joomla_plugins_updates.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_plugins_updates/:id', 'joomla_plugin_updates.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/joomla_plugins_updates', 'joomla_plugin_updates.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/joomla_plugins_updates/:id', 'joomla_plugin_updates.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/joomla_plugins_updates/:id', 'joomla_plugin_updates.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the joomla_plugins_files_folders_urls resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_plugins_files_folders_urls', 'joomla_plugins_files_folders_urls.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/joomla_plugins_files_folders_urls/:id', 'joomla_plugin_files_folders_urls.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/componentbuilder/joomla_plugins_files_folders_urls', 'joomla_plugin_files_folders_urls.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/componentbuilder/joomla_plugins_files_folders_urls/:id', 'joomla_plugin_files_folders_urls.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/componentbuilder/joomla_plugins_files_folders_urls/:id', 'joomla_plugin_files_folders_urls.delete', ['id' => '(\d+)'], $defaults),
		]);

		// The routes of the compiler resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/compiler', 'compiler.displayList', [], $getDefaults),
		]);

		// The routes of the extrusion resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/componentbuilder/extrusion', 'extrusion.displayList', [], $getDefaults),
		]);
	}
}
