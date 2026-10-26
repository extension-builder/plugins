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
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

/**
 * Extension - Componentbuilder Powers Autoloader Compiler script file.
 *
 * @package ComponentbuilderPowersAutoloaderCompiler
 */
class plgExtensionComponentbuilderPowersAutoloaderCompilerInstallerScript implements InstallerScriptInterface
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
	 */
	public function __construct()
	{
		// get application
		$this->app = Factory::getApplication();

		if (is_file(JPATH_ROOT . '/plugins/extension/componentbuilderpowersautoloadercompiler/componentbuilderpowersautoloadercompiler.php'))
		{
			$this->deleteFiles[] = '/plugins/extension/componentbuilderpowersautoloadercompiler/componentbuilderpowersautoloadercompiler.php';
		}
	}

	/**
	 * Called on install.
	 *
	 * @param   InstallerAdapter  $adapter  The calling installer adapter.
	 *
	 * @return  bool  True on success.
	 * @since   6.0.0
	 */
	public function install(InstallerAdapter $adapter): bool
	{
		return true;
	}

	/**
	 * Called on update.
	 *
	 * @param   InstallerAdapter  $adapter  The calling installer adapter.
	 *
	 * @return  bool  True on success.
	 * @since   6.0.0
	 */
	public function update(InstallerAdapter $adapter): bool
	{
		return true;
	}

	/**
	 * Called on uninstall.
	 *
	 * @param   InstallerAdapter  $adapter  The calling installer adapter.
	 *
	 * @return  bool  True on success.
	 * @since   6.0.0
	 */
	public function uninstall(InstallerAdapter $adapter): bool
	{
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
	public function preflight(string $route, InstallerAdapter $adapter): bool
	{
		// Use the installer application.
		$app = $this->app;

		// the default for both install and update
		$jversion = new Version();
		if (!$jversion->isCompatible('6.0.0'))
		{
			$app->enqueueMessage('Please upgrade to at least Joomla! 6.0.0 before continuing!', 'error');
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
	public function postflight(string $route, InstallerAdapter $adapter): bool
	{
		// Use the installer application.
		$app = $this->app;

		return true;
	}

	/**
	 * Remove the files and folders in the given array from
	 *
	 * @return  void
	 * @since   5.0.2
	 */
	protected function removeFiles(): void
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

return new plgExtensionComponentbuilderPowersAutoloaderCompilerInstallerScript();
