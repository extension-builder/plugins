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
namespace VDM\Plugin\Content\ComponentbuilderPrivacyTabs\Extension;

use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Event\Model\BeforeSaveEvent;
use Joomla\CMS\Event\Model\AfterCleanCacheEvent;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Factory;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Table\Extension;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Exception\CacheExceptionInterface;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Content - Componentbuilder Privacy Tabs plugin.
 *
 * @package   ComponentbuilderPrivacyTabs
 * @since     3.0.0
 */
final class ComponentbuilderPrivacyTabs extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
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
		if (strpos($context, 'com_componentbuilder.admin_view') === 0)
		{
			// Add the forms path
			Form::addFormPath(__DIR__ . '/../../forms');
			// add the admin view params for privacy integration
			$form->loadFile('admin_view');
			// if the global plugin is not set, allow it to be set from the admin view
			if (($plugin = $this->params->get('plugin', 0)) == 0)
			{
				$form->loadFile('config');
				// make the plugin field required (LATER when we can give more info)
				//$form->setFieldAttribute('plugin', 'required', true, 'params.privacy');
				//$form->setFieldAttribute('plugin', 'showon', 'activate:1', 'params.privacy');
				//$form->setFieldAttribute('note_select_privacy_plugin', 'showon', 'activate:1', 'params.privacy');
			}
			// update all editors to use this components global editor
			$global_editor = ComponentHelper::getParams('com_componentbuilder')->get('editor', 'none');
			// set the field editor value (with none as fallback)
			$form->setFieldAttribute('custom_link', 'editor', $global_editor . '|none', 'params.privacy');
		}
	}

	/**
	 * Update the privacy plugin setting before saving an administrator view.
	 *
	 * @param   BeforeSaveEvent  $event  The content save event.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onContentBeforeSave(BeforeSaveEvent $event): void
	{
		$context = $event->getContext();
		$item = $event->getItem();
		// When this is componentbuilder admin view
		if ('com_componentbuilder.admin_view' === $context)
		{
			// check if this is json
			if (JsonHelper::check($item->params))
			{
				// Convert the params field to an array.
				$registry = new Registry;
				$registry->loadString($item->params);
				$item->params = $registry->toArray();
			}
			// check if the plugin was set
			if (isset($item->params['plugin']) && $item->params['plugin'] > 0)
			{
				// first set new params
				$this->params->set('plugin', (int) $item->params['plugin']);
				// update the global plugin settings
				$extensionTable = new  Extension(Factory::getContainer()->get(DatabaseInterface::class));
				$extensionTable->load(array('element' => 'componentbuilderprivacytabs'));
				// Save the change
				$extensionTable->params = $this->params->toString();
				$extensionTable->save((array) $extensionTable);
				// and remove the value from the params
				unset($item->params['plugin']);
				// clear the plugin cache
				$this->cleanCache('com_plugins', 0);
				$this->cleanCache('com_plugins', 1);
			}
			// check if this is an array
			if (ArrayHelper::check($item->params))
			{
				// Convert the params field to a string.
				$params = new Registry;
				$params->loadArray($item->params);
				$item->params = (string) $params;
			}
		}
	}

	/**
	 * Clean the cache
	 *
	 * @param   string   $group      The cache group
	 * @param   integer  $client_id  The ID of the client
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	protected function cleanCache($group = null, $client_id = 0)
	{
		$app = $this->getApplication();

		$options = [
			'defaultgroup' => $group ?: (isset($this->option) ? $this->option : $app->getInput()->get('option')),
			'cachebase' => $client_id ? JPATH_ADMINISTRATOR . '/cache' : $app->get('cache_path', JPATH_SITE . '/cache'),
			'result' => true,
		];

		try
		{
			/** @var CallbackController $cache */
			$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
				->createCacheController('callback', $options);
			$cache->clean();
		}
		catch (CacheExceptionInterface $exception)
		{
			$options['result'] = false;
		}

		// Trigger the onContentCleanCache event.
		$app->getDispatcher()->dispatch(
			'onContentCleanCache',
			new AfterCleanCacheEvent('onContentCleanCache', $options)
		);
	}
}
