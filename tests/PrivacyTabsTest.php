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


use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Cache\Exception\CacheConnectingException;
use Joomla\CMS\Event\Model\AfterCleanCacheEvent;
use Joomla\CMS\Event\Model\BeforeSaveEvent;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\Mysqli\MysqliQuery;
use Joomla\DI\Container;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversNothing;
use ReflectionMethod;
use ReflectionProperty;


/**
 * Native privacy events preserve the persisted choice and cache notifications.
 *
 * @since 6.2.0
 */
#[CoversNothing]
final class PrivacyTabsTest extends PluginTestCase
{
	/**
	 * A selected plugin is saved globally, removed from view params, and invalidates both caches.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testSaveUpdatesGlobalPluginAndDispatchesBothCacheEvents(): void
	{
		$database = $this->createMock(DatabaseInterface::class);
		$database->method('getServerType')->willReturn('mysql');
		$database->method('getName')->willReturn('privacy_plugin_test');
		$database->method('createQuery')->willReturnCallback(static fn() => new MysqliQuery($database));
		$database->method('quoteName')->willReturnArgument(0);
		$database->method('quote')->willReturnCallback(static fn($value) => "'" . $value . "'");
		$fields = [];
		foreach (['extension_id', 'name', 'element', 'params'] as $field)
		{
			$fields[$field] = (object) ['Default' => null];
		}
		$database->method('getTableColumns')->willReturn($fields);
		$database->expects($this->once())->method('loadAssoc')->willReturn([
			'extension_id' => 45,
			'name' => 'Privacy tabs',
			'element' => 'componentbuilderprivacytabs',
			'params' => '{}',
		]);
		$saved = null;
		$database->expects($this->once())->method('updateObject')->willReturnCallback(
			function ($table, $item, $keys) use (&$saved): bool
			{
				$this->assertSame('#__extensions', $table);
				$this->assertSame(['extension_id'], $keys);
				$this->assertSame(45, $item->extension_id);
				$saved = json_decode($item->params, true, 512, JSON_THROW_ON_ERROR);
				return true;
			}
		);
		$cache = $this->createMock(CallbackController::class);
		$cache->expects($this->exactly(2))->method('__call')->with('clean', [])->willReturn(true);
		$cacheFactory = $this->createMock(CacheControllerFactoryInterface::class);
		$options = [];
		$cacheFactory->expects($this->exactly(2))->method('createCacheController')->willReturnCallback(
			function ($type, $configuration) use ($cache, &$options): CallbackController
			{
				$this->assertSame('callback', $type);
				$options[] = $configuration;
				return $cache;
			}
		);
		$container = new Container();
		$container->set(DatabaseInterface::class, $database);
		$container->set(CacheControllerFactoryInterface::class, $cacheFactory);
		$this->setJoomlaContainer($container);
		$this->application->method('get')->willReturnMap([['cache_path', JPATH_SITE . '/cache', '/configured/site/cache']]);
		$events = [];
		$this->dispatcher->addListener('onContentCleanCache', static function (AfterCleanCacheEvent $event) use (&$events): void
		{
			$events[] = [$event->getDefaultGroup(), $event->getCacheBase(), $event->getResult()];
		});
		$class = $this->compiledPlugin('ComponentbuilderPrivacyTabs')['class'];
		$plugin = new $class(['params' => new Registry(['plugin' => 0, 'keep' => true])]);
		$plugin->setApplication($this->application);
		$this->dispatcher->addSubscriber($plugin);
		$item = (object) ['params' => '{"plugin":73,"privacy":{"activate":1},"other":"retained"}'];
		$event = new BeforeSaveEvent('onContentBeforeSave', ['context' => 'com_componentbuilder.admin_view', 'subject' => $item, 'isNew' => false, 'data' => []]);
		$tableFields = new ReflectionProperty(Table::class, 'tableFields');
		$previousFields = $tableFields->getValue();
		try
		{
			$this->dispatcher->dispatch('onContentBeforeSave', $event);
		}
		finally
		{
			$tableFields->setValue(null, $previousFields);
		}
		$this->assertSame(['plugin' => 73, 'keep' => true], $saved);
		$this->assertSame(73, $plugin->params->get('plugin'));
		$this->assertSame(['privacy' => ['activate' => 1], 'other' => 'retained'], json_decode($item->params, true, 512, JSON_THROW_ON_ERROR));
		$this->assertSame([
			['com_plugins', '/configured/site/cache', true],
			['com_plugins', JPATH_ADMINISTRATOR . '/cache', true],
		], $events);
		$this->assertSame(array_column($options, 'cachebase'), array_column($events, 1));
		$this->assertSame([], $event->getArgument('result', []));
	}

	/**
	 * A cache failure is reported through the native event instead of being lost.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testCacheFailureDispatchesFalseResult(): void
	{
		$cacheFactory = $this->createMock(CacheControllerFactoryInterface::class);
		$cacheFactory->expects($this->once())->method('createCacheController')->willThrowException(new CacheConnectingException('unavailable'));
		$container = new Container();
		$container->set(CacheControllerFactoryInterface::class, $cacheFactory);
		$this->setJoomlaContainer($container);
		$events = [];
		$this->dispatcher->addListener('onContentCleanCache', static function (AfterCleanCacheEvent $event) use (&$events): void
		{
			$events[] = [$event->getDefaultGroup(), $event->getCacheBase(), $event->getResult()];
		});
		$class = $this->compiledPlugin('ComponentbuilderPrivacyTabs')['class'];
		$plugin = new $class(['params' => new Registry()]);
		$plugin->setApplication($this->application);
		(new ReflectionMethod($plugin, 'cleanCache'))->invoke($plugin, 'com_plugins', 1);
		$this->assertSame([['com_plugins', JPATH_ADMINISTRATOR . '/cache', false]], $events);
	}
}
