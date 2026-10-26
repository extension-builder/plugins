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
namespace VDM\Plugin\Content\ComponentbuilderComponentHeadersTabs\Extension;

use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Component\ComponentHelper;
use VDM\Joomla\Utilities\ArrayHelper;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Content - Componentbuilder Component Headers Tabs plugin.
 *
 * @package   ComponentbuilderComponentHeadersTabs
 * @since     4.0.0
 */
final class ComponentbuilderComponentHeadersTabs extends CMSPlugin implements SubscriberInterface
{

	/**
	 * Affects constructor behaviour. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected  $autoloadLanguage = true;

	/**
	 * Get the native Joomla event subscriptions.
	 *
	 * @return  array
	 * @since   6.0.1
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onContentPrepareForm' => 'onContentPrepareForm',
		];
	}

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

		// When this is componentbuilder joomla_component
		if (strpos($context, 'com_componentbuilder.joomla_component') === 0)
		{
			// Add the forms path
			Form::addFormPath(__DIR__ . '/../../forms');
			// add the admin view params for privacy integration
			$form->loadFile('joomla_component');
			// update all editors to use this components global editor
			$global_editor = ComponentHelper::getParams('com_componentbuilder')->get('editor', 'none');
			// set the field editor value (with none as fallback)
			$editors = $form->getXml()->xpath('descendant::fields[@name="joomla_component_headers"]//field[@type="editor"]');
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
}
