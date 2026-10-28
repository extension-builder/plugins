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
namespace VDM\Plugin\Extension\ComponentbuilderPrivacyCompiler\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Componentbuilder\Compiler\Factory as CompilerFactory;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Plugin\PluginHelper;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Extension - Componentbuilder Privacy Compiler plugin.
 *
 * @package   ComponentbuilderPrivacyCompiler
 * @since     6.0.0
 */
final class ComponentbuilderPrivacyCompiler extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected  $autoloadLanguage = true;

	/**
	 * The language string builder
	 * 
	 * @var     array
	 */
	protected $languageArray = [];

	/**
	 * Global switch to see if component have need of privacy plugin to be loaded.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected  $loadPrivacy = false;

	/**
	 * The Views Linked to Joomla Users
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected  $activeViews = [];

	/**
	 * The Views permission fields
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected  $permissionFields = [];

	/**
	 * Event Triggered in the compiler [on Before Model View Data]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onBeforeModelViewData(&$view)
	{
		// add the privacy
		$view->params = (isset($view->params) && JsonHelper::check($view->params)) ? json_decode($view->params, true) : $view->params;
		if (ArrayHelper::check($view->params) && isset($view->params['privacy']) && ArrayHelper::check($view->params['privacy']) && 
			isset($view->params['privacy']['activate']) && $view->params['privacy']['activate'] == 1)
		{
			// activate the load of the privacy plugin
			$this->loadPrivacy = true;
			// load the admin view details
			$this->activeViews[$view->id] = $view;
			// add permissions
			$view->addpermissions = (isset($view->addpermissions) && JsonHelper::check($view->addpermissions)) ? json_decode($view->addpermissions, true) : null;
			if (ArrayHelper::check($view->addpermissions))
			{
				$view->addpermissions = array_values($view->addpermissions);
				// add the new permissions
				$view->addpermissions[] = [
					'action' => 'view.privacy.delete',
					'implementation' => 3,
					'title' => $view->name_list . ' Privacy Delete',
					'description' => ' Allows the users in this group to remove their personal data in ' . $view->name_list . ' via the Joomla privacy suite.'
				];
				$view->addpermissions[] = [
					'action' => 'view.privacy.access',
					'implementation' => 3,
					'title' => $view->name_list . ' Privacy Access',
					'description' => ' Allows the users in this group to access their personal data in ' . $view->name_list . ' via the Joomla privacy suite.'
				];
				// convert back to json
				$view->addpermissions = json_encode($view->addpermissions, JSON_FORCE_OBJECT);
			}
			// add placeholders to view if not already set
			if (!isset($this->activeViews[$view->id]->placeholders))
			{
				$this->activeViews[$view->id]->placeholders = CompilerFactory::_('Placeholder')->active;
			}
		}
	}

	/**
	 * Event Triggered in the compiler [on After Get]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onAfterGet()
	{
		// check if this component needs a privacy plugin loaded
		if ($this->loadPrivacy)
		{
			$plugin = PluginHelper::getPlugin('content', 'componentbuilderprivacytabs');
			// check if this is json
			if (isset($plugin->params) && JsonHelper::check($plugin->params))
			{
				// Convert the params field to an array.
				$registry = new Registry;
				$registry->loadString($plugin->params);
				$plugin->params = $registry->toArray();
			}
			// now get the plugin ID if set
			if (isset($plugin->params['plugin']) && $plugin->params['plugin'] > 0)
			{
				// if linked it will only load it once
				CompilerFactory::_('Joomlaplugin.Data')->set($plugin->params['plugin']);
			}
			else
			{
				$this->getApplication()->enqueueMessage(Text::_('PLG_EXTENSION_COMPONENTBUILDERPRIVACYCOMPILER_YOU_DO_NOT_HAVE_A_GLOBAL_PRIVACY_PLUGIN_SETUP_SO_THE_INTEGRATION_WITH_JOOMLA_PRIVACY_SUITE_COULD_NOT_BE_BUILD'), 'Warning');
				$this->loadPrivacy= false;
			}
		}
	}

	/**
	 * Event Triggered in the compiler [on Before Update Files]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onBeforeUpdateFiles($compiler)
	{
		// check if privacy is to be loaded
		if ($this->loadPrivacy && ArrayHelper::check($this->activeViews))
		{
			// get compiler defaults
			$strictFieldExportPermissions = CompilerFactory::_('Config')->get('permission_strict_per_field', false);
			$exportTextOnly = CompilerFactory::_('Config')->get('export_text_only', 1);

			// load the getPrivacyExport functions
			foreach ($this->activeViews as $id => &$view)
			{
				// set permissions based on view
				if (isset($view->params['privacy']['permissions']))
				{
					CompilerFactory::_('Config')->set('permission_strict_per_field', (bool) $view->params['privacy']['permissions']);
				}

				// allow text only export
				CompilerFactory::_('Config')->set('export_text_only', 1);

				// set view list name
				$viewName_list = StringHelper::safe($view->name_list);
				// set view single name
				$viewName_single = StringHelper::safe($view->name_single);
				// load the function
				CompilerFactory::_('Compiler.Builder.Content.Multi')->add($viewName_list . '|MODELEXPORTMETHOD',
					$compiler->setGetItemsModelMethod(
						$viewName_single,
						$viewName_list,
						[
							'functionName' => 'getPrivacyExport',
							'docDesc' => 'Method to get data during an export request.',
							'type' => 'privacy'
						]
					)
				);
				// get the permissions building values for later if needed
				if (CompilerFactory::_('Config')->get('permission_strict_per_field', false) &&
					isset($compiler->permissionFields[$viewName_single]) &&
					ArrayHelper::check($compiler->permissionFields[$viewName_single]))
				{
					$this->permissionFields[$viewName_single] = $compiler->permissionFields[$viewName_single];
				}
			}

			// set compiler defaults
			CompilerFactory::_('Config')->set('permission_strict_per_field', $strictFieldExportPermissions);
			CompilerFactory::_('Config')->set('export_text_only', $exportTextOnly);

			// add helper classes
			$helper_strings = ['CUSTOM_HELPER_SCRIPT', 'SITE_CUSTOM_HELPER_SCRIPT', 'BOTH_CUSTOM_HELPER_SCRIPT'];
			$privacy_events = [
				'PrivacyCanRemoveData' => true,
				'PrivacyExportRequest' => true,
				'PrivacyRemoveData' => true
			];

			foreach ($helper_strings as $helper)
			{
				if (($helper_content = CompilerFactory::_('Compiler.Builder.Content.One')->get($helper)) !== null &&
					StringHelper::check($helper_content))
				{
					foreach ($privacy_events as $privacy_event => &$add)
					{
						// check if the even is overwriten
						if (strpos($helper_content, 'public static function on' . $privacy_event . '(') !== false)
						{
							$add = false;
						}
					}
				}
			}

			// add the events still needed
			CompilerFactory::_('Compiler.Builder.Content.One')->add('BOTH_CUSTOM_HELPER_SCRIPT',
				CompilerFactory::_('Placeholder')->update_($this->getHelperMethod($privacy_events))
			);
		}
	}

	/**
	 * Event Triggered in the compiler [on Before Set Lang File Data]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onBeforeSetLangFileData()
	{
		if (ArrayHelper::check($this->languageArray))
		{
			foreach($this->languageArray as $key => $string)
			{
				CompilerFactory::_('Language')->set('site', $key, $string);
			}
		}
	}

	/**
	 * Select native APIs for the generated Joomla 6 target only.
	 *
	 * @return  bool
	 *
	 * @since   6.2.0
	 */
	protected function isJoomlaSixTarget(): bool
	{
		return (int) CompilerFactory::_('Config')->get('joomla_version', 3) === 6;
	}

	/**
	 * get the Helper methods needed to integrate with Joomla Privacy Suite
	 * 
	 * @param   string   $helperMethods  The helper methods string
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function getHelperMethod(&$events)
	{
		$methods = '';
		foreach ($events as $event => $add)
		{
			// check if the even should be added
			if ($add)
			{
				// add the event
				$this->{'set'.$event}($methods);
			}
		}
		// only add header if there was events added
		if (StringHelper::check($methods))
		{
			$methods = PHP_EOL . PHP_EOL . Indent::_(1) . "//" . Line::_(__Line__, __Class__) . " <<<=== Privacy integration with Joomla Privacy suite ===>>>" . PHP_EOL . $methods;
		}

		return $methods;
	}

	/**
	 * Set Privacy Can Remove Data
	 * 
	 * @param   string   $methods  The methods string
	 * 
	 * @return  void
	 * 
	 */
	protected function setPrivacyCanRemoveData(&$methods)
	{
		$methods .= PHP_EOL . Indent::_(1) . "/**";
		$methods .= PHP_EOL . Indent::_(1) . " * Performs validation to determine if the data associated with a remove information request can be processed";
		$methods .= PHP_EOL . Indent::_(1) . " *";
		$methods .= PHP_EOL . Indent::_(1) . " * @param   PrivacyPlugin  \$plugin  The plugin being processed";
		$methods .= PHP_EOL . Indent::_(1) . " * @param   PrivacyRemovalStatus  \$status  The status being set";
		$methods .= PHP_EOL . Indent::_(1) . " * @param   PrivacyTableRequest  \$request  The request record being processed";
		$methods .= PHP_EOL . Indent::_(1) . " * @param   Joomla__"."_effdaf6d_2275_425d_9f52_d4952e564d34___Power                \$user     The user account associated with this request if available";
		$methods .= PHP_EOL . Indent::_(1) . " *";
		$methods .= PHP_EOL . Indent::_(1) . " * @return  PrivacyRemovalStatus";
		$methods .= PHP_EOL . Indent::_(1) . " */";
		$methods .= PHP_EOL . Indent::_(1) . "public static function onPrivacyCanRemoveData(&\$plugin, &\$status, &\$request, &\$user)";
		$methods .= PHP_EOL . Indent::_(1) . "{";
		$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Bucket to get all reasons why removal not allowed";
		$methods .= PHP_EOL . Indent::_(2) . "\$reasons = array();";
		foreach ($this->activeViews as $view)
		{
			// set view single name
			$viewName_single = StringHelper::safe($view->name_single);

			// load the canDo from getActions helper method
			$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Check if user has permission to delete " . $view->name_list;
			// set the if statement based on the permission builder
			$methods .= PHP_EOL . Indent::_(2) . "if (!\$user->authorise('"
				. CompilerFactory::_('Compiler.Creator.Permission')->getAction($viewName_single, 'core.delete')
				. "', 'com_" . Placefix::_("component") . "') && !\$user->authorise('"
				. CompilerFactory::_('Compiler.Creator.Permission')->getAction($viewName_single, 'core.privacy.delete')
				. "', 'com_" . Placefix::_("component") . "'))";
			$methods .= PHP_EOL . Indent::_(2) . "{";
			// set language key
			$lang_key = $view->placeholders[Placefix::_("LANG_PREFIX")] . '_PRIVACY_CANT_REMOVE_' . $view->placeholders[Placefix::_("VIEWS")];
			// set language string
			$this->languageArray[$lang_key] = "You do not have permission to remove/delete ". $view->name_list . ".";
			$methods .= PHP_EOL . Indent::_(3) . "\$reasons[] = Joomla__" . "_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('" . $lang_key . "');";
			$methods .= PHP_EOL . Indent::_(2) . "}";
			// set language key
			$lang_key = $view->placeholders[Placefix::_("LANG_PREFIX")] . '_PRIVACY_CANT_REMOVE_CONTACT_SUPPORT';
		}
		$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Check if any reasons were found not to allow removal";
		$methods .= PHP_EOL . Indent::_(2) . "if (Super__" . "_0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$reasons))";
		$methods .= PHP_EOL . Indent::_(2) . "{";
		$methods .= PHP_EOL . Indent::_(3) . "\$status->canRemove = false;";
		// set language string
		$this->languageArray[$lang_key] = 'Please contact support for more details.';
		$methods .= PHP_EOL . Indent::_(3) . "\$status->reason = implode(' ' . PHP_EOL, \$reasons) . ' ' . PHP_EOL . Joomla__" . "_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('" . $lang_key . "');";
		$methods .= PHP_EOL . Indent::_(2) . "}";
		$methods .= PHP_EOL . Indent::_(2) . "return \$status;";
		$methods .= PHP_EOL . Indent::_(1) . "}" . PHP_EOL;
	}

	/**
	 * Set Privacy Export Request
	 * 
	 * @param   string   $methods  The methods string
	 * 
	 * @return  void
	 * 
	 */
	protected function setPrivacyExportRequest(&$methods)
	{
		$methods .= PHP_EOL . Indent::_(1) . "/**";
		$methods .= PHP_EOL . Indent::_(1) . " * Processes an export request for Joomla core user data";
		$methods .= PHP_EOL . Indent::_(1) . " *";
		$methods .= PHP_EOL . Indent::_(1) . " * @param   PrivacyPlugin  \$plugin  The plugin being processed";
		$methods .= PHP_EOL . Indent::_(1) . " * @param   DomainArray  \$domains  The array of domains";
		$methods .= PHP_EOL . Indent::_(1) . " * @param   PrivacyTableRequest  \$request  The request record being processed";
		$methods .= PHP_EOL . Indent::_(1) . " * @param   Joomla__"."_effdaf6d_2275_425d_9f52_d4952e564d34___Power                \$user     The user account associated with this request if available";
		$methods .= PHP_EOL . Indent::_(1) . " *";
		$methods .= PHP_EOL . Indent::_(1) . " * @return  " . ($this->isJoomlaSixTarget() ? 'Joomla__' . '_f5e0753c_c8d9_4965_a606_9c45b37a6857___Power' : 'PrivacyExportDomain') . '[]';
		$methods .= PHP_EOL . Indent::_(1) . " */";
		$methods .= PHP_EOL . Indent::_(1) . "public static function onPrivacyExportRequest(&\$plugin, &\$domains, &\$request, &\$user)";
		$methods .= PHP_EOL . Indent::_(1) . "{";
		foreach ($this->activeViews as $view)
		{
			// set view list name
			$viewName_list = StringHelper::safe($view->name_list);
			// set view single name
			$viewName_single = StringHelper::safe($view->name_single);
			// load the canDo from getActions helper method
			$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Check if user has permission to access " . $view->name_list;
			// set the if statement based on the permission builder
			$methods .= PHP_EOL . Indent::_(2) . "if (\$user->authorise('"
				. CompilerFactory::_('Compiler.Creator.Permission')->getGlobal($viewName_single, 'core.access')
				. "', 'com_" . Placefix::_("component") . "') || \$user->authorise('"
				. CompilerFactory::_('Compiler.Creator.Permission')->getGlobal($viewName_single, 'core.privacy.access')
				. "', 'com_" . Placefix::_("component") . "'))";
			$methods .= PHP_EOL . Indent::_(2) . "{";
			$methods .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Get " . $view->name_single . " domain";
			$methods .= PHP_EOL . Indent::_(3) . "\$domains[] = self::create" . ucfirst($viewName_list) . "Domain(\$plugin, \$user);";
			$methods .= PHP_EOL . Indent::_(2) . "}";
		}
		$methods .= PHP_EOL . Indent::_(2) . "return \$domains;";
		$methods .= PHP_EOL . Indent::_(1) . "}" . PHP_EOL;

		foreach ($this->activeViews as $view)
		{
			// set view list name
			$viewName_list = StringHelper::safe($view->name_list);
			// set view single name
			$viewName_single = StringHelper::safe($view->name_single);

			$methods .= PHP_EOL . Indent::_(1) . "/**";
			$methods .= PHP_EOL . Indent::_(1) . " * Create the domain for the " . $view->name_single;
			$methods .= PHP_EOL . Indent::_(1) . " *";
			$methods .= PHP_EOL . Indent::_(1) . " * @param   Joomla__"."_11accf54_fcb9_4ba0_a645_7c80ce14e224___Power  \$user  The Joomla__"."_11accf54_fcb9_4ba0_a645_7c80ce14e224___Power object to process";
			$methods .= PHP_EOL . Indent::_(1) . " *";
			$methods .= PHP_EOL . Indent::_(1) . " * @return  " . ($this->isJoomlaSixTarget() ? 'Joomla__' . '_f5e0753c_c8d9_4965_a606_9c45b37a6857___Power' : 'PrivacyExportDomain');
			$methods .= PHP_EOL . Indent::_(1) . " */";
			$methods .= PHP_EOL . Indent::_(1) . "protected static function create" . ucfirst($viewName_list) . "Domain(&\$plugin, &\$user)";
			$methods .= PHP_EOL . Indent::_(1) . "{";
			$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " create " . $view->name_list . " domain";
			$methods .= PHP_EOL . Indent::_(2) . "\$domain = self::createDomain('" . $viewName_single . "', '" . Placefix::_("component") . "_" . $viewName_single . "_data');";
			$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " get database object";
			$methods .= PHP_EOL . Indent::_(2) . "\$db = " . ($this->isJoomlaSixTarget() ? 'Joomla__' . '_39403062_84fb_46e0_bac4_0023f766e827___Power::getContainer()->get(Joomla__' . '_7bd29d76_73c9_4c07_a5da_4f7a32aff78f___Power::class)' : 'Factory::getDbo()') . ';';
			$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " get all item ids of " . $view->name_list . " that belong to this user";
			$methods .= PHP_EOL . Indent::_(2) . "\$query = \$db->" . ($this->isJoomlaSixTarget() ? 'createQuery()' : 'getQuery(true)');
			$methods .= PHP_EOL . Indent::_(3) . "->select('id')";
			$methods .= PHP_EOL . Indent::_(3) . "->from(\$db->quoteName('#__" . Placefix::_('component') . '_' . $viewName_single . "'));";
			// get via custom script
			if (isset($view->params['privacy']['user_link']) && $view->params['privacy']['user_link'] == 3)
			{
				$methods .= PHP_EOL . str_replace(array_keys($view->placeholders), array_values($view->placeholders), $view->params['privacy']['custom_link']);
			}
			// just another field
			elseif (isset($view->params['privacy']['user_link']) && $view->params['privacy']['user_link'] == 2 && isset($view->params['privacy']['other_user_field']))
			{
				// get the field name
				if (($field_name = $this->getFieldName($view->fields, $view->params['privacy']['other_user_field'])) !== false)
				{
					$methods .= PHP_EOL . Indent::_(2) . "\$query->where(\$db->quoteName('" . $field_name . "') . ' = ' . \$db->quote(\$user->id));";
				}
				else
				{
					// give a warning message (TODO)

					// stop any from loading
					$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " ==== ERROR ===== ERROR ====== (field name not found)";
					$methods .= PHP_EOL . Indent::_(2) . "\$query->where(\$db->quoteName('id') . ' = -2'; //" . Line::_(__Line__, __Class__) . " <-- this will never return any value. Check your [other user field] selected in the admin view privacy tab.";
				}
			}
			// get based on created by
			else
			{
				$methods .= PHP_EOL . Indent::_(2) . "\$query->where(\$db->quoteName('created_by') . ' = ' . \$db->quote(\$user->id));";
			}
			$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " get all items for the " . $view->name_list . " domain";
			$methods .= PHP_EOL . Indent::_(2) . "\$pks = \$db->setQuery(\$query)->loadColumn();";
			$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " get the " . $view->name_list . " model";
			$methods .= PHP_EOL . Indent::_(2) . "\$model = self::getModel('" . $viewName_list . "');";
			$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Get all item details of " . $view->name_list . " that belong to this user";
			$methods .= PHP_EOL . Indent::_(2) . "\$items = \$model->getPrivacyExport(\$pks, \$user);";
			$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " check if we have items since permissions could block the request";
			$methods .= PHP_EOL . Indent::_(2) . "if (Super__" . "_0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$items))";
			$methods .= PHP_EOL . Indent::_(2) . "{";
			$methods .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Remove " . $view->name_single . " default columns";
			$methods .= PHP_EOL . Indent::_(3) . "foreach (array('params', 'asset_id', 'checked_out', 'checked_out_time', 'created', 'created_by', 'modified', 'modified_by', 'published', 'ordering', 'access', 'version', 'hits') as \$column)";
			$methods .= PHP_EOL . Indent::_(3) . "{";
			$methods .= PHP_EOL . Indent::_(4) . "\$items = ArrayHelper::dropColumn(\$items, \$column);";
			$methods .= PHP_EOL . Indent::_(3) . "}";
			$methods .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " load the items into the domain object";
			$methods .= PHP_EOL . Indent::_(3) . "foreach (\$items as \$item)";
			$methods .= PHP_EOL . Indent::_(3) . "{";
			$methods .= PHP_EOL . Indent::_(4) . "\$domain->addItem(self::createItemFromArray(\$item, \$item['id']));";
			$methods .= PHP_EOL . Indent::_(3) . "}";
			$methods .= PHP_EOL . Indent::_(2) . "}";
			$methods .= PHP_EOL . Indent::_(2) . "return \$domain;";
			$methods .= PHP_EOL . Indent::_(1) . "}" . PHP_EOL;
		}
		// we must add these helper methods
		$methods .= PHP_EOL . Indent::_(1) . "/**";
		$methods .= PHP_EOL . Indent::_(1) . " * Create a new domain object";
		$methods .= PHP_EOL . Indent::_(1) . " *";
		$methods .= PHP_EOL . Indent::_(1) . " * @param   string  \$name         The domain's name";
		$methods .= PHP_EOL . Indent::_(1) . " * @param   string  \$description  The domain's description";
		$methods .= PHP_EOL . Indent::_(1) . " *";
		$methods .= PHP_EOL . Indent::_(1) . " * @return  " . ($this->isJoomlaSixTarget() ? 'Joomla__' . '_f5e0753c_c8d9_4965_a606_9c45b37a6857___Power' : 'PrivacyExportDomain');
		$methods .= PHP_EOL . Indent::_(1) . " *";
		$methods .= PHP_EOL . Indent::_(1) . " * @since   3.9.0";
		$methods .= PHP_EOL . Indent::_(1) . " */";
		$methods .= PHP_EOL . Indent::_(1) . "protected static function createDomain(\$name, \$description = '')";
		$methods .= PHP_EOL . Indent::_(1) . "{";
		$methods .= PHP_EOL . Indent::_(2) . "\$domain              = new " . ($this->isJoomlaSixTarget() ? 'Joomla__' . '_f5e0753c_c8d9_4965_a606_9c45b37a6857___Power' : 'PrivacyExportDomain') . ';';
		$methods .= PHP_EOL . Indent::_(2) . "\$domain->name        = \$name;";
		$methods .= PHP_EOL . Indent::_(2) . "\$domain->description = \$description;";

		$methods .= PHP_EOL . PHP_EOL . Indent::_(2) . "return \$domain;";
		$methods .= PHP_EOL . Indent::_(1) . "}";

		$methods .= PHP_EOL . PHP_EOL . Indent::_(1) . "/**";
		$methods .= PHP_EOL . Indent::_(1) . " * Create an item object for an array";
		$methods .= PHP_EOL . Indent::_(1) . " *";
		$methods .= PHP_EOL . Indent::_(1) . " * @param   array         \$data    The array data to convert";
		$methods .= PHP_EOL . Indent::_(1) . " * @param   integer|null  \$itemId  The ID of this item";
		$methods .= PHP_EOL . Indent::_(1) . " *";
		$methods .= PHP_EOL . Indent::_(1) . " * @return  " . ($this->isJoomlaSixTarget() ? '\\Joomla\\Component\\Privacy\\Administrator\\Export\\Item' : 'PrivacyExportItem');
		$methods .= PHP_EOL . Indent::_(1) . " *";
		$methods .= PHP_EOL . Indent::_(1) . " * @since   3.9.0";
		$methods .= PHP_EOL . Indent::_(1) . " */";
		$methods .= PHP_EOL . Indent::_(1) . "protected static function createItemFromArray(array \$data, \$itemId = null)";
		$methods .= PHP_EOL . Indent::_(1) . "{";
		$methods .= PHP_EOL . Indent::_(2) . "\$item = new " . ($this->isJoomlaSixTarget() ? '\\Joomla\\Component\\Privacy\\Administrator\\Export\\Item' : 'PrivacyExportItem') . ';';
		$methods .= PHP_EOL . Indent::_(2) . "\$item->id = \$itemId;";

		$methods .= PHP_EOL . PHP_EOL . Indent::_(2) . "foreach (\$data as \$key => \$value)";
		$methods .= PHP_EOL . Indent::_(2) . "{";
		$methods .= PHP_EOL . Indent::_(3) . "if (is_object(\$value))";
		$methods .= PHP_EOL . Indent::_(3) . "{";
		$methods .= PHP_EOL . Indent::_(4) . "\$value = (array) \$value;";
		$methods .= PHP_EOL . Indent::_(3) . "}";

		$methods .= PHP_EOL . PHP_EOL . Indent::_(3) . "if (is_array(\$value))";
		$methods .= PHP_EOL . Indent::_(3) . "{";
		$methods .= PHP_EOL . Indent::_(4) . "\$value = print_r(\$value, true);";
		$methods .= PHP_EOL . Indent::_(3) . "}";

		$methods .= PHP_EOL . PHP_EOL . Indent::_(3) . "\$field        = new " . ($this->isJoomlaSixTarget() ? '\\Joomla\\Component\\Privacy\\Administrator\\Export\\Field' : 'PrivacyExportField') . ';';
		$methods .= PHP_EOL . Indent::_(3) . "\$field->name  = \$key;";
		$methods .= PHP_EOL . Indent::_(3) . "\$field->value = \$value;";

		$methods .= PHP_EOL . PHP_EOL . Indent::_(3) . "\$item->addField(\$field);";
		$methods .= PHP_EOL . Indent::_(2) . "}";

		$methods .= PHP_EOL . PHP_EOL . Indent::_(2) . "return \$item;";
		$methods .= PHP_EOL . Indent::_(1) . "}" . PHP_EOL;
	}

	/**
	 * get the field name
	 * 
	 * @param   array   $fields  The fields array
	 * @param   int       $id       The field id
	 * 
	 * @return  string    The field name
	 * 
	 */
	protected function getFieldName(&$fields, $id)
	{
		foreach ($fields as $field)
		{
			if ($field['field'] == $id)
			{
				return $field['base_name'];
			}
		}
		return false;
	}

	/**
	 * Set Privacy Remove Data
	 * 
	 * @param   string   $methods  The methods string
	 * 
	 * @return  void
	 * 
	 */
	protected function setPrivacyRemoveData(&$methods)
	{
		$methods .= PHP_EOL . Indent::_(1) . "/**";
		$methods .= PHP_EOL . Indent::_(1) . " * Removes the data associated with a remove information request";
		$methods .= PHP_EOL . Indent::_(1) . " *";
		$methods .= PHP_EOL . Indent::_(1) . " * @param   PrivacyTableRequest  \$request  The request record being processed";
		$methods .= PHP_EOL . Indent::_(1) . " * @param   Joomla__"."_effdaf6d_2275_425d_9f52_d4952e564d34___Power                \$user     The user account associated with this request if available";
		$methods .= PHP_EOL . Indent::_(1) . " *";
		$methods .= PHP_EOL . Indent::_(1) . " * @return  void";
		$methods .= PHP_EOL . Indent::_(1) . " */";
		$methods .= PHP_EOL . Indent::_(1) . "public static function onPrivacyRemoveData(&\$plugin, &\$request, &\$user)";
		$methods .= PHP_EOL . Indent::_(1) . "{";
		foreach ($this->activeViews as $view)
		{
			// set the anonymize switch
			$anonymize = false;
			if (isset($view->params['privacy']['anonymize']) && $view->params['privacy']['anonymize'] == 1 && isset($view->params['privacy']['anonymize_fields']) && ArrayHelper::check($view->params['privacy']['anonymize_fields'], true))
			{
				// Anonymize the data
				$anonymize = true;
			}
			// set view list name
			$viewName_list = StringHelper::safe($view->name_list);
			// set view single name
			$viewName_single = StringHelper::safe($view->name_single);
			// load the canDo from getActions helper method
			$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Check if user has permission to delet " . $view->name_list;
			// set the if statement based on the permission builder
			$methods .= PHP_EOL . Indent::_(2) . "if (\$user->authorise('"
				. CompilerFactory::_('Compiler.Creator.Permission')->getAction($viewName_single, 'core.delete')
				. "', 'com_" . Placefix::_("component") . "') || \$user->authorise('"
				. CompilerFactory::_('Compiler.Creator.Permission')->getAction($viewName_single, 'core.privacy.delete')
				. "', 'com_" . Placefix::_("component") . "'))";
			$methods .= PHP_EOL . Indent::_(2) . "{";
			// check if this is a plain delete, or it is a Anonymize 
			if ($anonymize)
			{
				// anonymize the data
				$methods .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Anonymize " . $view->name_single . " data";
				$methods .= PHP_EOL . Indent::_(3) . "self::anonymize" . ucfirst($viewName_list) . "Data(\$plugin, \$user);";
			}
			else
			{
				// just dump, delete the rows
				$methods .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Remove " . $view->name_single . " data";
				$methods .= PHP_EOL . Indent::_(3) . "self::remove" . ucfirst($viewName_list) . "Data(\$plugin, \$user);";
			}
			$methods .= PHP_EOL . Indent::_(2) . "}";
		}
		$methods .= PHP_EOL . Indent::_(1) . "}" . PHP_EOL;

		foreach ($this->activeViews as $view)
		{
			// set the anonymize switch
			$anonymize = false;
			if (isset($view->params['privacy']['anonymize']) && $view->params['privacy']['anonymize'] == 1 && isset($view->params['privacy']['anonymize_fields']) && ArrayHelper::check($view->params['privacy']['anonymize_fields'], true))
			{
				// Anonymize the data
				$anonymize = true;
			}
			// set view list name
			$viewName_list = StringHelper::safe($view->name_list);
			// set view single name
			$viewName_single = StringHelper::safe($view->name_single);

			$methods .= PHP_EOL . Indent::_(1) . "/**";

			// check if this is a plain delete, or it is a Anonymize
			if ($anonymize)
			{
				// Anonymize the data
				$methods .= PHP_EOL . Indent::_(1) . " * Anonymize the " . $view->name_single . " data";
			}
			else
			{
				// Delete the rows
				$methods .= PHP_EOL . Indent::_(1) . " * Remove the " . $view->name_single . " data";
			}

			$methods .= PHP_EOL . Indent::_(1) . " *";
			$methods .= PHP_EOL . Indent::_(1) . " * @param   Joomla__"."_11accf54_fcb9_4ba0_a645_7c80ce14e224___Power  \$user  The Joomla__"."_11accf54_fcb9_4ba0_a645_7c80ce14e224___Power object to process";
			$methods .= PHP_EOL . Indent::_(1) . " *";
			$methods .= PHP_EOL . Indent::_(1) . " * @return  void";
			$methods .= PHP_EOL . Indent::_(1) . " */";

			// check if this is a plain delete, or it is a Anonymize 
			if ($anonymize)
			{
				// Anonymize the data
				$methods .= PHP_EOL . Indent::_(1) . "protected static function anonymize" . ucfirst($viewName_list) . "Data(&\$plugin, &\$user)";
			}
			else
			{
				// Delete the rows
				$methods .= PHP_EOL . Indent::_(1) . "protected static function remove" . ucfirst($viewName_list) . "Data(&\$plugin, &\$user)";
			}

			$methods .= PHP_EOL . Indent::_(1) . "{";
			$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " get database object";
			$methods .= PHP_EOL . Indent::_(2) . "\$db = " . ($this->isJoomlaSixTarget() ? 'Joomla__' . '_39403062_84fb_46e0_bac4_0023f766e827___Power::getContainer()->get(Joomla__' . '_7bd29d76_73c9_4c07_a5da_4f7a32aff78f___Power::class)' : 'Factory::getDbo()') . ';';
			$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " get all item ids of " . $view->name_list . " that belong to this user";
			$methods .= PHP_EOL . Indent::_(2) . "\$query = \$db->" . ($this->isJoomlaSixTarget() ? 'createQuery()' : 'getQuery(true)');
			$methods .= PHP_EOL . Indent::_(3) . "->select('id')";
			$methods .= PHP_EOL . Indent::_(3) . "->from(\$db->quoteName('#__" . Placefix::_('component') . '_' . $viewName_single . "'));";
			// get via custom script
			if (isset($view->params['privacy']['user_link']) && $view->params['privacy']['user_link'] == 3)
			{
				$methods .= PHP_EOL . str_replace(array_keys($view->placeholders), array_values($view->placeholders), $view->params['privacy']['custom_link']);
			}
			// just another field
			elseif (isset($view->params['privacy']['user_link']) && $view->params['privacy']['user_link'] == 2 && isset($view->params['privacy']['other_user_field']))
			{
				// get the field name
				if (($field_name = $this->getFieldName($view->fields, $view->params['privacy']['other_user_field'])) !== false)
				{
					$methods .= PHP_EOL . Indent::_(2) . "\$query->where(\$db->quoteName('" . $field_name . "') . ' = ' . \$db->quote(\$user->id));";
				}
				else
				{
					// give a warning message (TODO)

					// stop any from loading
					$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " ==== ERROR ===== ERROR ====== (field name not found)";
					$methods .= PHP_EOL . Indent::_(2) . "\$query->where(\$db->quoteName('id') . ' = -2'; //" . Line::_(__Line__, __Class__) . " <-- this will never return any value. Check your [other user field] selected in the admin view privacy tab.";
				}
			}
			// get based on created by
			else
			{
				$methods .= PHP_EOL . Indent::_(2) . "\$query->where(\$db->quoteName('created_by') . ' = ' . \$db->quote(\$user->id));";
			}
			$methods .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " get all items for the " . $view->name_list . " table that belong to this user";
			$methods .= PHP_EOL . Indent::_(2) . "\$pks = \$db->setQuery(\$query)->loadColumn();";

			$methods .= PHP_EOL .PHP_EOL .  Indent::_(2) . "if (Super__" . "_0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$pks))";
			$methods .= PHP_EOL . Indent::_(2) . "{";
			$methods .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " get the " . $viewName_single . " model";
			$methods .= PHP_EOL . Indent::_(3) . "\$model = self::getModel('" . $viewName_single . "');";
			// check if this is a plain delete, or it is a Anonymize 
			if ($anonymize)
			{
				// build the pseudoanonymised data array
				$_data_bucket = array();
				$_random_bucket = array();
				$_permission_bucket = array();
				foreach ($view->params['privacy']['anonymize_fields'] as $row)
				{
					if (($field_name = $this->getFieldName($view->fields, $row['field'])) !== false)
					{
						if  ('RANDOM' === $row['value']) 
						{
							$_random_bucket[$field_name] = 8; // (TODO) make the size dynamic
						}
						$_data_bucket[] = PHP_EOL . Indent::_(4) . "'" . $field_name . "' => '" . $row['value'] ."'";
						$_permission_bucket[$field_name] = $field_name;
					}
				}
				// Anonymize the data
				$methods .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " this is the pseudoanonymised data array for " . $view->name_list;
				$methods .= PHP_EOL . Indent::_(3) . "\$pseudoanonymisedData = array(";
				$methods .= implode(',', $_data_bucket);
				$methods .= PHP_EOL . Indent::_(3) . ");";

				// add the permissional removal of values the user has not right to view or access
				$hasPermissional = false;
				if (isset($this->permissionFields[$viewName_single]) && ArrayHelper::check($this->permissionFields[$viewName_single]))
				{
					foreach ($this->permissionFields[$viewName_single] as $fieldName => $permission_options)
					{
						if (!$hasPermissional && isset($_permission_bucket[$fieldName]))
						{
							foreach($permission_options as $permission_option => $fieldType)
							{
								if (!$hasPermissional)
								{
									switch ($permission_option)
									{
										case 'access':
										case 'view':
										case 'edit':
											$hasPermissional = true;
										break;
									}
								}
							}
						}
					}
					// add the notes and get the global switch
					if ($hasPermissional)
					{
						$methods .= PHP_EOL . PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Get global permissional control activation. (default is inactive)";
						$methods .= PHP_EOL . Indent::_(3) . "\$strict_permission_per_field = Joomla__" . "_aeb8e463_291f_4445_9ac4_34b637c12dbd___Power::getParams('com_" . Placefix::_("component") . "')->get('strict_permission_per_field', 0);";
						$methods .= PHP_EOL . Indent::_(3) . "if(\$strict_permission_per_field)";
						$methods .= PHP_EOL . Indent::_(3) . "{";
						$methods .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__) . " remove all fields that is not permitted to be changed";
						foreach ($this->permissionFields[$viewName_single] as $fieldName => $permission_options)
						{
							if (isset($_permission_bucket[$fieldName]))
							{
								$methods .= PHP_EOL . Indent::_(4) . "if (";
								$_permission_if = array();
								foreach ($permission_options as $perm_key => $field_typrew)
								{
									$_permission_if[] = "!\$user->authorise('" . $viewName_single . "." . $perm_key . "." . $fieldName . "', 'com_" . Placefix::_("component") . "')";
								}
								$methods .=  implode(' || ', $_permission_if);
								$methods .=  ")";
								$methods .= PHP_EOL . Indent::_(4) . "{";
								$methods .= PHP_EOL . Indent::_(5) . "unset(\$pseudoanonymisedData['". $fieldName . "']);";
								$methods .= PHP_EOL . Indent::_(4) . "}";
							}
						}
						$methods .= PHP_EOL . Indent::_(3) . "}";
					}
				}


				$methods .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " get the " . $view->name_list . " table";
				$methods .= PHP_EOL . Indent::_(3) . "\$table = \$model->getTable();";

				$methods .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " check that we still have pseudoanonymised data for " . $view->name_list . " set";
				$methods .= PHP_EOL . Indent::_(3) . "if (!Super__" . "_0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$pseudoanonymisedData))";
				$methods .= PHP_EOL . Indent::_(3) . "{";
				$methods .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__) . " still archive all items";
				$methods .= PHP_EOL . Indent::_(4) . "\$table->publish(\$pks, 2);";
				$methods .= PHP_EOL . Indent::_(4) . "return false;";
				$methods .= PHP_EOL . Indent::_(3) . "}";

				$methods .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Iterate the items to anonimize each one.";
				$methods .= PHP_EOL . Indent::_(3) . "foreach (\$pks as \$i => \$pk)";
				$methods .= PHP_EOL . Indent::_(3) . "{";
				$methods .= PHP_EOL . Indent::_(4) . "\$table->reset();";
				$methods .= PHP_EOL . Indent::_(4) . "\$pseudoanonymisedData['id'] = \$pk;";
				if (ArrayHelper::check($_random_bucket))
				{
					foreach ($_random_bucket as $fieldName => $size)
					{
						$methods .= PHP_EOL . Indent::_(4) . "if (isset(\$pseudoanonymisedData['" . $fieldName . "']))";
						$methods .= PHP_EOL . Indent::_(4) . "{";
						$methods .= PHP_EOL . Indent::_(5) . "\$pseudoanonymisedData['" . $fieldName . "'] = self::randomkey(" . (int) $size . ");";
						$methods .= PHP_EOL . Indent::_(4) . "}";
					}
				}
				$methods .= PHP_EOL . PHP_EOL . Indent::_(4) . "if (\$table->bind(\$pseudoanonymisedData))";
				$methods .= PHP_EOL . Indent::_(4) . "{";
				$methods .= PHP_EOL . Indent::_(5) . "\$table->store();";
				$methods .= PHP_EOL . Indent::_(4) . "}";
				$methods .= PHP_EOL . Indent::_(3) . "}";

				$methods .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " archive all items";
				$methods .= PHP_EOL . Indent::_(3) . "\$table->publish(\$pks, 2);";
			}
			else
			{
				// Delete the rows
				$methods .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " get the " . $view->name_list . " table";
				$methods .= PHP_EOL . Indent::_(3) . "\$table = \$model->getTable();";
				$methods .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Iterate the items to delete each one.";
				$methods .= PHP_EOL . Indent::_(3) . "foreach (\$pks as \$i => \$pk)";
				$methods .= PHP_EOL . Indent::_(3) . "{";
				$methods .= PHP_EOL . Indent::_(4) . "if (\$table->load(\$pk))";
				$methods .= PHP_EOL . Indent::_(4) . "{";
				$methods .= PHP_EOL . Indent::_(5) . "\$table->delete(\$pk);";
				$methods .= PHP_EOL . Indent::_(4) . "}";
				$methods .= PHP_EOL . Indent::_(3) . "}";
			}
			$methods .= PHP_EOL . Indent::_(2) . "}";
			$methods .= PHP_EOL . Indent::_(1) . "}" . PHP_EOL;
		}
	}

	/**
	 * Native event subscriptions.
	 *
	 * @return array
	 */
	public static function getSubscribedEvents(): array
	{
		$events = is_subclass_of(parent::class, SubscriberInterface::class)
			? parent::getSubscribedEvents() : [];

		$events['jcb_ce_onBeforeModelViewData'] = 'jcb_ce_onBeforeModelViewData';
		$events['jcb_ce_onAfterGet'] = 'jcb_ce_onAfterGet';
		$events['jcb_ce_onBeforeUpdateFiles'] = 'jcb_ce_onBeforeUpdateFiles';
		$events['jcb_ce_onBeforeSetLangFileData'] = 'jcb_ce_onBeforeSetLangFileData';
		return $events;
	}
}
