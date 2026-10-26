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
namespace VDM\Plugin\Content\ComponentbuilderDynamicGetHeadersTabs\Extension;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Component\ComponentHelper;
use VDM\Joomla\Utilities\ArrayHelper;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Content - Componentbuilder Dynamic Get Headers Tabs plugin.
 *
 * @package   ComponentbuilderDynamicGetHeadersTabs
 * @since     4.0.0
 */
final class ComponentbuilderDynamicGetHeadersTabs extends CMSPlugin
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
	 * @param   Form     $form  The form
	 * @param   \stdClass  $data  The data
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	public function onContentPrepareForm(Form $form, $data)
	{
		$context = $form->getName();

		// When this is componentbuilder dynamic_get
		if (strpos($context, 'com_componentbuilder.dynamic_get') === 0)
		{
			// Add the forms path
			Form::addFormPath(__DIR__ . '/../../forms');
			// add the admin view params for privacy integration
			$form->loadFile('dynamic_get');
			// update all editors to use this components global editor
			$global_editor = ComponentHelper::getParams('com_componentbuilder')->get('editor', 'none');
			// set the field editor value (with none as fallback)
			$editors = $form->getXml()->xpath('descendant::fields[@name="dynamic_get_headers"]//field[@type="editor"]');
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
		return true;
	}
}
