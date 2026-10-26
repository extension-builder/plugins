# JCB plugins: agent guide

Read this file before changing this repository. It records the plugins' purpose,
the current migration, and the contracts that changes must preserve. Read the
actual source and tests for the area being changed; this inventory is not a
substitute for tracing a caller.

## Project and current work

This repository contains 18 Joomla plugins connected to Joomla Component Builder
(JCB): nine content plugins, seven compiler extension plugins, one console plugin,
and one webservices plugin. They extend the authoring interface, participate in
compilation, and expose existing JCB CLI/API operations.

The current work removes deprecated Joomla APIs from the **Joomla 6** build while
repairing two regressions: existing plugin definitions were rejected without a
completed migration, and moving authored footer JavaScript to the document head
changed its execution timing. Follow the paired changes:

- [Plugins PR #1](https://github.com/extension-builder/plugins/pull/1): authored
  native handlers and imports; target-aware privacy emitters; tests of generated
  packages; exact field instructions for transferring changes into JCB definitions.
- [Compiler PR #61](https://github.com/extension-builder/joomla/pull/61): compiler
  fixes and regression coverage, including JavaScript loading and diagnostics.

Start with [the migration and transfer plan](docs/joomla6-native-migration.md), then
read the [content](docs/content-plugin-migration.md),
[compiler extension](docs/extension-plugin-migration.md), and
[infrastructure](docs/infrastructure-migration.md) notes for the affected files.
Do not equate an open PR or passing focused test with a completed release.

## Files and source ownership

Each `plg_<group>_<name>` folder is a generated reference tree. During this
migration only authored regions are updated. Regenerate installable packages with
JCB; these reference trees retain the original infrastructure and are not completed
Joomla 6 distributions:

| Path within a plugin | Responsibility |
| --- | --- |
| `src/Extension/*.php` | Generated plugin classes containing authored behavior and compiler-owned expansions such as `[[[API_ROUTES_METHOD]]]`. |
| `services/provider.php` | Joomla DI construction and service injection. |
| `script.php` | Installation/update lifecycle and removal of obsolete entry files. |
| `<name>.xml` | Plugin metadata, namespace, package contents and configuration fields. |
| `forms/*.xml` | Additional form fields loaded by content plugins, when present. |
| `language/*` | Plugin configuration/form/interface language strings. |

The maintained JCB database-backed definitions remain the source of truth. This
repository supplies reviewable generated source to transfer back into those
definitions. Changing an installed PHP file does not update JCB's stored
`main_class_code`; the next compilation can overwrite it. Record which methods,
imports, forms or configuration changed. Transfer authored code into the matching
JCB fields, and regenerate providers/installers through the corresponding
compiler target. Never paste an entire generated file, provider or installer into
`main_class_code`.

Use [the exact field guide](docs/plugin-definition-fields.md). Do not edit generated
class declarations, provider files, installer wrappers or API expansions by hand.
Do not add common initialization, compatibility checks, lifecycle signatures or
success-return boilerplate to preflight/postflight fields. Preserve existing custom
actions; improve the J6 compiler when the surrounding generated structure is wrong.
Property/method selections insert code into the body editor, so updating a catalogue
entry does not migrate previously stored bodies automatically.

The API Routes definition uses `[[[API_ROUTES_METHOD]]]`. Preserve that placeholder;
the compiler generates its version-specific handler and Joomla 6 subscriptions.
Do not copy the expanded route table or an extra subscription map back into the
definition. The Joomla 5 generator must retain its existing output. An authored
custom subscription map remains authoritative.

Use the existing plugin and compiler architecture. Do not introduce a new
plugin-definition selector, template directory, `.tpl` format, broad source
rewriter, or automatic alteration of arbitrary stored PHP to resolve a local
failure. Compiler templates live in the core repository's established
`admin/compiler` version sets and `settings.json` selection.

## Plugin inventory

### Content plugins: authoring forms

Every plugin below lives in `plg_content_<name>`. JCB generates
`SubscriberInterface` and maps `onContentPrepareForm` to its authored handler accepting
`Joomla\CMS\Event\Model\PrepareFormEvent`. The handler obtains the existing form
from `$event->getForm()` and modifies that form. This event has no result list:
use neither a legacy `return true` nor an invented `addResult(true)`.

| Plugin name | Form and behavior | Related compiler plugin |
| --- | --- | --- |
| `componentbuilderadminheaderstabs` | Loads `admin_view.xml` for `com_componentbuilder.admin_view`; applies JCB's selected editor to the header editor fields. | `componentbuilderheaderscompiler` |
| `componentbuildercomponentdashboardheaderstabs` | Loads `component_dashboard.xml` for the component dashboard definition; applies the selected editor. | `componentbuilderheaderscompiler` |
| `componentbuildercomponentheaderstabs` | Loads `joomla_component.xml` for the component definition; applies the selected editor. | `componentbuilderheaderscompiler` |
| `componentbuildercustomadminheaderstabs` | Loads `custom_admin_view.xml` for custom administrator views; applies the selected editor. | `componentbuilderheaderscompiler` |
| `componentbuilderdynamicgetheaderstabs` | Loads `dynamic_get.xml` for dynamic query definitions; applies the selected editor. | `componentbuilderheaderscompiler` |
| `componentbuilderfieldorderingtabs` | Loads `admin_view.xml` with the view's field-ordering settings. | `componentbuilderfieldorderingcompiler` |
| `componentbuilderlanguagetabs` | Loads `joomla_component.xml` with component language-packaging settings. | `componentbuilderlanguagepackaging` |
| `componentbuilderprivacytabs` | Loads administrator-view privacy fields and, when no global privacy plugin is selected, `config.xml`; applies the selected editor to the custom link. Also handles saving the global plugin selection. | `componentbuilderprivacycompiler` |
| `componentbuildersiteheaderstabs` | Loads `site_view.xml` for site views; applies the selected editor. | `componentbuilderheaderscompiler` |

The form handlers retain their context-prefix checks. Do not broaden them to
unrelated components. The header plugins read
`ComponentHelper::getParams('com_componentbuilder')->get('editor', 'none')`; their
editor fallback remains `|none`.

JCB additionally subscribes Privacy Tabs' authored `onContentBeforeSave` handler using
`BeforeSaveEvent`. It reads the event's context and mutable item, normalizes the
item's JSON parameters, saves a selected global privacy plugin in the extension
table, removes that selection from the view parameters, and restores the JSON
representation. It then clears site and administrator plugin caches and emits
`AfterCleanCacheEvent` with the actual cache outcome. Its dependencies include
the application, Joomla's extension table, `DatabaseInterface`,
`CacheControllerFactoryInterface`, and the application dispatcher. Preserve both
cache locations, item mutations and failure notifications.

### Extension plugins: compiler hooks

Every plugin below lives in `plg_extension_<name>`. The hook names in this table
have the prefix **`jcb_ce_`**. Their explicit `getSubscribedEvents()` maps are part
of the contract.

| Plugin name | Subscribed hook suffixes | Reads, mutations and output |
| --- | --- | --- |
| `componentbuilderactionlogcompiler` | `onAfterGetComponentData`, `onAfterBuildFilesContent`, `onAfterModelViewData` | Selects a title field, temporarily sets `MAIN_TITLE`, collects per-view scripts and language strings, and appends install/update/uninstall action-log integration to `Compiler.Builder.Content.One`. Target 3 has distinct emitters; targets 4–6 use the installer's action-log helpers. Respects configured component activation. |
| `componentbuilderexportcompiler` | `onAfterGetComponentData`, `onAfterModelComponentData`, `onBeforeSetLangFileData`, `onBeforeSetConfigFieldsets` | Detects export/custom-import use in administrator views, sets `export_text_only` and `permission_strict_per_field` in compiler `Config`, adds export configuration fields on the **second** fieldset pass, and registers their language strings. Respects configured component activation. |
| `componentbuilderfieldorderingcompiler` | `onBeforeModelViewData` | Decodes the view's `fieldordering` parameters and writes them to `Compiler.Builder.Views.Default.Ordering` under the normalized list-view name when ordering is enabled. |
| `componentbuilderheaderscompiler` | `onBeforeModelViewData`, `onBeforeModelCustomViewData`, `onBeforeModelDynamicGetData`, `onBeforeModelComponentData`, `setClassHeader`, `onBeforeGetComponentData`, `onAfterGetComponentData` | Collects header text and linked Powers from admin/site/custom views, dynamic gets, component and dashboard settings. Resolves component placeholders and Power namespace aliases, adds missing headers without duplicates, and loads linked Powers through the existing compiler services. |
| `componentbuilderlanguagepackaging` | `onBeforeModelComponentData`, `onAfterGet`, `onBeforeSetLangFileData`, `onBeforeBuildPluginLangFiles`, `onBeforeBuildModuleLangFiles`, `onBeforeBuildAllLangFiles` | Reads language options, exclusions and translation thresholds; overrides `percentage_language_add` when configured; removes selected extra languages from `Compiler.Builder.Languages`; creates language files, manifests and ZIP archives through existing compiler paths/utilities. Its provider injects `DatabaseInterface`. Preserve client selection, threshold checks, counters and cleanup. |
| `componentbuilderpowersautoloadercompiler` | `onAfterGet` | When compiler Powers and component activation are enabled, loads the configured autoloader plugin through `Joomlaplugin.Data` and sets `PLUGIN_POWER_AUTOLOADER`. Reports a missing global selection through Joomla's message queue. |
| `componentbuilderprivacycompiler` | `onBeforeModelViewData`, `onAfterGet`, `onBeforeUpdateFiles`, `onBeforeSetLangFileData` | Collects enabled privacy views and permissions, loads the privacy plugin selected by Privacy Tabs, emits per-view `getPrivacyExport` methods and component privacy helpers, and registers language strings. Temporarily adjusts export configuration and restores it. Honors already-authored helper methods. Target 6 receives native database/query/privacy classes; targets 3–5 retain their existing output. |

These are **JCB compiler hooks**, not Joomla content events. In the companion core
repository, `Compiler/JoomlaSix/Event.php::trigger()` retrieves registered
listeners and invokes them with the original positional arguments. Some are
references to live compiler data. Do not convert these methods to single-event
handlers or remove `&` simply because native Joomla content handlers use typed
events. Preserve hook names, map entries, argument order, object/reference
mutations, stage order and temporary configuration restoration.

The compiler helper methods emitted by Privacy Compiler also retain their
existing by-reference component/helper contract. They are called by the privacy
plugin; they are not standalone Joomla dispatcher listeners.

### Console and webservices entry points

| Folder | Event and behavior | Dependencies and boundaries |
| --- | --- | --- |
| `plg_console_componentbuildercommands` | Subscribes to `ApplicationEvents::BEFORE_EXECUTE` with `registerCommands(Event $event)`. Registers `componentbuilder:compile:component` and the `init`, `get`, `reset`, `pull`, `push` package commands for entities from `Componentbuilder\Factory::getSuperpowers()`. | Uses the console application's available public container or Joomla's container, `WritableLoaderInterface`, shared command services, and JCB's existing command classes. Do not hardcode a second entity catalogue or execute commands during registration. |
| `plg_webservices_componentbuilderapiroutes` | JCB generates `onBeforeApiRoute` with `BeforeApiRouteEvent` from `[[[API_ROUTES_METHOD]]]`. It registers the linked component's JSON:API routes, including numeric-ID and GUID variants where configured. | Gets the router from the event. Preserve methods, paths, controller actions, parameter constraints and authentication defaults. The linked component's API configuration is authoritative; the checked-in expanded route table is reference output. |

## Host Joomla and generated target are different

- **Host:** the Joomla instance running the regenerated plugins. Their native package
  providers construct `CMSPlugin` with its configuration array only and inject
  the application. Joomla performs subscriber registration. Installers implement
  `InstallerScriptInterface`, return the script instance, and require Joomla 6.
  They must not enter `LegacyInstallerScript` or the deprecated dispatcher-first
  plugin constructor branch.
- **Target:** the version of the extension JCB is generating. Read
  `Compiler\Config->joomla_version` for emitted code, independently of the host.
  A Joomla 6 host can compile Joomla 3, 4 or 5 output. Preserve those outputs when
  a migration is explicitly limited to target 6. In core, event service selection
  follows host `Version::MAJOR_VERSION`; do not confuse it with target selection.

The checked-in folders retain the original generated infrastructure; only packages
regenerated with the J6 compiler are Joomla 6 distributions. Preserve
separate generated Joomla 4/5 packages and their providers/installers. All nine
content plugins need separate Joomla 4 handler definitions because the concrete
model event classes start in Joomla 5.0. Those event APIs alone do not require
separate Joomla 5/6 bodies; this is not a claim that these complete Joomla 6
packages install on Joomla 5. The detailed migration notes explain each boundary.

## Code-generation and editing conventions

Use PHP tabs, the existing Allman brace style, complete method documentation, and
small changes that preserve unrelated line endings and behavior. Existing mixed
formatting is not permission for wholesale rewrites.

When a plugin **generates code**, emit it line by line with
`PHP_EOL . Indent::_(n)` and use `Line::_(__LINE__, __CLASS__)` for generated debug
comments. Indentation must remain configurable. Do not introduce heredoc/nowdoc
PHP or JavaScript blocks, hardcoded indentation blocks, or a formatter that hides
how generated lines are built. Existing authored JavaScript and PHP are not
interchangeable with compiler-owned generated fragments.

Preserve Super Power and Joomla Power identities, placeholders, namespace
resolution and alias/collision handling. Generated PHP here contains resolved
imports; maintained JCB definitions may use Power placeholders instead. Do not
strip those placeholders, invent identifiers, or replace the Power pipeline with
parallel class maps. When emitting a Power placeholder intended for a later
compile, retain the existing split-token convention so self-compilation does not
consume it prematurely.

Preserve form field names, parameter keys, language keys, plugin folder names,
manifest entries, route contracts and cleanup paths unless the task explicitly
changes them. Do not overwrite unrelated authored definitions or generated
output to make tests pass. A deprecated expression inside a Joomla 3 emitter is
not evidence that the Joomla 6 output uses it; inspect the selected path.

## Tests and completion evidence

Follow [tests/README.md](tests/README.md) for the pinned Joomla/JCB runtime and
Composer dependency setup. From this repository run:

```bash
bash tests/run.sh
```

For paired local work, `JCB_TEST_PROJECT` may point to the companion checkout's
prepared `libraries/vendor_jcb/tests` directory. PHPUnit configuration is
`phpunit.xml.dist`; CI is `.github/workflows/plugin-tests.yml` and tests PHP 8.3
and 8.4. Setup may download pinned dependencies; individual tests must not call
remote services or mutate an installed Joomla site.

The tests compile authored source and generate infrastructure in temporary trees
before loading the resulting classes and forms, rather than loading the checked-in
reference scaffolding. They exercise real plugin source, not copied replacement
implementations. `ContentPluginsTest` covers form changes and editor settings;
`PrivacyTabsTest` covers item/global settings and cache success/failure events;
`CompilerIntegrationTest` compiles all plugin bodies through the Joomla 6
extension renderer and Joomla Power injector, then dispatches native events into
the compiled content plugins with their original form XML; `PluginInfrastructureTest` covers
providers, installers and entry-point integration. Keep additional compiler-hook
and emitted-output tests beside these as their coverage expands.
`PrivacyCompilerTest` executes emitted privacy export and removal helpers and
checks their target-specific database and export-object contracts.

The plugin source fixtures use the authored regions of the supplied PHP. Standard
content subscription maps are absent from their inputs; API input retains
`[[[API_ROUTES_METHOD]]]`. The compiler generates providers, installers and maps.
These focused fixtures are not a substitute for the complete maintained JCB
definition package. The companion installed test starts from authored fields too.

Deprecations, including suppressed constructor deprecations, are failures. Test
observable behavior: field preservation, subscription and dispatch, by-reference
mutation, dependency wiring, native installer loading, generated code and version
cutoffs. Do not weaken tests to accept a new compilation exception or to equate a
warning with a detected JavaScript defect. For JavaScript, verify DOM readiness,
global bindings, dependency order and repeated execution using the actual emitted
output in the companion core tests.

Before declaring the migration complete:

1. Review and transfer the source changes into the maintained JCB definitions.
2. Run plugin and companion compiler checks against the intended paired commits.
3. Verify that affected Joomla 3–5 generated output remains unchanged.
4. Compile the complete JCB definition package with its linked plugins, install
   the generated Joomla 6 packages, and exercise the affected authoring and
   compilation paths.
5. Report exact validation and remaining limits. Focused unit tests, Hello World
   golden output, or tests of a released administrator tree are not proof of a
   complete JCB self-compilation or of all newly generated JavaScript behavior.
