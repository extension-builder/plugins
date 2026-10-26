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
namespace VDM\Plugin\Extension\ComponentbuilderHeadersCompiler\Extension;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Factory;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Joomla\Utilities\GuidHelper;
use VDM\Joomla\Utilities\String\NamespaceHelper;
use Joomla\Event\SubscriberInterface;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Extension - Componentbuilder Headers Compiler plugin.
 *
 * @package   ComponentbuilderHeadersCompiler
 * @since     4.0.1
 */
final class ComponentbuilderHeadersCompiler extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Global switch to see if a file has custom headers.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $loadHeaders = false;

	/**
	 * The active headers
	 *
	 * @var    array
	 * @since  1.0.8
	 */
	protected $activeHeaders = [];

	/**
	 * The compiler placeholders values
	 *
	 * @var    array
	 * @since  1.0.6
	 */
	protected $placeholders = array();

	/**
	 * The powers to include in project
	 *
	 * @var    array
	 * @since  1.0.6
	 */
	protected $linkedPowers = array();

	/**
	 * The Targets
	 *
	 * @var    array
	 * @since  1.0.8
	 */
	protected $targets = array(
		'admin_view_headers'          =>
			array(
				'add_admin_view_model'       => array(
					'field'   => 'admin_view_model',
					'context' => 'admin.view.model',
					'view'    => 'name_single'
				),
				'add_admin_view'             => array(
					'field'   => 'admin_view',
					'context' => 'admin.view',
					'view'    => 'name_single'
				),
				'add_admin_view_html'        => array(
					'field'   => 'admin_view_html',
					'context' => 'admin.view.html',
					'view'    => 'name_single'
				),
				'add_site_admin_view_html'   => array(
					'field'   => 'site_admin_view_html',
					'context' => 'site.admin.view.html',
					'view'    => 'name_single'
				),
				'add_admin_view_controller'  => array(
					'field'   => 'admin_view_controller',
					'context' => 'admin.view.controller',
					'view'    => 'name_single'
				),
				'add_ajax_model'             => array(
					'field'   => 'ajax_model',
					'context' => 'ajax.admin.model',
					'view'    => 'ajax'
				),
				'add_admin_views_model'      => array(
					'field'   => 'admin_views_model',
					'context' => 'admin.views.model',
					'view'    => 'name_list'
				),
				'add_admin_views'            => array(
					'field'   => 'admin_views',
					'context' => 'admin.views',
					'view'    => 'name_list'
				),
				'add_admin_views_html'       => array(
					'field'   => 'admin_views_html',
					'context' => 'admin.views.html',
					'view'    => 'name_list'
				),
				'add_admin_views_controller' => array(
					'field'   => 'admin_views_controller',
					'context' => 'admin.views.controller',
					'view'    => 'name_list'
				),
				'add_import_custom_controller'       => array(
					'field'   => 'import_custom_controller',
					'context' => 'import.custom.controller',
					'view'    => 'name_list'
				),
				'add_import_custom_model' => array(
					'field'   => 'import_custom_model',
					'context' => 'import.custom.model',
					'view'    => 'name_list'
				)
			),
		'site_view_headers'           =>
			array(
				'add_site_view_model'       => array(
					'field'   => 'site_view_model',
					'context' => 'site.view.model',
					'view'    => 'code'
				),
				'add_site_view'             => array(
					'field'   => 'site_view',
					'context' => 'site.view',
					'view'    => 'code'
				),
				'add_site_view_html'        => array(
					'field'   => 'site_view_html',
					'context' => 'site.view.html',
					'view'    => 'code'
				),
				'add_site_view_controller'  => array(
					'field'   => 'site_view_controller',
					'context' => 'site.view.controller',
					'view'    => 'code'
				),
				'add_site_views_model'      => array(
					'field'   => 'site_views_model',
					'context' => 'site.views.model',
					'view'    => 'code'
				),
				'add_site_views'            => array(
					'field'   => 'site_views',
					'context' => 'site.views',
					'view'    => 'code'
				),
				'add_site_views_html'       => array(
					'field'   => 'site_views_html',
					'context' => 'site.views.html',
					'view'    => 'code'
				),
				'add_site_views_controller' => array(
					'field'   => 'site_views_controller',
					'context' => 'site.views.controller',
					'view'    => 'code'
				),
				'add_ajax_model'            => array(
					'field'   => 'ajax_model',
					'context' => 'ajax.site.model',
					'view'    => 'ajax'
				)
			),
		'custom_admin_view_headers'   =>
			array(
				'add_custom_admin_view_model'       => array(
					'field'   => 'custom_admin_view_model',
					'context' => 'custom.admin.view.model',
					'view'    => 'code'
				),
				'add_custom_admin_view'             => array(
					'field'   => 'custom_admin_view',
					'context' => 'custom.admin.view',
					'view'    => 'code'
				),
				'add_custom_admin_view_html'        => array(
					'field'   => 'custom_admin_view_html',
					'context' => 'custom.admin.view.html',
					'view'    => 'code'
				),
				'add_custom_admin_view_controller'  => array(
					'field'   => 'custom_admin_view_controller',
					'context' => 'custom.admin.view.controller',
					'view'    => 'code'
				),
				'add_custom_admin_views_model'      => array(
					'field'   => 'custom_admin_views_model',
					'context' => 'custom.admin.views.model',
					'view'    => 'code'
				),
				'add_custom_admin_views'            => array(
					'field'   => 'custom_admin_views',
					'context' => 'custom.admin.views',
					'view'    => 'code'
				),
				'add_custom_admin_views_html'       => array(
					'field'   => 'custom_admin_views_html',
					'context' => 'custom.admin.views.html',
					'view'    => 'code'
				),
				'add_custom_admin_views_controller' => array(
					'field'   => 'custom_admin_views_controller',
					'context' => 'custom.admin.views.controller',
					'view'    => 'code'
				),
				'add_ajax_model'                    => array(
					'field'   => 'ajax_model',
					'context' => 'ajax.admin.model',
					'view'    => 'ajax'
				)
			),
		'dynamic_get_headers'           =>
			array(
				'add_site_view_model'       => array(
					'field'   => 'site_view_model',
					'context' => 'site.view.model',
					'view'    => 'code'
				),
				'add_site_view'             => array(
					'field'   => 'site_view',
					'context' => 'site.view',
					'view'    => 'code'
				),
				'add_site_view_html'        => array(
					'field'   => 'site_view_html',
					'context' => 'site.view.html',
					'view'    => 'code'
				),
				'add_site_view_controller'  => array(
					'field'   => 'site_view_controller',
					'context' => 'site.view.controller',
					'view'    => 'code'
				),
				'add_site_views_model'      => array(
					'field'   => 'site_views_model',
					'context' => 'site.views.model',
					'view'    => 'code'
				),
				'add_site_views'            => array(
					'field'   => 'site_views',
					'context' => 'site.views',
					'view'    => 'code'
				),
				'add_site_views_html'       => array(
					'field'   => 'site_views_html',
					'context' => 'site.views.html',
					'view'    => 'code'
				),
				'add_site_views_controller' => array(
					'field'   => 'site_views_controller',
					'context' => 'site.views.controller',
					'view'    => 'code'
				),
				'add_custom_admin_view_model'       => array(
					'field'   => 'custom_admin_view_model',
					'context' => 'custom.admin.view.model',
					'view'    => 'code'
				),
				'add_custom_admin_view'             => array(
					'field'   => 'custom_admin_view',
					'context' => 'custom.admin.view',
					'view'    => 'code'
				),
				'add_custom_admin_view_html'        => array(
					'field'   => 'custom_admin_view_html',
					'context' => 'custom.admin.view.html',
					'view'    => 'code'
				),
				'add_custom_admin_view_controller'  => array(
					'field'   => 'custom_admin_view_controller',
					'context' => 'custom.admin.view.controller',
					'view'    => 'code'
				),
				'add_custom_admin_views_model'      => array(
					'field'   => 'custom_admin_views_model',
					'context' => 'custom.admin.views.model',
					'view'    => 'code'
				),
				'add_custom_admin_views'            => array(
					'field'   => 'custom_admin_views',
					'context' => 'custom.admin.views',
					'view'    => 'code'
				),
				'add_custom_admin_views_html'       => array(
					'field'   => 'custom_admin_views_html',
					'context' => 'custom.admin.views.html',
					'view'    => 'code'
				),
				'add_custom_admin_views_controller' => array(
					'field'   => 'custom_admin_views_controller',
					'context' => 'custom.admin.views.controller',
					'view'    => 'code'
				),
				'add_ajax_model'                    => array(
					'field'   => 'ajax_model',
					'context' => 'ajax.admin.model',
					'view'    => 'ajax'
				)
			),
		'component_dashboard_headers' =>
			array(
				'add_dashboard_model'      => array(
					'field'   => 'dashboard_model',
					'context' => 'dashboard.model',
					'view'    => 'dashboard'
				),
				'add_dashboard_view'       => array(
					'field'   => 'dashboard_view',
					'context' => 'dashboard.view',
					'view'    => 'dashboard'
				),
				'add_dashboard_view_html'  => array(
					'field'   => 'dashboard_view_html',
					'context' => 'dashboard.view.html',
					'view'    => 'dashboard'
				),
				'add_dashboard_controller' => array(
					'field'   => 'dashboard_controller',
					'context' => 'dashboard.controller',
					'view'    => 'dashboard'
				),
				'add_ajax_model'           => array(
					'field'   => 'ajax_model',
					'context' => 'ajax.admin.model',
					'view'    => 'ajax'
				)
			),
		'joomla_component_headers'    =>
			array(
				'add_admin_component' => array(
					'field'   => 'admin_component',
					'context' => 'admin.component',
					'view'    => 'admin'
				),
				'add_site_component'  => array(
					'field'   => 'site_component',
					'context' => 'site.component',
					'view'    => 'site'
				),
				'add_admin_helper'    => array(
					'field'   => 'admin_helper',
					'context' => 'admin.helper',
					'view'    => 'admin'
				),
				'add_site_helper'     => array(
					'field'   => 'site_helper',
					'context' => 'site.helper',
					'view'    => 'site'
				)
			)
	);

	/**
	 * Event Triggered in the compiler [on Before Model View Data]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onBeforeModelViewData(&$view)
	{
		// check that the params are set
		if (isset($view->params))
		{
			// add the headers for the Admin Views
			$this->setHeaders($view->params, $view, 'admin_view_headers');
		}
	}

	/**
	 * Event Triggered in the compiler [on Before Model Custom View Data]
	 *
	 * @return  void
	 *
	 * @since   1.0.2
	 */
	public function jcb_ce_onBeforeModelCustomViewData(&$view, &$id, &$table)
	{
		// check that the params are set
		if (isset($view->params))
		{
			// add the headers for the Site Views
			$this->setHeaders($view->params, $view, 'site_view_headers');
			// add the headers for the Custom Admin Views
			$this->setHeaders($view->params, $view, 'custom_admin_view_headers');
		}
	}

	/**
	 * Event Triggered in the compiler [on Before Model Dynamic Get Data]
	 *
	 * @return  void
	 *
	 * @since   1.0.10
	 */
	public function jcb_ce_onBeforeModelDynamicGetData(&$dynamicGet, &$id, &$code, &$area)
	{
		// check that the params are set
		if (isset($dynamicGet->params))
		{
			// add the headers for the Site Views
			$this->setDynamicHeaders($dynamicGet->params, $code, 'dynamic_get_headers');
		}
	}

	/**
	 * Event Triggered in the compiler [on Before Model Component Data]
	 *
	 * @return  void
	 *
	 * @since   1.0.4
	 */
	public function jcb_ce_onBeforeModelComponentData(&$component)
	{
		// check that the params are set
		if (isset($component->params))
		{
			// add the headers for the Joomla Component
			$this->setHeaders($component->params, $component, 'joomla_component_headers');
		}
		// check that the dashboard params are set
		if (isset($component->dashboard_params))
		{
			// add the headers for the Component Dashboard
			$this->setHeaders($component->dashboard_params, $component, 'component_dashboard_headers');
		}
	}

	/**
	 * Event Triggered in the compiler [on set Class Header]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_setClassHeader(&$event_context, &$view_name, &$headers)
	{
		if ($this->loadHeaders && isset($this->activeHeaders[$view_name])
			&& isset($this->activeHeaders[$view_name][$event_context])
			&& is_array($this->activeHeaders[$view_name][$event_context]))
		{
			// work with the header values as keys
			$_headers = array_flip($headers);

			// new headers
			$new = $this->activeHeaders[$view_name][$event_context];

			// now add the new headers
			foreach ($new as $n => $header)
			{
				// if an empty line is found just skip it 
				// we check if this header is already set
				if (empty($header) || isset($_headers[$header]))
				{
					continue;
				}
				$headers[] = $header;
			}
		}
	}

	/**
	 * Event Triggered in the compiler [on Before Get Component Data]
	 *
	 * @return  void
	 *
	 * @since   1.0.6
	 */
	public function jcb_ce_onBeforeGetComponentData()
	{
		// get placeholders from the compiler
		$this->placeholders = Factory::_('Component.Placeholder')->get();
	}

	/**
	 * Event Triggered in the compiler [on After Get Component Data]
	 *
	 * @return  void
	 *
	 * @since   1.0.6
	 */
	public function jcb_ce_onAfterGetComponentData()
	{
		// add the powers to the component
		if (ArrayHelper::check($this->linkedPowers, true))
		{
			Factory::_('Power')->load($this->linkedPowers);
		}
	}

	/**
	 * set the headers
	 *
	 * @return  void
	 *
	 * @since   1.0.8
	 */
	protected function setHeaders(&$params, &$obj, $key)
	{
		// add the headers
		$params = (JsonHelper::check($params)) ? json_decode($params, true) : $params;
		// make sure we have the keys values in the params area
		if (ArrayHelper::check($params) && isset($params[$key])
			&& ArrayHelper::check($params[$key]))
		{
			foreach ($this->targets[$key] as $target => $event)
			{
				if (isset($params[$key][$target])
					&& $params[$key][$target] == 1)
				{
					// get the header string if set
					$this->getHeaders(
						$params[$key],
						$event,
						$this->getViewName(
							$obj,
							$event['view']
						)
					);
				}
			}
		}
	}

	/**
	 * set the dynamic get headers
	 *
	 * @return  void
	 *
	 * @since   1.0.10
	 */
	protected function setDynamicHeaders($params, $code, $key)
	{
		// add the headers
		$params = (JsonHelper::check($params)) ? json_decode($params, true) : $params;
		// make sure we have the keys values in the params area
		if (ArrayHelper::check($params) && isset($params[$key])
			&& ArrayHelper::check($params[$key]))
		{
			foreach ($this->targets[$key] as $target => $event)
			{
				if (isset($params[$key][$target])
					&& $params[$key][$target] == 1)
				{
					// get the header string if set
					$this->getHeaders(
						$params[$key],
						$event,
						$code
					);
				}
			}
		}
	}

	/**
	 * get the headers
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function getHeaders(&$params, &$get, $view_name)
	{
		// we first check if the value is set
		if (isset($params[$get['field']]) || isset($params['power_' . $get['field']]))
		{
			// start little headers bucket
			$headers = [];

			// load the headers if power
			if (isset($params['power_' . $get['field']]) && ArrayHelper::check($params['power_' . $get['field']], true)
				&& ($powers = $this->getPowers($params['power_' . $get['field']])) !== null)
			{
				foreach ($powers as $power)
				{
					$power = trim($power);
					$headers[$power] = $power;
				}
			}

			// load the headers if text
			if (isset($params[$get['field']]) && StringHelper::check($params[$get['field']]))
			{
				if (($_headers = explode(PHP_EOL, $params[$get['field']])))
				{
					foreach ($_headers as $header)
					{
						$header = trim($header);
						if (empty($header))
						{
							continue;
						}
						$headers[$header] = $header;
					}
				}
			}

			// check if we found some header values
			if (ArrayHelper::check($headers, true))
			{
				// activate the load of the headers
				$this->loadHeaders = true;
				// check if this active header is already set
				if (!isset($this->activeHeaders[$view_name][$get['context']]))
				{
					// start the active header
					$this->activeHeaders[$view_name][$get['context']] = [];
				}
				// load the found headers and avoid adding the same header twice
				foreach ($headers as $header)
				{
					$header = trim($header);
					$this->activeHeaders[$view_name][$get['context']][$header] = $header;
				}
			}
		}
	}

	/**
	 * get the view name
	 *
	 * @return  string
	 *
	 * @since   1.0.8
	 */
	protected function getViewName(&$view, &$get)
	{
		if ($get === 'site' || $get === 'admin' || $get === 'ajax' || $get === 'dashboard')
		{
			// static key name
			return $get;
		}
		elseif (isset($view->{$get}))
		{
			return StringHelper::safe(
				$view->{$get}
			);
		}
		return '_error';
	}

	/**
	 * get the powers header use strings
	 *
	 * @return  array|null
	 *
	 * @since   1.0.6
	 */
	protected function getPowers($rows): ?array
	{
		// load the active powers
		$powers = array_filter(
			// get the power namespace
			array_map(function ($row) {
				if (($power = GuidHelper::item($row['power'], 'power', ['a.guid', 'a.namespace'])) !== null)
				{
						$power->build = (int) $row['build'];
						$power->as = (string) $row['as'];

						return $power;
				}
				elseif (Factory::_('Power.Remote.Get')->item($row['power'],  ['remote']))
				{
					if (($power = GuidHelper::item($row['power'], 'power', ['a.guid', 'a.namespace'])) !== null)
					{
						$power->build = (int) $row['build'];
						$power->as = (string) $row['as'];

						return $power;
					}
				}
				return false;
			}, $rows),
			// check that we have valid powers
			function ($row) {
				return is_object($row) && isset($row->guid);
			}
		);
		// add to active powers
		if (ArrayHelper::check($powers))
		{
			// convert the dots to namespace
			return array_map(function ($power) {
				// add to compiler (to build)
				if ($power->build != 6)
				{
					// secure that always will remain always even if only set that way once
					if (empty($this->linkedPowers[$power->guid]) || $power->build == 1)
					{
						$this->linkedPowers[$power->guid] = $power->build;
					}
				}
				// build the namespace
				$namespace = NamespaceHelper::safe(
					str_replace(
						array_keys($this->placeholders),
						array_values($this->placeholders),
						str_replace('.', '\\', $power->namespace)
					)
				);
				// check if it has an AS option
				if (StringHelper::check($power->as) && $power->as !== 'default')
				{
					return 'use ' . $namespace . ' as ' . $power->as . ';';
				}
				return 'use ' . $namespace . ';';
			}, $powers);
		}

		return null;
	}

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   5.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'jcb_ce_onBeforeModelViewData' => 'jcb_ce_onBeforeModelViewData',
			'jcb_ce_onBeforeModelCustomViewData' => 'jcb_ce_onBeforeModelCustomViewData',
			'jcb_ce_onBeforeModelDynamicGetData' => 'jcb_ce_onBeforeModelDynamicGetData',
			'jcb_ce_onBeforeModelComponentData' => 'jcb_ce_onBeforeModelComponentData',
			'jcb_ce_setClassHeader' => 'jcb_ce_setClassHeader',
			'jcb_ce_onBeforeGetComponentData' => 'jcb_ce_onBeforeGetComponentData',
			'jcb_ce_onAfterGetComponentData' => 'jcb_ce_onAfterGetComponentData'
		];
	}
}
