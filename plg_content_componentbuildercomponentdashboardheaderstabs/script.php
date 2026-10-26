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

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Version;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

/**
 * Content - Componentbuilder Component Dashboard Headers Tabs script file.
 *
 * @package ComponentbuilderComponentDashboardHeadersTabs
 */
class plgContentComponentbuilderComponentDashboardHeadersTabsInstallerScript
{
	/**
	 * The CMS Application.
	 *
	 * @since  4.4.2
	 */
	protected $app;

	/**
	 * A list of files to be deleted
	 *
	 * @var    array
	 * @since  3.6
	 */
	protected array $deleteFiles = [];

	/**
	 * A list of folders to be deleted
	 *
	 * @var    array
	 * @since  3.6
	 */
	protected array $deleteFolders = [];

	/**
	 * Constructor
	 *
	 * @param   InstallerAdapter  $adapter  The object responsible for running this script
	 */
	public function __construct($adapter)
	{
		// get application
		$this->app = Factory::getApplication();

		if (is_file(JPATH_ROOT . '/plugins/content/componentbuildercomponentdashboardheaderstabs/componentbuildercomponentdashboardheaderstabs.php'))
		{
			$this->deleteFiles[] = '/plugins/content/componentbuildercomponentdashboardheaderstabs/componentbuildercomponentdashboardheaderstabs.php';
		}
	}

	/**
	 * Called before any type of action
	 *
	 * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
	 * @param   InstallerAdapter  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function preflight($route, $adapter)
	{
		// set application to local method var, just use $this->app in future [we will drop $app in J6]
		$app = $this->app;

		// the default for both install and update
		$jversion = new Version();
		if (!$jversion->isCompatible('5.0.0'))
		{
			$app->enqueueMessage('Please upgrade to at least Joomla! 5.0.0 before continuing!', 'error');
			return false;
		}

		// remove old files and folders
		$this->removeFiles();

		return true;
	}

	/**
	 * Called before any type of action
	 *
	 * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
	 * @param   InstallerAdapter  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function postflight($route, $adapter)
	{
		// set application to local method var, just use $this->app in future [we will drop $app in J6]
		$app = $this->app;

		return true;
	}

	/**
	 * Remove the files and folders in the given array from
	 *
	 * @return  void
	 * @since   5.0.2
	 */
	protected function removeFiles()
	{
		if (!empty($this->deleteFiles))
		{
			foreach ($this->deleteFiles as $file)
			{
				if (is_file(JPATH_ROOT . $file) && !File::delete(JPATH_ROOT . $file))
				{
					echo Text::sprintf('JLIB_INSTALLER_ERROR_FILE_FOLDER', $file) . '<br>';
				}
			}
		}

		if (!empty($this->deleteFolders))
		{
			foreach ($this->deleteFolders as $folder)
			{
				if (is_dir(JPATH_ROOT . $folder) && !Folder::delete(JPATH_ROOT . $folder))
				{
					echo Text::sprintf('JLIB_INSTALLER_ERROR_FILE_FOLDER', $folder) . '<br>';
				}
			}
		}
	}
}
