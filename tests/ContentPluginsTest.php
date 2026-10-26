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


use Joomla\CMS\Event\Model\BeforeSaveEvent;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Form\Form;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Plugin\Content\ComponentbuilderPrivacyTabs\Extension\ComponentbuilderPrivacyTabs;


/**
 * Native dispatch must retain the actual JCB forms and item mutations.
 *
 * @since 6.2.0
 */
#[CoversNothing]
final class ContentPluginsTest extends PluginTestCase
{
	/**
	 * The maintained content plugins and the forms they extend.
	 *
	 * @return iterable
	 * @since 6.2.0
	 */
	public static function forms(): iterable
	{
		yield 'admin headers' => ['ComponentbuilderAdminHeadersTabs', 'admin_view', 'admin_view_headers'];
		yield 'component dashboard headers' => ['ComponentbuilderComponentDashboardHeadersTabs', 'component_dashboard', 'component_dashboard_headers'];
		yield 'component headers' => ['ComponentbuilderComponentHeadersTabs', 'joomla_component', 'joomla_component_headers'];
		yield 'custom admin headers' => ['ComponentbuilderCustomAdminHeadersTabs', 'custom_admin_view', 'custom_admin_view_headers'];
		yield 'dynamic get headers' => ['ComponentbuilderDynamicGetHeadersTabs', 'dynamic_get', 'dynamic_get_headers'];
		yield 'field ordering' => ['ComponentbuilderFieldOrderingTabs', 'admin_view', 'fieldordering'];
		yield 'language options' => ['ComponentbuilderLanguageTabs', 'joomla_component', 'language_options'];
		yield 'privacy' => ['ComponentbuilderPrivacyTabs', 'admin_view', 'privacy'];
		yield 'site headers' => ['ComponentbuilderSiteHeadersTabs', 'site_view', 'site_view_headers'];
	}

	/**
	 * Dispatch typed events into the real plugin, loading its shipped XML form.
	 *
	 * @param string $name     Plugin class name.
	 * @param string $view     JCB form context.
	 * @param string $fieldset Expected form section.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	#[DataProvider('forms')]
	public function testFormPreparationRetainsFieldsAndEditorSettings(string $name, string $view, string $fieldset): void
	{
		$class = 'VDM\\Plugin\\Content\\' . $name . '\\Extension\\' . $name;
		$plugin = new $class(['name' => strtolower($name), 'type' => 'content', 'params' => new Registry(['plugin' => 23])]);
		$plugin->setApplication($this->application);
		$this->dispatcher->addSubscriber($plugin);
		$form = new Form('com_componentbuilder.' . $view);
		$this->assertTrue($form->load('<form><field name="existing" type="text" /></form>'));
		$event = new PrepareFormEvent('onContentPrepareForm', ['subject' => $form, 'data' => (object) ['id' => 4]]);
		$this->dispatcher->dispatch('onContentPrepareForm', $event);

		$xml = $form->getXml();
		$this->assertCount(1, $xml->xpath('//field[@name="existing"]'));
		$this->assertCount(1, $xml->xpath('//fieldset[@name="' . $fieldset . '"]'));
		$expected = simplexml_load_file(JCB_PLUGINS_ROOT . '/plg_content_' . strtolower($name) . '/forms/' . $view . '.xml');
		$this->assertCount(count($expected->xpath('//field')), $xml->xpath('//fieldset[@name="' . $fieldset . '"]//field'));

		$editors = $xml->xpath('//field[@type="editor"]');
		if (str_contains($name, 'Headers'))
		{
			$this->assertNotEmpty($editors);
		}
		foreach ($editors as $editor)
		{
			$this->assertSame('codemirror|none', (string) $editor['editor']);
		}
		$this->assertSame($form, $event->getForm());
		$this->assertArrayNotHasKey('result', $event->getArguments());

		$unrelated = new Form('com_content.article');
		$this->assertTrue($unrelated->load('<form><field name="existing" type="text" /></form>'));
		$before = $unrelated->getXml()->asXML();
		$this->dispatcher->dispatch('onContentPrepareForm', new PrepareFormEvent('onContentPrepareForm', ['subject' => $unrelated, 'data' => []]));
		$this->assertSame($before, $unrelated->getXml()->asXML());
	}

	/**
	 * Privacy plugin selection is shown only when no global selection exists.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testPrivacyConfigurationIsAvailableWithoutGlobalPluginSelection(): void
	{
		$plugin = new ComponentbuilderPrivacyTabs(['params' => new Registry(['plugin' => 0])]);
		$plugin->setApplication($this->application);
		$this->dispatcher->addSubscriber($plugin);
		$form = new Form('com_componentbuilder.admin_view');
		$this->assertTrue($form->load('<form />'));
		$this->dispatcher->dispatch('onContentPrepareForm', new PrepareFormEvent('onContentPrepareForm', ['subject' => $form, 'data' => []]));
		$this->assertCount(1, $form->getXml()->xpath('//fieldset[@name="plugin"]'));
		$this->assertCount(1, $form->getXml()->xpath('//fieldset[@name="privacy"]'));
	}

	/**
	 * Native before-save dispatch preserves the item and does not invent a veto.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testPrivacySaveRetainsJsonParametersAndIgnoresOtherComponents(): void
	{
		$plugin = new ComponentbuilderPrivacyTabs(['params' => new Registry(['plugin' => 23])]);
		$plugin->setApplication($this->application);
		$this->dispatcher->addSubscriber($plugin);
		foreach (['com_componentbuilder.admin_view', 'com_content.article'] as $context)
		{
			$item = (object) ['params' => '{"privacy":{"activate":1},"other":"keep"}'];
			$event = new BeforeSaveEvent('onContentBeforeSave', ['context' => $context, 'subject' => $item, 'isNew' => false, 'data' => []]);
			$this->dispatcher->dispatch('onContentBeforeSave', $event);
			$this->assertSame(['privacy' => ['activate' => 1], 'other' => 'keep'], json_decode($item->params, true, 512, JSON_THROW_ON_ERROR));
			$this->assertSame($item, $event->getItem());
			$this->assertSame([], $event->getArgument('result', []));
		}
	}
}
