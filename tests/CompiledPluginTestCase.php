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


use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Joomla\Event\SubscriberInterface;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller\RecordId;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Plugin\Routes;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Resources;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\Plugin\Extension;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\Plugin\Provider;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Gui;
use VDM\Joomla\Componentbuilder\Compiler\Extension\JoomlaSix\InstallScript;
use VDM\Joomla\Componentbuilder\Compiler\Field;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\JoomlaSix\Data;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaSix\Header;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder\Reverse;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Compile supplied authored regions through the real Joomla 6 generators.
 *
 * Persistence and remote Power lookup are test boundaries. Plugin normalization,
 * route expansion, service detection, class/header/provider/installer generation
 * and Power injection run production code. These fixtures are not a maintained
 * JCB definition export or a complete package compilation.
 *
 * @since 6.2.0
 */
abstract class CompiledPluginTestCase extends ArchitectureTestCase
{
	/**
	 * Generated trees kept alive for the duration of their behavior test.
	 *
	 * @var array<string,array>
	 * @since 6.2.0
	 */
	private array $compiledPlugins = [];

	/**
	 * Generate a plugin once per test without loading the checked-in scaffolding.
	 *
	 * @param string $name Supplied class name or absolute source filename.
	 * @return array Generated class, folder, normalized definition and emitted source.
	 * @since 6.2.0
	 */
	protected function compiledPlugin(string $name): array
	{
		if (isset($this->compiledPlugins[$name]))
		{
			return $this->compiledPlugins[$name];
		}

		$files = is_file($name) ? [$name] : glob(JCB_PLUGINS_ROOT . '/plg_*/src/Extension/' . $name . '.php');
		$this->assertCount(1, $files, 'Each source fixture must identify one supplied plugin.');
		$file = $files[0];
		$sourceFolder = dirname($file, 3);
		[, $group, $element] = explode('_', basename($sourceFolder), 3);
		$parser = new Parser();
		$source = file_get_contents($file);
		$body = $group === 'webservices' ? "\t[[[API_ROUTES_METHOD]]]" : $parser->getClassCode($source);
		$imports = $group === 'webservices' ? '' : implode(PHP_EOL, $parser->getUseStatements($source));
		if ($group === 'content')
		{
			$this->assertStringNotContainsString('function getSubscribedEvents', $body);
			$this->assertStringNotContainsString('SubscriberInterface', $imports);
		}
		$this->config()->set('joomla_version', 6);
		$this->config()->set('component_code_name', 'componentbuilder');
		$this->config()->set('project_author', 'JCB test');
		$this->config()->set('project_website', 'https://example.invalid');
		$this->config()->set('add_placeholders', false);
		$this->placeholder()->remove('API_ROUTES_METHOD');
		if ($group === 'webservices')
		{
			$this->renderer(Routes::class, [
				'resources' => new Resources($this->config()),
				'recordid' => $this->renderer(RecordId::class),
			])->set($this->apiResources());
		}

		$record = (object) [
			'id' => 17,
			'class_id' => 4,
			'guid' => '3a7b223e-0346-48e6-a115-74c16a5de3ba',
			'name' => basename($file, '.php'),
			'group' => $group,
			'created' => '2026-10-26 00:00:00',
			'extends' => '\\Joomla\\CMS\\Plugin\\CMSPlugin',
			'main_class_code' => base64_encode($body),
			'comment' => base64_encode('/** Supplied authored plugin source fixture. */'),
			'header' => '',
			'class_head' => base64_encode('use Joomla\\CMS\\Plugin\\CMSPlugin;'),
			'head' => base64_encode($imports),
			'add_head' => $imports === '' ? 0 : 1,
			'addreadme' => 0,
			'add_sql' => 0,
			'add_sql_uninstall' => 0,
			'add_update_server' => 0,
			'add_sales_server' => 0,
		];
		if ($group === 'console')
		{
			$installer = file_get_contents($sourceFolder . '/script.php');
			$this->assertSame(1, preg_match('~(?<body>\t{3}// check that componentbuilder is installed.*?\t{3}\})~s', $installer, $match));
			$record->add_php_preflight_install = 1;
			$record->php_preflight_install = base64_encode($match['body']);
		}
		$plugin = $this->loadDefinition($record, $parser);
		$this->assertSame($this->placeholder()->update_($body), $plugin->main_class_code);
		$this->assertSame($element, $plugin->context_name);

		$namespacePrefix = __NAMESPACE__ . '\\Generated\\Run' . bin2hex(random_bytes(8));
		$this->placeholder()->set('NamespacePrefix', $namespacePrefix, false);
		$this->placeholder()->set('NamespacePrefix', $namespacePrefix);
		$this->placeholder()->set('Component', 'Componentbuilder');
		$this->placeholder()->set('ComponentNamespace', 'Componentbuilder');
		$this->placeholder()->set('PluginGroupNamespace', $plugin->group_namespace);
		$this->placeholder()->set('PluginNamespace', $plugin->namespace);
		$namespace = $namespacePrefix . '\\Plugin\\' . $plugin->group_namespace . '\\' . $plugin->namespace . '\\Extension';
		$header = $this->renderer(Header::class);
		$power = $this->createStub(PowerInterface::class);
		$power->method('get')->willReturnMap([
			['c06c5116-6b9d-487c-9b09-5094ec4506a3', 0, (object) [
				'_namespace' => 'Joomla\\Event', 'class_name' => 'SubscriberInterface', 'type' => 'interface',
			]],
			['7bd29d76-73c9-4c07-a5da-4f7a32aff78f', 0, (object) [
				'_namespace' => 'Joomla\\Database', 'class_name' => 'DatabaseInterface', 'type' => 'interface',
			]],
		]);
		$injector = new Injector($power, new Extractor($this->createStub(DatabaseInterface::class), 6), $parser, $this->placeholder());
		$extension = $this->renderer(Extension::class, ['power' => $power])->get($plugin);
		$code = $injector->power('<?php' . PHP_EOL . 'namespace ' . $namespace . ';' . PHP_EOL
			. $header->get('plugin.extension.header', $plugin->class_name) . PHP_EOL . $plugin->header . PHP_EOL . $extension);
		$this->assertStringNotContainsString('[[[API_ROUTES_METHOD]]]', $code);
		$this->assertSame(1, substr_count($code, 'function getSubscribedEvents('));
		if ($group !== 'webservices')
		{
			$this->assertStringContainsString($body, $code);
		}
		$relativeFolder = basename($sourceFolder);
		$generatedFile = $this->writeTemporaryFile($relativeFolder . '/src/Extension/' . $plugin->class_name . '.php', $code);
		$folder = dirname($generatedFile, 3);
		foreach (glob($sourceFolder . '/forms/*.xml') as $form)
		{
			$this->writeTemporaryFile($relativeFolder . '/forms/' . basename($form), file_get_contents($form));
		}
		$provider = $injector->power('<?php' . PHP_EOL . $header->get('plugin.provider.header', $plugin->class_name)
			. PHP_EOL . $this->renderer(Provider::class)->get($plugin));
		$this->writeTemporaryFile($relativeFolder . '/services/provider.php', $provider);
		$installerNamespace = $namespace . '\\Installer';
		$script = '<?php' . PHP_EOL . 'namespace ' . $installerNamespace . ';' . PHP_EOL . (new InstallScript())->get($plugin);
		$this->writeTemporaryFile($relativeFolder . '/script.php', $injector->power($script));
		// Unqualified installer constants resolve inside its unique test namespace.
		// Keep cleanup and the authored Console prerequisite away from the host site.
		$site = $this->createTemporaryDirectory($relativeFolder . '/installer-site');
		define($installerNamespace . '\\JPATH_ROOT', $site);
		define($installerNamespace . '\\JPATH_ADMINISTRATOR', $site . '/administrator');
		require $generatedFile;
		$class = $namespace . '\\' . $plugin->class_name;
		$this->assertTrue(is_subclass_of($class, SubscriberInterface::class));
		$result = ['class' => $class, 'folder' => $folder, 'plugin' => $plugin, 'code' => $code, 'provider' => $provider, 'script' => $script, 'site' => $site];
		$this->compiledPlugins[$name] = $result;
		$this->compiledPlugins[basename($file, '.php')] = $result;
		$this->compiledPlugins[$file] = $result;

		return $result;
	}

	/**
	 * Normalize the extracted fields through Data, including its trait service checks.
	 *
	 * @param object $record Source fields before storage decoding.
	 * @param Parser $parser The real source parser used by GUI metadata handling.
	 * @return object The normalized plugin definition.
	 * @since 6.2.0
	 */
	private function loadDefinition(object $record, Parser $parser): object
	{
		$query = $this->createStub(QueryInterface::class);
		foreach (['select', 'from', 'join', 'where'] as $method)
		{
			$query->method($method)->willReturnSelf();
		}
		$database = $this->createMock(DatabaseInterface::class);
		$database->method('quoteName')->willReturnArgument(0);
		$database->method('quote')->willReturnCallback(static fn($value) => "'" . $value . "'");
		$database->expects($this->once())->method('getQuery')->with(true)->willReturn($query);
		$database->expects($this->once())->method('setQuery')->with($query);
		$database->expects($this->once())->method('execute');
		$database->method('getNumRows')->willReturn(1);
		$database->expects($this->once())->method('loadObject')->willReturnCallback(static fn() => clone $record);
		$customcode = $this->createStub(Customcode::class);
		$customcode->method('update')->willReturnArgument(0);
		$gui = new Gui($this->config(), $this->createStub(Reverse::class), $parser, $database);
		$data = $this->renderer(Data::class, [
			'customcode' => $customcode,
			'gui' => $gui,
			'field' => $this->createStub(Field::class),
			'db' => $database,
		]);
		$languageDeprecation = 'Joomla\\CMS\\Factory::getLanguage() is deprecated. Load the language from the dependency injection container or via Joomla\\CMS\\Factory::getApplication()->getLanguage().';
		$metadataDeprecations = [];
		$previousHandler = set_error_handler(static function (int $severity, string $message, string $file, int $line) use (&$metadataDeprecations, &$previousHandler, $languageDeprecation): bool
		{
			// Joomla 6.1.2's date metadata formatter still delegates to its old language accessor.
			// Record that core-only fixture preparation boundary; runtime plugin code stays strict.
			if ($severity === E_USER_DEPRECATED && $message === $languageDeprecation
				&& str_ends_with($file, '/libraries/src/Factory.php'))
			{
				$metadataDeprecations[] = $message;
				return true;
			}

			return $previousHandler === null ? false : $previousHandler($severity, $message, $file, $line);
		}, E_DEPRECATED | E_USER_DEPRECATED);
		try
		{
			$plugin = $data->get($record->guid);
		}
		finally
		{
			restore_error_handler();
		}
		$this->assertSame([$languageDeprecation, $languageDeprecation], $metadataDeprecations);
		$this->assertNotNull($plugin);

		return $plugin;
	}

	/**
	 * A representative linked JCB resource covers authenticated CRUD and GUID routes.
	 *
	 * @return array Admin view links consumed by the real route generator.
	 * @since 6.2.0
	 */
	private function apiResources(): array
	{
		return [[
			'add_api' => 2,
			'settings' => (object) [
				'name_single' => 'Joomla Component', 'name_list' => 'Joomla Components',
				'name_single_code' => 'joomla_component', 'name_list_code' => 'joomla_components',
				'fields' => [['field' => 'guid', 'list' => 1, 'settings' => (object) [
					'name' => 'Guid', 'type_name' => 'text', 'datatype' => 'VARCHAR', 'indexes' => 2,
					'xml' => '<field type="text" name="guid" label="Guid" />',
				]]],
			],
		]];
	}
}
