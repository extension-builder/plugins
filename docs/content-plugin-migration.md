# Content plugin definitions for Joomla 6

Copy the reviewed source changes into the corresponding **Joomla 6 JCB plugin
records** before compiling the component with the native Joomla 6 compiler.
These source files are the migration reference; changing an installed plugin
file alone does not update the `main_class_code` stored in JCB.

All nine content plugins now implement Joomla's `SubscriberInterface`, explicitly
subscribe to `onContentPrepareForm`, and receive a `PrepareFormEvent`. The handler
gets the existing form with `$event->getForm()` and performs the same form and
editor modifications. `PrepareFormEvent` has no result collection: the previous
`return true` is removed, not replaced by `addResult(true)`.

| JCB plugin / source class | Form context retained |
| --- | --- |
| `componentbuilderadminheaderstabs` / `ComponentbuilderAdminHeadersTabs` | `com_componentbuilder.admin_view` |
| `componentbuildercomponentdashboardheaderstabs` / `ComponentbuilderComponentDashboardHeadersTabs` | `com_componentbuilder.component_dashboard` |
| `componentbuildercomponentheaderstabs` / `ComponentbuilderComponentHeadersTabs` | `com_componentbuilder.joomla_component` |
| `componentbuildercustomadminheaderstabs` / `ComponentbuilderCustomAdminHeadersTabs` | `com_componentbuilder.custom_admin_view` |
| `componentbuilderdynamicgetheaderstabs` / `ComponentbuilderDynamicGetHeadersTabs` | `com_componentbuilder.dynamic_get` |
| `componentbuilderfieldorderingtabs` / `ComponentbuilderFieldOrderingTabs` | `com_componentbuilder.admin_view` |
| `componentbuilderlanguagetabs` / `ComponentbuilderLanguageTabs` | `com_componentbuilder.joomla_component` |
| `componentbuilderprivacytabs` / `ComponentbuilderPrivacyTabs` | `com_componentbuilder.admin_view` |
| `componentbuildersiteheaderstabs` / `ComponentbuilderSiteHeadersTabs` | `com_componentbuilder.site_view` |

For each row the changed class is
`plg_content_<plugin>/src/Extension/<class>.php`. Copy both the changed methods and
the required event/interface references into JCB's existing plugin definition
fields. These files are generated PHP with resolved class imports. When updating
JCB definitions, retain the project's normal Joomla Power references and import
resolution; do not replace that pipeline or invent Power identifiers.

## Privacy Tabs also migrates save and cache handling

`ComponentbuilderPrivacyTabs` explicitly subscribes to `onContentBeforeSave` and
receives `BeforeSaveEvent`. It gets the context and mutable item from that event
and retains the existing parameter persistence and cache clearing behavior. The
handler previously returned no result; it still contributes no success/failure
result and modifies the same item object.

Its cache helper now reads configuration from the injected application, obtains
a callback cache through `CacheControllerFactoryInterface`, and dispatches a
native `AfterCleanCacheEvent`. It clears both the site and administrator plugin
cache locations as before and reports cache exceptions through the event's
`result` argument. The extension table's `params` property is assigned directly
instead of using the deprecated legacy property setter.

No fallback to the deprecated plugin listener adapter, application
`triggerEvent()`, `Factory::getConfig()`, or `Cache::getInstance()` is introduced.

## Joomla 4 and Joomla 5 boundaries

**Keep separate Joomla 4 definitions for all nine plugins.** `PrepareFormEvent`,
`BeforeSaveEvent`, and `AfterCleanCacheEvent` were introduced in Joomla 5.0. Joomla
4 dispatches a different event contract, so copying these typed handlers into a
Joomla 4 plugin record would break dispatch.

The event APIs used here already exist in Joomla 5.0; this handler migration does
not itself require separate Joomla 5 and Joomla 6 method bodies. Joomla's 5.0
`FormBehaviorTrait` dispatches `PrepareFormEvent` directly. However, these plugin
packages are the Joomla 6 distribution, and their installer minimum remains
Joomla 6.0. Retain the existing Joomla 5 distribution/definitions unless a
separate Joomla 5 build is intentionally maintained and tested. This change does
not assert that every other dependency of the complete plugins supports every
Joomla 5 minor version.

Primary API references:

- [Joomla 5.0 PrepareFormEvent](https://github.com/joomla/joomla-cms/blob/5.0.0/libraries/src/Event/Model/PrepareFormEvent.php)
- [Joomla 5.0 form dispatch](https://github.com/joomla/joomla-cms/blob/5.0.0/libraries/src/MVC/Model/FormBehaviorTrait.php)
- [Joomla 5.0 BeforeSaveEvent](https://github.com/joomla/joomla-cms/blob/5.0.0/libraries/src/Event/Model/BeforeSaveEvent.php)
- [Joomla 5.0 AfterCleanCacheEvent](https://github.com/joomla/joomla-cms/blob/5.0.0/libraries/src/Event/Model/AfterCleanCacheEvent.php)
