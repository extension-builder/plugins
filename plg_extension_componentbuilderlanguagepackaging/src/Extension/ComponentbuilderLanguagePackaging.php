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
namespace VDM\Plugin\Extension\ComponentbuilderLanguagePackaging\Extension;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Filesystem\Folder;
use Joomla\Database\DatabaseAwareTrait;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Componentbuilder\Compiler\Factory;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Joomla\Utilities\FileHelper;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Joomla\Utilities\GetHelper;
use Joomla\Event\SubscriberInterface;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Extension - Componentbuilder Language Packaging plugin.
 *
 * @package   ComponentbuilderLanguagePackaging
 * @since     3.0.0
 */
final class ComponentbuilderLanguagePackaging extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;

	/**
	 * Affects constructor behaviour. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected  $autoloadLanguage = true;

	/**
	 * The percentage before a language can be added
	 * 
	 * @var     int
	 * @since  1.0.0
	 */
	protected $percentageLanguageAdd;

	/**
	 * The percentage before a language can be added
	 * 
	 * @var     int
	 * @since  1.0.0
	 */
	protected $percentageLanguageAddOveride = 200;

	/**
	 * The languages names
	 * 
	 * @var     array
	 * @since  1.0.0
	 */
	protected $languageNames = [];

	/**
	 * The language building tracker
	 * 
	 * @var     array
	 * @since  1.0.0
	 */
	protected $languageTracker = [];

	/**
	 * The should the site folder be removed
	 * 
	 * @var     bool
	 * @since  1.0.0
	 */
	protected $removeSiteFolder;

	/**
	 * The should the site folder be removed
	 * 
	 * @var     bool
	 * @since  1.0.0
	 */
	protected $removeSiteEditFolder;

	/**
	 * The component path
	 * 
	 * @var     string
	 * @since  1.0.0
	 */
	protected $componentPath;

	/**
	 * The compiler path
	 * 
	 * @var     string
	 * @since  1.0.0
	 */
	protected $compilerPath;

	/**
	 * The temporal path
	 * 
	 * @var     string
	 * @since  1.0.0
	 */
	protected $tempPath;

	/**
	 * The joomla version
	 * 
	 * @var     string
	 * @since  1.0.0
	 */
	protected $joomlaVersion;

	/**
	 * The component version
	 * 
	 * @var     string
	 * @since  1.0.0
	 */
	protected $component_version;

	/**
	 * The component name
	 * 
	 * @var     string
	 * @since  1.0.0
	 */
	protected $componentCodeName;

	/**
	 * The file content static values
	 * 
	 * @var     array
	 * @since  1.0.0
	 */
	protected $fileContentStatic;

	/*
	 * The line numbers Switch
	 * 
	 * @var      boolean
	 * @since  1.0.0
	 */
	protected $debugLinenr = false;

	/**
	 * The Active Components
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected  $activeComponents = [];

	/**
	 * The Active Components Names
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected  $activeComponentsNames = [];

	/**
	 * The Languages
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected  $languages = [];

	/**
	 * The Language build details
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected  $buildDetails = [];

	/**
	 * The Excluded Languages
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected  $excludedLang = [];

	/**
	 * The Active Language
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected  $langTag;

	/**
	 * Event Triggered in the compiler [on Before Model Component Data]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onBeforeModelComponentData(&$component)
	{
		// add the privacy
		$component->params = (isset($component->params) && JsonHelper::check($component->params)) ? json_decode($component->params, true) : $component->params;
		if (ArrayHelper::check($component->params) && isset($component->params['language_options']) &&
			isset($component->params['language_options']['activate']) && $component->params['language_options']['activate'] == 1)
		{
			// load the admin component details
			$this->activeComponents[$component->id] = Factory::_('Config')->component_context;
			$this->activeComponentsNames[$component->id] = StringHelper::safe($component->name_code);
			$this->activeComponentsRealNames[$component->id] = $component->name;
			// add excluded list of languages
			if (isset($component->params['language_options']['languages']))
			{
				$this->excludedLang[$component->id] = $component->params['language_options']['languages'];
			}
			else
			{
				$this->excludedLang[$component->id] = array();
			}
			// now set the component add languages if we should use local (2)
			if (isset($component->params['language_options']['use_percentagelanguageadd']) && $component->params['language_options']['use_percentagelanguageadd'] == 2)
			{
				$this->percentageLanguageAddOveride = $component->params['language_options']['percentagelanguageadd'];
			}
		}
	}

	/**
	 * Event Triggered in the compiler [on After Get]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onAfterGet()
	{
		// get component id
		$id = (int) Factory::_('Config')->component_id;
		// check if there is active
		if (ArrayHelper::check($this->activeComponents)
			&& isset($this->activeComponents[$id]) && $this->percentageLanguageAddOveride != 200)
		{
			Factory::_('Config')->set('percentage_language_add', $this->percentageLanguageAddOveride);
		}
	}

	/**
	 * Event Triggered in the compiler [on Before Set Lang File Data]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onBeforeSetLangFileData()
	{
		// lets map some arrays to the plugin for later use
		$this->compilerPath = Factory::_('Config')->compiler_path;
		$this->tempPath = Factory::_('Config')->tmp_path;
		$this->langTag = Factory::_('Config')->lang_tag;
		$this->debugLinenr = Factory::_('Config')->get('debug_line_nr', false);
		$this->component_version = Factory::_('Config')->get('component_version', '1.0.0');
		$this->joomlaVersion = Factory::_('Config')->joomla_version;
		$this->percentageLanguageAdd = Factory::_('Config')->percentage_language_add;
		$this->removeSiteFolder = Factory::_('Config')->remove_site_folder;
		$this->removeSiteEditFolder = Factory::_('Config')->remove_site_edit_folder;
		$this->componentPath = Factory::_('Utilities.Paths')->component_path;
		$this->componentCodeName = Factory::_('Config')->component_code_name;
	}

	/**
	 * Event Triggered in the compiler [on Before Build Plugin Lang Files]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onBeforeBuildPluginLangFiles(&$plugin)
	{
		// get component id
		$id = (int) Factory::_('Config')->component_id;
		// check if there is active
		if (ArrayHelper::check($this->activeComponents) && isset($this->activeComponents[$id]))
		{
			// set file name
			$file_name = 'plg_' . strtolower($plugin->group) . '_' . strtolower($plugin->code_name);
			// extrude the languages that should not remain in the plugin
			$this->extrudeLanguages($id, 'plugins', Factory::_('Config')->lang_tag, $file_name, 'admin');
		}
	}

	/**
	 * Event Triggered in the compiler [on Before Build Module Lang Files]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onBeforeBuildModuleLangFiles(&$module)
	{
		// get component id
		$id = (int) Factory::_('Config')->component_id;
		// check if there is active
		if (ArrayHelper::check($this->activeComponents) && isset($this->activeComponents[$id]))
		{
			// extrude the languages that should not remain in the module
			$this->extrudeLanguages($id, 'modules', Factory::_('Config')->lang_tag, $module->file_name, $module->target_client);
		}
	}

	/**
	 * Event Triggered in the compiler [on Before Build All Lang Files]
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function jcb_ce_onBeforeBuildAllLangFiles($targetArea)
	{
		// get component id
		$id = (int) Factory::_('Config')->component_id;
		// check if there is active
		if (ArrayHelper::check($this->activeComponents) && isset($this->activeComponents[$id]))
		{
			// set file name
			$file_name = 'com_' . $this->activeComponentsNames[$id];
			// extrude the languages that should not remain in the module
			$this->extrudeLanguages($id, $targetArea, Factory::_('Config')->lang_tag, $file_name);
		}
		// build the language packages
		$this->buildLanguages($id, Factory::_('Config')->lang_tag);
	}

	/**
	 * Extruder of the languages
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function extrudeLanguages(&$id, $targetArea, $langTag, &$file_name, $target_client = 'both')
	{
		$mainLangLoader = [];
		// check if this id was set before
		if (!isset($this->languages[$id]))
		{
			$this->languages[$id] = [];
			$this->buildDetails[$id] = [];
		}
		// check if this file name was set before
		if (!isset($this->languages[$id][$file_name]))
		{
			$this->languages[$id][$file_name] = [];
		}
		// set all the extra languages not excluded
		foreach (Factory::_('Compiler.Builder.Languages')->get($targetArea) as $key => $language)
		{
			if ($key !== $langTag && ArrayHelper::check($language) && (!isset($this->excludedLang[$id]) || !in_array($key, $this->excludedLang[$id])))
			{
				// add to our bucket
				$this->languages[$id][$file_name][$key] = $language;
				// remove from the JCB build
				Factory::_('Compiler.Builder.Languages')->remove("{$targetArea}.{$key}");
			}
			// count the area strings
			if ($langTag === $key)
			{
				foreach ($language as $area => $languageStrings)
				{
					$mainLangLoader[$area] = count($languageStrings);
				}
			}
		}
		// store details for build
		$this->buildDetails[$id][$file_name] = [$langTag => $mainLangLoader, 'target_client' => $target_client];
	}

	/**
	 * Start the building of the languages packages
	 * 
	 * @return  void
	 * 
	 */
	protected function buildLanguages(&$id, $langTag)
	{
		if (isset($this->languages[$id]) && ArrayHelper::check($this->languages[$id]))
		{
			// rest xml array
			$langXML = [];
			$langNames = [];
			$langPackages = [];
			$langZIPNames = [];
			$langXMLNames = [];
			$versionName = $this->activeComponentsNames[$id] . '_v' . str_replace('.', '_', $this->component_version . '__J' . $this->joomlaVersion);
			foreach ($this->languages[$id] as $file_name => $languages)
			{
				if (ArrayHelper::check($languages) && isset($this->buildDetails[$id][$file_name][$langTag]))
				{
					// get the main lang loader
					$mainLangLoader = $this->buildDetails[$id][$file_name][$langTag];
					// get the target client
					$target_client = $this->buildDetails[$id][$file_name]['target_client'];
					foreach ($languages as $tag => $areas)
					{
						// trim the tag
						$tag = trim($tag);
						// get language name
						$langName = $this->getLanguageName($tag);
						$langCodeName = StringHelper::safe($langName, 'F');
						// set the file folder name
						$langFolderFileName = $langCodeName . '_' . $versionName;
						// set the main folder path
						$main_path = $this->compilerPath . '/' . $langFolderFileName . '/';
						// set the language name for later
						$langNames[$main_path] = $langName;
						// set the lang zip name for later
						$langZIPNames[$main_path] = $langFolderFileName;
						// set the lang xml name for later
						$langXMLNames[$main_path] = $langCodeName . '_' . $this->activeComponentsNames[$id] ;
						// we must check if old folder is found and remove it
						if (!isset($this->languageTracker[$main_path]) && is_dir($main_path))
						{
							// remove the main folder
							Factory::_('Utilities.Folder')->remove($main_path);
							// do not remove it again
							$this->languageTracker[$main_path] = true;
						}
						// check if exist and create if not
						if (!is_dir($main_path))
						{
							Folder::create($main_path);
							// count the folder created
							Factory::_('Utilities.Counter')->folder++;
						}
						foreach ($areas as $area => $languageStrings)
						{
							// get the file name
							$fileName = $this->getLanguageFileName($file_name, $tag, $area);
							// check if language should be added
							if (Factory::_('Language.Translation')->check($tag, $languageStrings, $mainLangLoader[$area], $fileName) && ($actions = $this->getLangActions($file_name, $tag, $area, $target_client)) !== false)
							{
								// set the language data
								$lang = array_map(
									function ($langstring, $placeholder) {
										return $placeholder . '="' . $langstring . '"';
									}, array_values($languageStrings),
									array_keys($languageStrings)
								);
								// set the line counter
								Factory::_('Utilities.Counter')->line += count(
									(array) $lang
								);
								// check that the main folder exist
								foreach ($actions as $act)
								{
									$client_path = $main_path . $act['target_client'] . '/';
									// check if exist and create if not
									if (!is_dir($client_path))
									{
										Folder::create($client_path);
										// count the folder created
										$this->folderCount++;
									}
									// write the language data to a file
									FileHelper::write(
										$client_path . $act['file_name'], implode(PHP_EOL, $lang)
									);
									// count the file created
									Factory::_('Utilities.Counter')->line++;
									// build xml strings
									if (!isset($langXML[$main_path]))
									{
										$langXML[$main_path] = array();
										$langPackages[$main_path] = array();
									}
									if (!isset($langXML[$main_path][$act['target_client']]))
									{
										$langXML[$main_path][$act['target_client']] = array();
									}
									// set the package targets
									$langPackages[$main_path][$act['target_client']] = $act['target'];
									$langXML[$main_path][$act['target_client']][] = $act['file_name'];
								}
								// clear memory
								unset($lang);
							}
						}
					}
				}
			}

			// load the lang xml
			if (ArrayHelper::check($langXML))
			{
				foreach ($langXML as $main_path => $target_clients)
				{
					// get the XML
					$xml = str_replace(
							array_keys(Factory::_('Compiler.Builder.Content.One')->allActive()),
							array_values(Factory::_('Compiler.Builder.Content.One')->allActive()),
							$this->getLanguageXML($target_clients, $langPackages[$main_path], $langNames[$main_path])
						);
					// get the XML File Name
					$xmlFileName = $langXMLNames[$main_path] . '.xml';
					// write the language data to a file
					FileHelper::write(
						$main_path . $xmlFileName, $xml
					);
					// set the zip full path
					$zipPath = $this->tempPath . '/' . $langZIPNames[$main_path] . '.zip';
					// now zip the package
					if (FileHelper::zip(
						$main_path, $zipPath
					))
					{
						// now remove the package
						Factory::_('Utilities.Folder')->remove($main_path);
					}
				}
			}
		}
	}

	/**
	 * get the language xml
	 * 
	 * @return  string
	 * 
	 */
	protected function getLanguageXML(&$target_clients, &$targets, &$language)
	{
		$xml = '<?xml version="1.0" encoding="utf-8"?>';
		$xml .= PHP_EOL . '<extension type="file" version="3.2" method="upgrade">';
		$xml .= PHP_EOL . Indent::_(1) . '<name>' . Placefix::_h('Component') . ' - ' . $language . ' Language Pack</name>';
		$xml .= PHP_EOL . Indent::_(1) . '<creationDate>' . Placefix::_h('BUILDDATE') . '</creationDate>';
		$xml .= PHP_EOL . Indent::_(1) . '<author>' . Placefix::_h('AUTHOR') . '</author>';
		$xml .= PHP_EOL . Indent::_(1) . '<authorEmail>' . Placefix::_h('AUTHOREMAIL') . '</authorEmail>';
		$xml .= PHP_EOL . Indent::_(1) . '<authorUrl>' . Placefix::_h('AUTHORWEBSITE') . '</authorUrl>';
		$xml .= PHP_EOL . Indent::_(1) . '<copyright>' . Placefix::_h('COPYRIGHT') . '</copyright>';
		$xml .= PHP_EOL . Indent::_(1) . '<license>' . Placefix::_h('LICENSE') . '</license>';
		$xml .= PHP_EOL . Indent::_(1) . '<version>' . Placefix::_h('ACTUALVERSION') . '</version>';
		$xml .= PHP_EOL . Indent::_(1) . '<description>' . $language . ' Language Pack - ' . Placefix::_h('SHORT_DESCRIPTION') . '</description>';
		$xml .= PHP_EOL . Indent::_(1) . '<fileset>';
		foreach ($target_clients as $target_client => $files)
		{
			$xml .= PHP_EOL . Indent::_(2) . '<files folder="' . $target_client . '" target="' . $targets[$target_client] . '">';
			foreach ($files as $file)
			{
				$xml .= PHP_EOL . Indent::_(3) . '<filename>' . $file . '</filename>';
			}
			$xml .= PHP_EOL . Indent::_(2) . '</files>';
		}
		$xml .= PHP_EOL . Indent::_(1) . '</fileset>';
		$xml .= PHP_EOL . '</extension>';

		return $xml;
	}

	/**
	 * get the language name
	 * 
	 * @return  string
	 * 
	 */
	protected function getLanguageName(&$tag)
	{
		if (!isset($this->languageNames[$tag]))
		{
			if (($name = GetHelper::var('language', $tag, 'langtag', 'name')) !== false)
			{
				$this->languageNames[$tag] = $name;
			}
			else
			{
				$this->languageNames[$tag] = $tag;
			}
		}
		return $this->languageNames[$tag];
	}

	/**
	 * get the language actions
	 * 
	 * @return  array
	 * 
	 */
	protected function getLangActions(&$file_name, &$tag, &$area, &$target_client)
	{
		// component extention type
		if (strpos($file_name, 'com_') !== false)
		{
			$target_client = 'admin';
			$target = 'administrator/language/';
			if (strpos($area, 'site') !== false)
			{
				$target_client = 'site';
				$target = 'language/';
			}
			return array(
				array(
					'target_client' => $target_client,
					'target' => $target . $tag,
					'file_name' => $this->getLanguageFileName($file_name, $tag, $area)
				)
			);
		}
		elseif ('admin' === $target_client)
		{
			$target = 'administrator/language/';
		}
		else
		{
			$target = 'language/';
		}
		// module/plugin extension type (TODO we return both for now)
		return array(
			array(
				'target_client' => $target_client,
				'target' => $target . $tag,
				'file_name' => $this->getLanguageFileName($file_name, $tag, $area)
			),
			array(
				'target_client' => $target_client,
				'target' => $target . $tag,
				'file_name' => $this->getLanguageFileName($file_name, $tag, $area, '.sys')
			)
		);
	}

	/**
	 * get the language file name
	 * 
	 * @return  string
	 * 
	 */
	protected function getLanguageFileName(&$file_name, &$tag, &$area, $type = '')
	{
		// component extension type
		if (strpos($file_name, 'com_') !== false)
		{
			if (strpos($area, 'sys') !== false)
			{
				$type = '.sys';
			}
		}
		// file name
		return $tag . '.' . $file_name . $type . '.ini';
	}

	/**
	 * check if a translation should be added
	 * 
	 * @return  bool
	 * @deprecated 3.4 Use Factory::_('Language.Translation')->check(...);
	 */
	protected function shouldLanguageBeAdded(&$tag, &$languageStrings, &$total, &$file_name)
	{
		// only log messages for none $this->langTag translations
		Factory::_('Language.Translation')->check(
			$tag, $languageStrings, $total, $file_name
		);
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
			'jcb_ce_onBeforeModelComponentData' => 'jcb_ce_onBeforeModelComponentData',
			'jcb_ce_onAfterGet' => 'jcb_ce_onAfterGet',
			'jcb_ce_onBeforeSetLangFileData' => 'jcb_ce_onBeforeSetLangFileData',
			'jcb_ce_onBeforeBuildPluginLangFiles' => 'jcb_ce_onBeforeBuildPluginLangFiles',
			'jcb_ce_onBeforeBuildModuleLangFiles' => 'jcb_ce_onBeforeBuildModuleLangFiles',
			'jcb_ce_onBeforeBuildAllLangFiles' => 'jcb_ce_onBeforeBuildAllLangFiles'
		];
	}
}
