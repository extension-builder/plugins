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
namespace VDM\Plugin\Content\ComponentbuilderSiteHeadersTabs\Extension;

use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Component\ComponentHelper;
use VDM\Joomla\Utilities\ArrayHelper;
use Joomla\Event\SubscriberInterface;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Content - Componentbuilder Site Headers Tabs plugin.
 *
 * @package   ComponentbuilderSiteHeadersTabs
 * @since     6.0.0
 */
final class ComponentbuilderSiteHeadersTabs extends CMSPlugin implements SubscriberInterface
{

	/**
	 * Affects constructor behaviour. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected  $autoloadLanguage = true;


	/**
	 * Runs on content preparation of form.
	 *
	 * @param   PrepareFormEvent  $event  The form preparation event.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onContentPrepareForm(PrepareFormEvent $event): void
	{
		$form = $event->getForm();
		$context = $form->getName();

		// When this is componentbuilder site_view
		if (strpos($context, 'com_componentbuilder.site_view') === 0)
		{
			// Add the forms path
			Form::addFormPath(__DIR__ . '/../../forms');
			// add the admin view params for privacy integration
			$form->loadFile('site_view');
			// update all editors to use this components global editor
			$global_editor = ComponentHelper::getParams('com_componentbuilder')->get('editor', 'none');
			// set the field editor value (with none as fallback)
			$editors = $form->getXml()->xpath('descendant::fields[@name="site_view_headers"]//field[@type="editor"]');
			// check if we found any
			if (ArrayHelper::check($editors))
			{
				foreach ($editors as $editor)
				{
					// set the editor to the global option
					$editor['editor'] = $global_editor . '|none';
				}
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

		$events['onContentPrepareForm'] = 'onContentPrepareForm';
		return $events;
	}
}
