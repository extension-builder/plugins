<?php
/**
 * @package    Joomla.Component.Builder.Tests
 * @created    26th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace VDM\Plugin\Tests;


use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\ComponentRecord;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\Dispatcher;
use ReflectionProperty;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Isolate Joomla globals while exercising the real supplied plugin classes.
 *
 * @since 6.2.0
 */
abstract class PluginTestCase extends FilesystemTestCase
{
	/**
	 * Static state restored after every plugin test.
	 *
	 * @var array
	 * @since 6.2.0
	 */
	private array $pluginState = [];

	/**
	 * Application boundary without booting a Joomla site.
	 *
	 * @var CMSApplication
	 * @since 6.2.0
	 */
	protected CMSApplication $application;

	/**
	 * Real dispatcher shared with the application boundary.
	 *
	 * @var Dispatcher
	 * @since 6.2.0
	 */
	protected Dispatcher $dispatcher;

	/**
	 * Set up deterministic component parameters, paths and language state.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	protected function setUp(): void
	{
		parent::setUp();
		foreach ([[ComponentHelper::class, 'components'], [FormHelper::class, 'paths'], [PluginHelper::class, 'plugins']] as [$class, $name])
		{
			$property = new ReflectionProperty($class, $name);
			$this->pluginState[] = [$property, $property->getValue()];
		}
		(new ReflectionProperty(ComponentHelper::class, 'components'))->setValue(null, [
			'com_componentbuilder' => new ComponentRecord([
				'option' => 'com_componentbuilder',
				'enabled' => 1,
				'params' => '{"editor":"codemirror"}',
			]),
		]);
		(new ReflectionProperty(FormHelper::class, 'paths'))->setValue(null, null);
		$this->dispatcher = new Dispatcher();
		$this->application = $this->createStub(CMSApplication::class);
		$this->application->method('getLanguage')->willReturn(new Language('en-GB'));
		$this->application->method('getDispatcher')->willReturn($this->dispatcher);
		$this->setJoomlaApplication($this->application);
	}

	/**
	 * Restore shared core state so random execution order remains safe.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	protected function tearDown(): void
	{
		foreach ($this->pluginState as [$property, $value])
		{
			$property->setValue(null, $value);
		}
		$this->pluginState = [];
		parent::tearDown();
	}
}
