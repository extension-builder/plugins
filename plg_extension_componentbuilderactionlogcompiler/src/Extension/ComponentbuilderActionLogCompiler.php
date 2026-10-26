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
namespace VDM\Plugin\Extension\ComponentbuilderActionLogCompiler\Extension;

// The power autoloader for this project (JPATH_ADMINISTRATOR) area.
$power_autoloader = JPATH_ADMINISTRATOR . '/components/com_componentbuilder/src/Helper/PowerloaderHelper.php';
if (file_exists($power_autoloader))
{
	require_once $power_autoloader;
}

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Factory as CFactory;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Utilities\ArrayHelper;
use Joomla\Event\SubscriberInterface;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Extension - Componentbuilder ActionLog Compiler plugin.
 *
 * @package   ComponentbuilderActionLogCompiler
 * @since     4.0.0
 */
final class ComponentbuilderActionLogCompiler extends CMSPlugin implements SubscriberInterface
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
	 * The Scripts
	 * 
	 * @var   array
	 */
	protected $scriptsArray = ['POSTINSTALLSCRIPT' => [], 'POSTUPDATESCRIPT' => [], 'UNINSTALLSCRIPT' => []];

	/**
	 * Event Triggered in the compiler [on After Get Component Data]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onAfterGetComponentData()
	{
		if ($this->componentActive() && ArrayHelper::check($this->languageArray))
		{
			foreach($this->languageArray as $key => $string)
			{
				CFactory::_('Language')->set('admin', $key, $string);
			}
		}
	}

	/**
	 * Event Triggered in the compiler [on After Build Files Content]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onAfterBuildFilesContent()
	{
		if ($this->componentActive())
		{
			$function_name = 'getMainJ3';
			if (CFactory::_('Config')->get('joomla_version', 3) != 3)
			{
				$function_name = 'getMainJ4';
			}
			// now load the script strings to the  component
			foreach ($this->scriptsArray as $target => &$bucket)
			{
				// add the component main target script
				CFactory::_('Compiler.Builder.Content.One')->add($target, $this->{$function_name . $target}());
				// add the component views target scripts
				if (ArrayHelper::check($bucket))
				{
					CFactory::_('Compiler.Builder.Content.One')->add($target, implode('', $bucket));
				}
			}
		}
	}

	/**
	 * Event Triggered in the compiler [on After Model View Data]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onAfterModelViewData(&$view)
	{
		// add the better integration with action log
		if ($this->componentActive() &&ArrayHelper::check($view->fields))
		{
			foreach ($view->fields as $field)
			{
				if (isset($field['title']) && $field['title'] == 1)
				{
					$title_holder = $field['base_name'];
					break;
				}
			}
			// if not found try again
			if (!isset($title_holder))
			{
				foreach ($view->fields as $field)
				{
					if (isset($field['list']) && $field['list'] == 1 &&
						isset($field['order_list']) && $field['order_list'] == 1 &&
						isset($field['link']) && $field['link'] == 1)
					{
						$title_holder = $field['base_name'];
						break;
					}
				}
			}

			// if found update placeholder
			if (isset($title_holder))
			{
				// set main title
				CFactory::_('Placeholder')->set('<<<MAIN_TITLE>>>', $title_holder, false);
			}
			else
			{
				// fall back on ID
				CFactory::_('Placeholder')->set('<<<MAIN_TITLE>>>', 'id', false);
			}

			$function_name = 'getViewJ3';
			if (CFactory::_('Config')->get('joomla_version', 3) != 3)
			{
				$function_name = 'getViewJ4';
			}

			// now load the script strings
			foreach ($this->scriptsArray as $target => &$bucket)
			{
				$bucket[] = $this->{$function_name . $target}();
			}

			// just remove it again
			CFactory::_('Placeholder')->remove('<<<MAIN_TITLE>>>');

			// set language string
			$this->languageArray[CFactory::_('Placeholder')->get_("LANG_PREFIX") . '_TYPE_' . CFactory::_('Placeholder')->get_("VIEW")] = $view->name_single;
		}
	}

	/**
	 * get the Main Post Install Script
	 * 
	 * @return  string
	 * 
	 */
	protected function getMainJ3POSTINSTALLSCRIPT()
	{
		$script = PHP_EOL . PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Set db if not set already.";
		$script .= PHP_EOL . Indent::_(3) . "if (!isset(\$db))";
		$script .= PHP_EOL . Indent::_(3) . "{";
		$script .= PHP_EOL . Indent::_(4) . "\$db = Factory::getDbo();";
		$script .= PHP_EOL . Indent::_(3) . "}";

		$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Create the " . Placefix::_("component") . " action logs extensions object.";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("component") . "_action_logs_extensions = new \stdClass();";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("component") . "_action_logs_extensions->extension = 'com_" . Placefix::_("component") . "';";

		$script .= PHP_EOL . PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Set the object into the action logs extensions table.";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("component") . "_action_logs_extensions_Inserted = \$db->insertObject('#__action_logs_extensions', \$" . Placefix::_("component") . "_action_logs_extensions);";

		return CFactory::_('Placeholder')->update_($script);
	}

	/**
	 * get the View Post Install Script
	 * 
	 * @return  string
	 * 
	 */
	protected function getViewJ3POSTINSTALLSCRIPT()
	{
		$script = PHP_EOL . PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Set db if not set already.";
		$script .= PHP_EOL . Indent::_(3) . "if (!isset(\$db))";
		$script .= PHP_EOL . Indent::_(3) . "{";
		$script .= PHP_EOL . Indent::_(4) . "\$db = Factory::getDbo();";
		$script .= PHP_EOL . Indent::_(3) . "}";

		$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Create the " . Placefix::_("view") . " action log config object.";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_action_log_config = new \stdClass();";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_action_log_config->type_title = '" . Placefix::_("VIEW") . "';";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_action_log_config->type_alias = 'com_" . Placefix::_("component") . "." . Placefix::_("view") . "';";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_action_log_config->id_holder = 'id';";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_action_log_config->title_holder = '<<<MAIN_TITLE>>>';";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_action_log_config->table_name = '#__" . Placefix::_("component") . "_" . Placefix::_("view") . "';";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_action_log_config->text_prefix = '" . Placefix::_("LANG_PREFIX") . "';";

		$script .= PHP_EOL . PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Set the object into the action log config table.";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_Inserted = \$db->insertObject('#__action_log_config', \$" . Placefix::_("view") . "_action_log_config);";

		return CFactory::_('Placeholder')->update_($script);
	}

	/**
	 * get the Main Post Update Script
	 * 
	 * @return  string
	 * 
	 */
	protected function getMainJ3POSTUPDATESCRIPT()
	{
		$script = PHP_EOL . PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Set db if not set already.";
		$script .= PHP_EOL . Indent::_(3) . "if (!isset(\$db))";
		$script .= PHP_EOL . Indent::_(3) . "{";
		$script .= PHP_EOL . Indent::_(4) . "\$db = Factory::getDbo();";
		$script .= PHP_EOL . Indent::_(3) . "}";

		$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Create the " . Placefix::_("component") . " action logs extensions object.";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("component") . "_action_logs_extensions = new \stdClass();";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("component") . "_action_logs_extensions->extension = 'com_" . Placefix::_("component") . "';";

		$script .= PHP_EOL . PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Check if " . Placefix::_("component") . " action log extension is already in action logs extensions DB.";
		$script .= PHP_EOL . Indent::_(3) . "\$query = \$db->getQuery(true);";
		$script .= PHP_EOL . Indent::_(3) . "\$query->select(\$db->quoteName(array('id')));";
		$script .= PHP_EOL . Indent::_(3) . "\$query->from(\$db->quoteName('#__action_logs_extensions'));";
		$script .= PHP_EOL . Indent::_(3) . "\$query->where(\$db->quoteName('extension') . ' LIKE '. \$db->quote(\$" . Placefix::_("component") . "_action_logs_extensions->extension));";
		$script .= PHP_EOL . Indent::_(3) . "\$db->setQuery(\$query);";
		$script .= PHP_EOL . Indent::_(3) . "\$db->execute();";

		$script .= PHP_EOL . PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Set the object into the action logs extensions table if not found.";
		$script .= PHP_EOL . Indent::_(3) . "if (!\$db->getNumRows())";
		$script .= PHP_EOL . Indent::_(3) . "{";
		$script .= PHP_EOL . Indent::_(4) . "\$" . Placefix::_("component") . "_action_logs_extensions_Inserted = \$db->insertObject('#__action_logs_extensions', \$" . Placefix::_("component") . "_action_logs_extensions);";
		$script .= PHP_EOL . Indent::_(3) . "}";

		return CFactory::_('Placeholder')->update_($script);
	}

	/**
	 * get the View Post Update Script
	 * 
	 * @return  string
	 * 
	 */
	protected function getViewJ3POSTUPDATESCRIPT()
	{
		$script = PHP_EOL . PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Set db if not set already.";
		$script .= PHP_EOL . Indent::_(3) . "if (!isset(\$db))";
		$script .= PHP_EOL . Indent::_(3) . "{";
		$script .= PHP_EOL . Indent::_(4) . "\$db = Factory::getDbo();";
		$script .= PHP_EOL . Indent::_(3) . "}";

		$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Create the " . Placefix::_("view") . " action log config object.";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_action_log_config = new \stdClass();";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_action_log_config->id = null;";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_action_log_config->type_title = '" . Placefix::_("VIEW") . "';";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_action_log_config->type_alias = 'com_" . Placefix::_("component") . "." . Placefix::_("view") . "';";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_action_log_config->id_holder = 'id';";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_action_log_config->title_holder = '<<<MAIN_TITLE>>>';";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_action_log_config->table_name = '#__" . Placefix::_("component") . "_" . Placefix::_("view") . "';";
		$script .= PHP_EOL . Indent::_(3) . "\$" . Placefix::_("view") . "_action_log_config->text_prefix = '" . Placefix::_("LANG_PREFIX") . "';";

		$script .= PHP_EOL . PHP_EOL .  Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Check if " . Placefix::_("view") . " action log config is already in action_log_config DB.";
		$script .= PHP_EOL . Indent::_(3) . "\$query = \$db->getQuery(true);";
		$script .= PHP_EOL . Indent::_(3) . "\$query->select(\$db->quoteName(array('id')));";
		$script .= PHP_EOL . Indent::_(3) . "\$query->from(\$db->quoteName('#__action_log_config'));";
		$script .= PHP_EOL . Indent::_(3) . "\$query->where(\$db->quoteName('type_alias') . ' LIKE '. \$db->quote(\$" . Placefix::_("view") . "_action_log_config->type_alias));";
		$script .= PHP_EOL . Indent::_(3) . "\$db->setQuery(\$query);";
		$script .= PHP_EOL . Indent::_(3) . "\$db->execute();";

		$script .= PHP_EOL . PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " Set the object into the content types table.";
		$script .= PHP_EOL . Indent::_(3) . "if (\$db->getNumRows())";
		$script .= PHP_EOL . Indent::_(3) . "{";
		$script .= PHP_EOL . Indent::_(4) . "\$" . Placefix::_("view") . "_action_log_config->id = \$db->loadResult();";
		$script .= PHP_EOL . Indent::_(4) . "\$" . Placefix::_("view") . "_action_log_config_Updated = \$db->updateObject('#__action_log_config', \$" . Placefix::_("view") . "_action_log_config, 'id');";
		$script .= PHP_EOL . Indent::_(3) . "}";
		$script .= PHP_EOL . Indent::_(3) . "else";
		$script .= PHP_EOL . Indent::_(3) . "{";
		$script .= PHP_EOL . Indent::_(4) . "\$" . Placefix::_("view") . "_action_log_config_Inserted = \$db->insertObject('#__action_log_config', \$" . Placefix::_("view") . "_action_log_config);";
		$script .= PHP_EOL . Indent::_(3) . "}";

		return CFactory::_('Placeholder')->update_($script);
	}

	/**
	 * get the Main Uninstall Script
	 * 
	 * @return  string
	 * 
	 */
	protected function getMainJ3UNINSTALLSCRIPT()
	{
		$script = PHP_EOL . PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Set db if not set already.";
		$script .= PHP_EOL . Indent::_(2) . "if (!isset(\$db))";
		$script .= PHP_EOL . Indent::_(2) . "{";
		$script .= PHP_EOL . Indent::_(3) . "\$db = Factory::getDbo();";
		$script .= PHP_EOL . Indent::_(2) . "}";
		$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Set app if not set already.";
		$script .= PHP_EOL . Indent::_(2) . "if (!isset(\$app))";
		$script .= PHP_EOL . Indent::_(2) . "{";
		$script .= PHP_EOL . Indent::_(3) . "\$app = Factory::getApplication();";
		$script .= PHP_EOL . Indent::_(2) . "}";

		$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Remove " . Placefix::_("Component") . " from the action_logs_extensions table";
		$script .= PHP_EOL . Indent::_(2) . "\$" . Placefix::_("component") . "_action_logs_extensions = array( \$db->quoteName('extension') . ' = ' . \$db->quote('com_" . Placefix::_("component") . "') );";
		$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Create a new query object.";
		$script .= PHP_EOL . Indent::_(2) . "\$query = \$db->getQuery(true);";
		$script .= PHP_EOL . Indent::_(2) . "\$query->delete(\$db->quoteName('#__action_logs_extensions'));";
		$script .= PHP_EOL . Indent::_(2) . "\$query->where(\$" . Placefix::_("component") . "_action_logs_extensions);";
		$script .= PHP_EOL . Indent::_(2) . "\$db->setQuery(\$query);";

		$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Execute the query to remove " . Placefix::_("Component") . "";
		$script .= PHP_EOL . Indent::_(2) . "\$" . Placefix::_("component") . "_removed_done = \$db->execute();";
		$script .= PHP_EOL . Indent::_(2) . "if (\$" . Placefix::_("component") . "_removed_done)";
		$script .= PHP_EOL . Indent::_(2) . "{";
		$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " If successfully remove " . Placefix::_("Component") . " add queued success message.";
		$script .= PHP_EOL . Indent::_(3) . "\$app->enqueueMessage(Te" . "xt::_('The com_" . Placefix::_("component") . " extension was removed from the <b>#__action_logs_extensions</b> table'));";
		$script .= PHP_EOL . Indent::_(2) . "}";

		return CFactory::_('Placeholder')->update_($script);
	}

	/**
	 * get the View Uninstall Script
	 * 
	 * @return  string
	 * 
	 */
	protected function getViewJ3UNINSTALLSCRIPT()
	{
		$script = PHP_EOL . PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Set db if not set already.";
		$script .= PHP_EOL . Indent::_(2) . "if (!isset(\$db))";
		$script .= PHP_EOL . Indent::_(2) . "{";
		$script .= PHP_EOL . Indent::_(3) . "\$db = Factory::getDbo();";
		$script .= PHP_EOL . Indent::_(2) . "}";
		$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Set app if not set already.";
		$script .= PHP_EOL . Indent::_(2) . "if (!isset(\$app))";
		$script .= PHP_EOL . Indent::_(2) . "{";
		$script .= PHP_EOL . Indent::_(3) . "\$app = Factory::getApplication();";
		$script .= PHP_EOL . Indent::_(2) . "}";

		$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Remove " . Placefix::_("Component") . " " . Placefix::_("View") . " from the action_log_config table";
		$script .= PHP_EOL . Indent::_(2) . "\$" . Placefix::_("view") . "_action_log_config = array( \$db->quoteName('type_alias') . ' = '. \$db->quote('com_" . Placefix::_("component") . "." . Placefix::_("view") . "') );";
		$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Create a new query object.";
		$script .= PHP_EOL . Indent::_(2) . "\$query = \$db->getQuery(true);";
		$script .= PHP_EOL . Indent::_(2) . "\$query->delete(\$db->quoteName('#__action_log_config'));";
		$script .= PHP_EOL . Indent::_(2) . "\$query->where(\$" . Placefix::_("view") . "_action_log_config);";
		$script .= PHP_EOL . Indent::_(2) . "\$db->setQuery(\$query);";
		$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__) . " Execute the query to remove com_" . Placefix::_("component") . "." . Placefix::_("view") . "";
		$script .= PHP_EOL . Indent::_(2) . "\$" . Placefix::_("view") . "_action_log_config_done = \$db->execute();";
		$script .= PHP_EOL . Indent::_(2) . "if (\$" . Placefix::_("view") . "_action_log_config_done)";
		$script .= PHP_EOL . Indent::_(2) . "{";
		$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__) . " If successfully removed " . Placefix::_("Component") . " " . Placefix::_("View") . " add queued success message.";
		$script .= PHP_EOL . Indent::_(3) . "\$app->enqueueMessage(Te" . "xt::_('The com_" . Placefix::_("component") . "." . Placefix::_("view") . " type alias was removed from the <b>#__action_log_config</b> table'));";
		$script .= PHP_EOL . Indent::_(2) . "}";

		return CFactory::_('Placeholder')->update_($script);
	}

	/**
	 * get the Main Post Install Script
	 * 
	 * @return  string
	 * 
	 */
	protected function getMainJ4POSTINSTALLSCRIPT()
	{
		$script = PHP_EOL . PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__)
			. " Add component to the action logs extensions table.";
		$script .= PHP_EOL . Indent::_(3) . "\$this->setActionLogsExtensions();";

		return $script;
	}

	/**
	 * get the View Post Install Script
	 * 
	 * @return  string
	 * 
	 */
	protected function getViewJ4POSTINSTALLSCRIPT()
	{
		$script = PHP_EOL . PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__)
			. " Add " . Placefix::_("View") . " to the action logs config table.";
		$script .= PHP_EOL . Indent::_(3) . "\$this->setActionLogConfig(";
		$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__) . " typeTitle";
		$script .= PHP_EOL . Indent::_(4) . "'" . Placefix::_("VIEW") . "',";
		$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__) . " typeAlias";
		$script .= PHP_EOL . Indent::_(4) . "'com_" . Placefix::_("component") . "." . Placefix::_("view") . "',";
		$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__) . " idHolder";
		$script .= PHP_EOL . Indent::_(4) . "'id',";
		$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__) . " titleHolder";
		$script .= PHP_EOL . Indent::_(4) . "'<<<MAIN_TITLE>>>',";
		$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__) . " tableName";
		$script .= PHP_EOL . Indent::_(4) . "'#__" . Placefix::_("component") . "_" . Placefix::_("view") . "',";
		$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__) . " textPrefix";
		$script .= PHP_EOL . Indent::_(4) . "'" . Placefix::_("LANG_PREFIX") . "'";
		$script .= PHP_EOL . Indent::_(3) . ");";

		return CFactory::_('Placeholder')->update_($script);
	}

	/**
	 * get the Main Post Update Script
	 * 
	 * @return  string
	 * 
	 */
	protected function getMainJ4POSTUPDATESCRIPT()
	{
		$script = PHP_EOL . PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__)
			. " Add/Update component in the action logs extensions table.";
		$script .= PHP_EOL . Indent::_(3) . "\$this->setActionLogsExtensions();";

		return $script;
	}

	/**
	 * get the View Post Update Script
	 * 
	 * @return  string
	 * 
	 */
	protected function getViewJ4POSTUPDATESCRIPT()
	{
		$script = PHP_EOL . PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__)
			. " Add/Update " . Placefix::_("View") . " in the action logs config table.";
		$script .= PHP_EOL . Indent::_(3) . "\$this->setActionLogConfig(";
		$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__) . " typeTitle";
		$script .= PHP_EOL . Indent::_(4) . "'" . Placefix::_("VIEW") . "',";
		$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__) . " typeAlias";
		$script .= PHP_EOL . Indent::_(4) . "'com_" . Placefix::_("component") . "." . Placefix::_("view") . "',";
		$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__) . " idHolder";
		$script .= PHP_EOL . Indent::_(4) . "'id',";
		$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__) . " titleHolder";
		$script .= PHP_EOL . Indent::_(4) . "'<<<MAIN_TITLE>>>',";
		$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__) . " tableName";
		$script .= PHP_EOL . Indent::_(4) . "'#__" . Placefix::_("component") . "_" . Placefix::_("view") . "',";
		$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__) . " textPrefix";
		$script .= PHP_EOL . Indent::_(4) . "'" . Placefix::_("LANG_PREFIX") . "'";
		$script .= PHP_EOL . Indent::_(3) . ");";

		return CFactory::_('Placeholder')->update_($script);
	}

	/**
	 * get the Main Uninstall Script
	 * 
	 * @return  string
	 * 
	 */
	protected function getMainJ4UNINSTALLSCRIPT()
	{
		$script = PHP_EOL . PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " Remove component from action logs extensions table.";
		$script .= PHP_EOL . Indent::_(2) . "\$this->removeActionLogsExtensions();";

		return $script;
	}

	/**
	 * get the View Uninstall Script
	 * 
	 * @return  string
	 * 
	 */
	protected function getViewJ4UNINSTALLSCRIPT()
	{
		$script = PHP_EOL . PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " Remove " . Placefix::_("View") . " from action logs config table.";
		$script .= PHP_EOL . Indent::_(2) . "\$this->removeActionLogConfig('com_"
			. Placefix::_("component") . "." . Placefix::_("view") . "');";

		return CFactory::_('Placeholder')->update_($script);
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
			return in_array((int) CFactory::_('Config')->component_id, $this->componentsActive);
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
			'jcb_ce_onAfterBuildFilesContent' => 'jcb_ce_onAfterBuildFilesContent',
			'jcb_ce_onAfterModelViewData' => 'jcb_ce_onAfterModelViewData'
		];
	}
}
