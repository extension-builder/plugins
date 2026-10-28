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
namespace VDM\Plugin\Extension\ComponentbuilderPowersAutoloaderCompiler\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use VDM\Joomla\Componentbuilder\Compiler\Factory as CompilerFactory;
use VDM\Joomla\Utilities\ArrayHelper;
use Joomla\Event\SubscriberInterface;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Extension - Componentbuilder Powers Autoloader Compiler plugin.
 *
 * @package   ComponentbuilderPowersAutoloaderCompiler
 * @since     6.0.0
 */
final class ComponentbuilderPowersAutoloaderCompiler extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected  $autoloadLanguage = true;

	/**
	 * Event Triggered in the compiler [on After Get]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onAfterGet()
	{
		// check if this component needs a power autoloader plugin loaded
		if (CompilerFactory::_('Config')->add_power && $this->componentActive())
		{
			// now get the plugin ID if set
			if (($id = (int) $this->params->get('plugin', 0)) !== 0)
			{
				// load the power autoloader plugin
				CompilerFactory::_('Joomlaplugin.Data')->set($id);
				// now set the plugin powers placeholder
				CompilerFactory::_('Compiler.Builder.Content.One')->set('PLUGIN_POWER_AUTOLOADER', '');
			}
			else
			{
				Factory::getApplication()->enqueueMessage(Text::_('PLG_EXTENSION_COMPONENTBUILDERPOWERSAUTOLOADERCOMPILER_YOU_DO_NOT_HAVE_A_GLOBAL_POWER_PLUGIN_SETUP_SO_THE_POWERS_PLUGIN_AUTOLOADER_COULD_NOT_BE_ADDED'), 'Notice');
			}
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
			return in_array((int) CompilerFactory::_('Config')->component_id, $this->componentsActive);
		}
		return false;
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

		$events['jcb_ce_onAfterGet'] = 'jcb_ce_onAfterGet';
		return $events;
	}
}
