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
namespace VDM\Plugin\Extension\ComponentbuilderFieldOrderingCompiler\Extension;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Joomla\Componentbuilder\Compiler\Factory;
use Joomla\Event\SubscriberInterface;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Extension - Componentbuilder Field Ordering Compiler plugin.
 *
 * @package   ComponentbuilderFieldOrderingCompiler
 * @since     6.0.0
 */
final class ComponentbuilderFieldOrderingCompiler extends CMSPlugin implements SubscriberInterface
{
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
		if (ArrayHelper::check($view->params) && isset($view->params['fieldordering']) && ArrayHelper::check($view->params['fieldordering']))
		{
			if ($view->params['fieldordering']['add_admin_ordering'] == 1
				|| $view->params['fieldordering']['add_linked_ordering'] == 1)
			{
				// setup the view key name
				$name_list   = StringHelper::safe($view->name_list);
				// load the admin view details
				Factory::_('Compiler.Builder.Views.Default.Ordering')->set($name_list, $view->params['fieldordering']);
			}
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
		return $events;
	}
}
