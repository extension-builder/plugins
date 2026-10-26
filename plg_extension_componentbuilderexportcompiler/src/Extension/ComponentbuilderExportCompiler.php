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
namespace VDM\Plugin\Extension\ComponentbuilderExportCompiler\Extension;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use VDM\Joomla\Componentbuilder\Compiler\Factory;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use Joomla\Event\SubscriberInterface;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Extension - Componentbuilder Export Compiler plugin.
 *
 * @package   ComponentbuilderExportCompiler
 * @since     3.0.0
 */
final class ComponentbuilderExportCompiler extends CMSPlugin implements SubscriberInterface
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

	/*
	 * The Export Text Only switch
	 * 
	 * @var      int
	 */
	protected $exportTextOnly = 0;

	/*
	 * The Strict Field Export Permissions switch
	 * 
	 * @var      bool
	 */
	protected $strictFieldExportPermissions = false;

	/**
	 * Event Triggered in the compiler [on Before Get Component Data]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onAfterGetComponentData()
	{
		if ($this->exportTextOnly && $this->componentActive())
		{
			// activate export text only
			Factory::_('Config')->set('export_text_only', (int) $this->exportTextOnly);

			// activate strict_permission_per_field if set in plugin (default true)
			Factory::_('Config')->set('permission_strict_per_field', (bool) $this->strictFieldExportPermissions);
		}
	}

	/**
	 * Event Triggered in the compiler [on After Model Component Data]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onAfterModelComponentData(&$component)
	{
		// check if we have export for any view
		if ($this->componentActive())
		{
			// set the export/import option
			if (isset($component->admin_views) && ArrayHelper::check($component->admin_views))
			{
				foreach ($component->admin_views as $view)
				{
					if (!$this->exportTextOnly && (isset($view['port']) && $view['port'] || 1 == $view['settings']->add_custom_import))
					{
						$this->exportTextOnly = 1;
						$this->strictFieldExportPermissions = (bool) $this->params->get('strict_permission_per_field', 1);
					}
				}
			}
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
		if ($this->exportTextOnly && $this->componentActive() && ArrayHelper::check($this->languageArray))
		{
			foreach($this->languageArray as $key => $string)
			{
				Factory::_('Language')->set('adminsys', $key, $string);
			}
		}
	}

	/**
	 * Event Triggered in the compiler [on Before Set Config Field sets]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onBeforeSetConfigFieldsets(&$timer)
	{
		// only add fields after second time
		if ($this->exportTextOnly && $this->componentActive() && $timer == 2)
		{
			// main lang prefix
			$lang = Factory::_('Config')->lang_prefix . '_CONFIG';
			// start building field set for config
			Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(1) . "<fieldset");
			Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(2) . 'name="export_text_only_config"');
			Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(2) . 'label="' . $lang . '_EXPORT_TEXT_ONLY_TAB_LABEL"');
			Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(2) . 'description="' . $lang . '_EXPORT_TEXT_ONLY_TAB_DESCRIPTION">');
			// setup lang
			$this->languageArray[$lang . '_EXPORT_TEXT_ONLY_TAB_LABEL'] = "Export Options";
			$this->languageArray[$lang . '_EXPORT_TEXT_ONLY_TAB_DESCRIPTION'] = "Here are some extra option to adjust the export behaviour of admin views.";
			// add custom Export Options
			if (isset($configFieldSetsCustomField['Export Options']) && ArrayHelper::check($configFieldSetsCustomField['Export Options']))
			{
				Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', implode("", $configFieldSetsCustomField['Export Options']));
				unset($configFieldSetsCustomField['Export Options']);
			}
			else
			{
				$this->languageArray[$lang . '_EXPORT_TEXT_ONLY_LABEL'] = "Export Text Only";
				$this->languageArray[$lang . '_EXPORT_TEXT_ONLY_DESCRIPTION'] = "This option enables the export of string/text instead of linked IDs in all admin views that have an export option.";

				$this->languageArray[$lang . '_ACTIVATE'] = "Activate";
				$this->languageArray[$lang . '_DEACTIVATE'] = "Deactivate";

				Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', PHP_EOL . Indent::_(2) . "<!--" . Line::_(__Line__, __Class__) . " Export Text Only Field. Type: Radio. (joomla) -->");
				Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(2) . "<field");
				Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "type=\"radio\"");
				Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "name=\"export_text_only\"");
				Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "label=\"" . $lang . "_EXPORT_TEXT_ONLY_LABEL\"");
				Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "description=\"" . $lang . "_EXPORT_TEXT_ONLY_DESCRIPTION\"");
				Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "class=\"btn-group btn-group-yesno\"");
				Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "default=\"0\">");
				Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "<!--" . Line::_(__Line__, __Class__) . " Option Set. -->");
				Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "<option value=\"1\">");
				Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(4) . $lang . "_ACTIVATE</option>");
				Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "<option value=\"0\">");
				Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(4) . $lang . "_DEACTIVATE</option>");
				Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(2) . "</field>");

				// add  strict Field Export Permissions field
				if ($this->strictFieldExportPermissions)
				{
					$this->languageArray[$lang . '_STRICT_PERMISSION_PER_FIELD_LABEL'] = "Use Strict Permission per/field";
					$this->languageArray[$lang . '_STRICT_PERMISSION_PER_FIELD_DESCRIPTION'] = "Use strict permissions per/field in the export methods where there are fields permissions in a view.";

					Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', PHP_EOL . Indent::_(2) . "<!--" . Line::_(__Line__, __Class__) . " Strict_permission_per_field Field. Type: Radio. (joomla) -->");
					Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(2) . "<field");
					Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "type=\"radio\"");
					Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "name=\"strict_permission_per_field\"");
					Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "label=\"" . $lang . "_STRICT_PERMISSION_PER_FIELD_LABEL\"");
					Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "description=\"" . $lang . "_STRICT_PERMISSION_PER_FIELD_DESCRIPTION\"");
					Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "class=\"btn-group btn-group-yesno\"");
					Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "default=\"1\">");
					Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "<!--" . Line::_(__Line__, __Class__) . " Option Set. -->");
					Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "<option value=\"1\">");
					Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(4) . $lang . "_ACTIVATE</option>");
					Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(3) . "<option value=\"0\">");
					Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(4) . $lang . "_DEACTIVATE</option>");
					Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(2) . "</field>");
				}
			}
			// close that fieldset
			Factory::_('Compiler.Builder.Config.Fieldsets')->add('component', Indent::_(1) . "</fieldset>");
		}
	}


	/**
	 * The array of active components
	 * 
	 * @var     array
	 */
	protected $componentsActive;

	/**
	 * The activate option
	 * 
	 * @var     int
	 */
	protected $activateOption = 0;

	/**
	 * Set the line number in comments
	 * 
	 * @return  bool
	 * 
	 */
	protected function componentActive()
	{
		// check the active option
		if (!$this->activateOption)
		{
			$this->activateOption = $this->params->get('activate_option', 1);
		}
		// active for all components
		if ($this->activateOption == 1)
		{
			return true;
		}
		// first check is we have the active components set
		if ($this->activateOption == 2 && !ArrayHelper::check($this->componentsActive))
		{
			$this->componentsActive = $this->params->get('components');
		}
		// only check if there are active
		if (ArrayHelper::check($this->componentsActive))
		{
			return in_array((int) Factory::_('Config')->component_id, $this->componentsActive);
		}
		return false;
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
			'jcb_ce_onAfterGetComponentData' => 'jcb_ce_onAfterGetComponentData',
			'jcb_ce_onAfterModelComponentData' => 'jcb_ce_onAfterModelComponentData',
			'jcb_ce_onBeforeSetLangFileData' => 'jcb_ce_onBeforeSetLangFileData',
			'jcb_ce_onBeforeSetConfigFieldsets' => 'jcb_ce_onBeforeSetConfigFieldsets'
		];
	}
}
