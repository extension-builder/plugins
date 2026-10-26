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
namespace VDM\Plugin\Content\ComponentbuilderLanguageTabs\Extension;

use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Form\Form;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Content - Componentbuilder Language Tabs plugin.
 *
 * @package   ComponentbuilderLanguageTabs
 * @since     3.0.0
 */
final class ComponentbuilderLanguageTabs extends CMSPlugin
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

		// When this is componentbuilder admin view
		if (strpos($context, 'com_componentbuilder.joomla_component') === 0)
		{
			// Add the forms path
			Form::addFormPath(__DIR__ . '/../../forms');
			// add the admin view params for privacy integration
			$form->loadFile('joomla_component');
		}
	}

}
