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


use Joomla\CMS\Language\LanguageFactory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\User\User;
use Joomla\Component\Privacy\Administrator\Export\Domain;
use Joomla\Component\Privacy\Administrator\Export\Field;
use Joomla\Component\Privacy\Administrator\Export\Item;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Joomla\DI\Container;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Factory as CompilerFactory;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\StringHelper;


/**
 * Exercise generated privacy helpers against the pinned native Joomla runtime.
 *
 * @since 6.2.0
 */
#[CoversNothing]
final class PrivacyCompilerTest extends PluginTestCase
{
	/**
	 * Target configuration read by the emitter.
	 *
	 * @var Config
	 * @since 6.2.0
	 */
	private Config $config;

	/**
	 * Native services used by generated code.
	 *
	 * @var Container
	 * @since 6.2.0
	 */
	private Container $joomlaContainer;

	/**
	 * Utility state restored after each test.
	 *
	 * @var array
	 * @since 6.2.0
	 */
	private array $utilityState = [];

	/**
	 * Install only the compiler services required by the real emitter.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->isolateFactory(CompilerFactory::class);
		foreach ([[Indent::class, 'indent', "\t"], [Indent::class, 'bucket', []], [Line::class, 'add', false], [StringHelper::class, 'langTag', 'en-GB']] as [$class, $name, $value])
		{
			$property = new ReflectionProperty($class, $name);
			$this->utilityState[] = [$property, $property->isInitialized() ? $property->getValue() : $value];
			$property->setValue(null, $value);
		}
		$this->config = new Config(new Input([]), new Registry(), new Registry());
		$this->config->set('joomla_version', 6);
		$this->config->set('component_code_name', 'fixture');
		$services = new Container();
		$services->set('Config', $this->config);
		$services->set('Placeholder', new Placeholder($this->config));
		// Permissions are an external compiler boundary; retain the requested action.
		$services->set('Compiler.Creator.Permission', new class
		{
			/**
			 * Keep a view action unchanged at the permission boundary.
			 *
			 * @param string $view View name.
			 * @param string $action Requested action.
			 * @return string
			 * @since 6.2.0
			 */
			public function getAction(string $view, string $action): string
			{
				return $action;
			}

			/**
			 * Keep a global action unchanged at the permission boundary.
			 *
			 * @param string $view View name.
			 * @param string $action Requested action.
			 * @return string
			 * @since 6.2.0
			 */
			public function getGlobal(string $view, string $action): string
			{
				return $action;
			}
		});
		(new ReflectionProperty(CompilerFactory::class, 'container'))->setValue(null, $services);
		$this->joomlaContainer = new Container();
		$this->joomlaContainer->set(LanguageFactoryInterface::class, new LanguageFactory());
		$this->setJoomlaContainer($this->joomlaContainer);
	}

	/**
	 * Restore the compiler utilities before restoring Joomla globals.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	protected function tearDown(): void
	{
		foreach ($this->utilityState as [$property, $value])
		{
			$property->setValue(null, $value);
		}
		parent::tearDown();
	}

	/**
	 * Cover every compile target excluded from the native migration.
	 *
	 * @return iterable
	 * @since 6.2.0
	 */
	public static function olderTargets(): iterable
	{
		yield 'Joomla 3' => [3];
		yield 'Joomla 4' => [4];
		yield 'Joomla 5' => [5];
	}

	/**
	 * The compile target, rather than the Joomla 6 test host, selects APIs.
	 *
	 * @param int $target Compiler output version.
	 * @return void
	 * @since 6.2.0
	 */
	#[DataProvider('olderTargets')]
	public function testOlderTargetsKeepTheirPrivacyApis(int $target): void
	{
		$this->config->set('joomla_version', $target);
		$code = $this->generateHelpers();
		$this->assertSame(2, substr_count($code, 'Factory::getDbo()'));
		$this->assertSame(2, substr_count($code, '$db->getQuery(true)'));
		foreach (['PrivacyExportDomain', 'PrivacyExportItem', 'PrivacyExportField'] as $class)
		{
			$this->assertStringContainsString('new ' . $class . ';', $code);
		}
		$this->assertStringNotContainsString('createQuery()', $code);
		$this->assertStringNotContainsString('\\Joomla\\Component\\Privacy\\Administrator\\Export\\', $code);
	}

	/**
	 * Execute the emitted export and removal helpers with native database services.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testNativeHelpersExportRealPrivacyObjectsAndDeleteMatchingRows(): void
	{
		foreach (['Field', 'Item', 'Domain'] as $name)
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_privacy/src/Export/' . $name . '.php';
		}
		$code = $this->generateHelpers();
		$this->assertSame(2, substr_count($code, '$db->createQuery()'));
		$this->assertStringNotContainsString('getDbo()', $code);
		$this->assertStringNotContainsString('getQuery(true)', $code);
		$this->assertStringContainsString('Joomla___f5e0753c_c8d9_4965_a606_9c45b37a6857___Power', $code);
		$class = $this->loadNativeHelpers($code);
		$query = $this->createMock(QueryInterface::class);
		$query->expects($this->exactly(2))->method('select')->with('id')->willReturnSelf();
		$query->expects($this->exactly(2))->method('from')->with('`#__fixture_entry`')->willReturnSelf();
		$query->expects($this->exactly(2))->method('where')->with("`created_by` = '42'")->willReturnSelf();
		$database = $this->createMock(DatabaseInterface::class);
		$database->expects($this->exactly(2))->method('createQuery')->willReturn($query);
		$database->expects($this->never())->method('getQuery');
		$database->method('quoteName')->willReturnCallback(static fn($name) => '`' . $name . '`');
		$database->method('quote')->willReturnCallback(static fn($value) => "'" . $value . "'");
		$database->expects($this->exactly(2))->method('setQuery')->with($query)->willReturnSelf();
		$database->expects($this->exactly(2))->method('loadColumn')->willReturn([7]);
		$this->joomlaContainer->set(DatabaseInterface::class, $database);
		$table = new class
		{
			/**
			 * Rows loaded before deletion.
			 *
			 * @var array
			 * @since 6.2.0
			 */
			public array $loaded = [];

			/**
			 * Rows removed by the generated helper.
			 *
			 * @var array
			 * @since 6.2.0
			 */
			public array $deleted = [];

			/**
			 * Record a successful load without database access.
			 *
			 * @param int $id Row identifier.
			 * @return bool
			 * @since 6.2.0
			 */
			public function load(int $id): bool
			{
				$this->loaded[] = $id;
				return true;
			}
			/**
			 * Record a successful deletion without changing stored data.
			 *
			 * @param int $id Row identifier.
			 * @return bool
			 * @since 6.2.0
			 */
			public function delete(int $id): bool
			{
				$this->deleted[] = $id;
				return true;
			}
		};
		$class::$model = new class($table)
		{
			/**
			 * IDs and user received from the generated export query.
			 *
			 * @var array
			 * @since 6.2.0
			 */
			public array $exportCalls = [];

			/**
			 * Inject the table boundary used by removal.
			 *
			 * @param object $table Table boundary.
			 * @since 6.2.0
			 */
			public function __construct(private object $table)
			{
			}
			/**
			 * Return a row containing both personal and excluded system fields.
			 *
			 * @param array $ids Queried row identifiers.
			 * @param User $user User whose export is requested.
			 * @return array
			 * @since 6.2.0
			 */
			public function getPrivacyExport(array $ids, User $user): array
			{
				$this->exportCalls[] = [$ids, $user->id];
				return [['id' => 7, 'title' => 'Personal entry', 'metadata' => (object) ['key' => 'value'], 'created_by' => 42]];
			}
			/**
			 * Expose the table boundary used by generated removal code.
			 *
			 * @return object
			 * @since 6.2.0
			 */
			public function getTable(): object
			{
				return $this->table;
			}
		};
		$user = $this->createStub(User::class);
		$user->id = 42;
		$user->method('authorise')->willReturn(true);
		$plugin = new \stdClass();
		$request = new \stdClass();
		$domains = [];
		$this->assertSame($class::onPrivacyExportRequest($plugin, $domains, $request, $user), $domains);
		$this->assertCount(1, $domains);
		$this->assertInstanceOf(Domain::class, $domains[0]);
		$this->assertSame('entry', $domains[0]->name);
		$this->assertSame('fixture_entry_data', $domains[0]->description);
		$items = $domains[0]->getItems();
		$this->assertCount(1, $items);
		$this->assertInstanceOf(Item::class, $items[0]);
		$this->assertSame(7, $items[0]->id);
		$fields = [];
		foreach ($items[0]->getFields() as $field)
		{
			$this->assertInstanceOf(Field::class, $field);
			$fields[$field->name] = $field->value;
		}
		$this->assertSame(['id' => 7, 'title' => 'Personal entry', 'metadata' => print_r(['key' => 'value'], true)], $fields);
		$this->assertSame([[[7], 42]], $class::$model->exportCalls);
		$class::onPrivacyRemoveData($plugin, $request, $user);
		$this->assertSame([7], $table->loaded);
		$this->assertSame([7], $table->deleted);
	}

	/**
	 * Render all privacy helpers using the supplied plugin's actual emitter.
	 *
	 * @return string
	 * @since 6.2.0
	 */
	private function generateHelpers(): string
	{
		$class = $this->compiledPlugin('ComponentbuilderPrivacyCompiler')['class'];
		$plugin = (new ReflectionClass($class))->newInstanceWithoutConstructor();
		(new ReflectionProperty($plugin, 'activeViews'))->setValue($plugin, [(object) [
			'id' => 1,
			'name_single' => 'Entry',
			'name_list' => 'Entries',
			'params' => ['privacy' => ['user_link' => 1]],
			'fields' => [],
			'placeholders' => [Placefix::_('LANG_PREFIX') => 'COM_FIXTURE', Placefix::_('VIEWS') => 'ENTRIES'],
		]]);
		$events = ['PrivacyCanRemoveData' => true, 'PrivacyExportRequest' => true, 'PrivacyRemoveData' => true];
		return (new ReflectionMethod($plugin, 'getHelperMethod'))->invokeArgs($plugin, [&$events]);
	}

	/**
	 * Resolve existing Power tokens at the test boundary and load generated PHP.
	 *
	 * The component's model loader is the external boundary; privacy methods remain unchanged.
	 *
	 * @param string $code Generated helper methods.
	 * @return string
	 * @since 6.2.0
	 */
	private function loadNativeHelpers(string $code): string
	{
		$code = strtr($code, [
			'Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power' => '\\Joomla\\CMS\\Factory',
			'Joomla___7bd29d76_73c9_4c07_a5da_4f7a32aff78f___Power' => '\\' . DatabaseInterface::class,
			'Joomla___f5e0753c_c8d9_4965_a606_9c45b37a6857___Power' => '\\' . Domain::class,
			'Super___0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power' => '\\' . ArrayHelper::class,
			'Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power' => '\\Joomla\\CMS\\Language\\Text',
			Placefix::_('component') => 'fixture',
		]);
		$name = 'PrivacyHelper' . bin2hex(random_bytes(8));
		$source = '<?php' . PHP_EOL . 'namespace ' . __NAMESPACE__ . ';'
			. PHP_EOL . 'use Joomla\\Utilities\\ArrayHelper;'
			. PHP_EOL . 'final class ' . $name
			. PHP_EOL . '{'
			. PHP_EOL . Indent::_(1) . 'public static object $model;'
			. PHP_EOL . Indent::_(1) . 'public static function getModel(string $name): object'
			. PHP_EOL . Indent::_(1) . '{'
			. PHP_EOL . Indent::_(2) . 'return self::$model;'
			. PHP_EOL . Indent::_(1) . '}'
			. $code . PHP_EOL . '}';
		require $this->writeTemporaryFile($name . '.php', $source);
		return __NAMESPACE__ . '\\' . $name;
	}
}
