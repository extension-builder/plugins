# Joomla 6 plugin infrastructure migration

These changes belong to the Joomla 6 versions of the plugins. The plugin
folders are compiled extension sources; copy authored handler changes into the
corresponding JCB plugin definitions, and regenerate service providers and
installer scripts with the Joomla 6 compiler. Do not copy a generated provider
or installer class into a plugin's `main_class_code` field.

## Providers

Every `plg_*/services/provider.php` now passes the plugin configuration array as
the only argument to the inherited `CMSPlugin` constructor. Application
injection and additional service injection, including the Language Packaging
plugin's database, remain unchanged.

Joomla 6 supports this constructor directly. Passing the dispatcher as the
first constructor argument invokes Joomla 6's deprecated compatibility branch.
The updated provider matches JCB's
`Compiler/Architecture/JoomlaSix/Plugin/Provider.php` output. Joomla handles
subscription registration after loading the plugin; the provider does not add
an alternative registration path.

## Installer scripts

Every `plg_*/script.php` now:

- implements `InstallerScriptInterface` and returns its instance;
- has a parameterless constructor, since Joomla supplies the adapter to the
  lifecycle methods rather than to native script loading;
- implements typed `install`, `update`, `uninstall`, `preflight` and `postflight`
  methods;
- requires Joomla 6.0.0 before removing an obsolete plugin entry file;
- preserves its existing cleanup paths and the Console Commands plugin's
  requirement that JCB be installed first.

The empty install/update/uninstall methods return success, preserving the
previous absence of custom actions. Returning the interface instance prevents
Joomla from constructing `LegacyInstallerScript`. These changes match the
existing Joomla 6 compiler's native installer contract.

## API and console plugins

`plg_webservices_componentbuilderapiroutes/src/Extension/ComponentbuilderApiRoutes.php`
already accepted `BeforeApiRouteEvent`. It now explicitly implements
`SubscriberInterface` and declares the subscription map, removing implicit
method discovery. The route table is unchanged.

The Console Commands handler already subscribes to `application.before_execute`.
Joomla dispatches an `ApplicationEvent`, which extends the handler's accepted
`Event` type and provides `getApplication()`. Its command registration and
container lookup remain unchanged; the provider and installer changes are the
native Joomla 6 corrections needed for that plugin.

## Joomla 4 and 5 separation

**All 18 deployable plugin packages in this branch are Joomla 6 packages.**
Their config-only providers require Joomla 6, and their installers explicitly
reject Joomla 4 and 5. Keep the previous provider/installer output when
compiling separately maintained Joomla 4/5 definitions. Do not deploy these
Joomla 6 folders over an older Joomla installation.

The affected plugin folders are:

- `plg_console_componentbuildercommands`
- `plg_content_componentbuilderadminheaderstabs`
- `plg_content_componentbuildercomponentdashboardheaderstabs`
- `plg_content_componentbuildercomponentheaderstabs`
- `plg_content_componentbuildercustomadminheaderstabs`
- `plg_content_componentbuilderdynamicgetheaderstabs`
- `plg_content_componentbuilderfieldorderingtabs`
- `plg_content_componentbuilderlanguagetabs`
- `plg_content_componentbuilderprivacytabs`
- `plg_content_componentbuildersiteheaderstabs`
- `plg_extension_componentbuilderactionlogcompiler`
- `plg_extension_componentbuilderexportcompiler`
- `plg_extension_componentbuilderfieldorderingcompiler`
- `plg_extension_componentbuilderheaderscompiler`
- `plg_extension_componentbuilderlanguagepackaging`
- `plg_extension_componentbuilderpowersautoloadercompiler`
- `plg_extension_componentbuilderprivacycompiler`
- `plg_webservices_componentbuilderapiroutes`

This is a distinction between deployable packages and authored bodies: some
bodies remain shareable, while native content-event signatures need the
separate-definition treatment described in the content migration note. A
Joomla 6 host can still compile Joomla 3–5 targets using the existing target
providers; the provider change does not alter those compiler services.

## Verification

All 18 service providers and installer scripts pass PHP 8.3 syntax checks.
`tests/PluginInfrastructureTest.php` passes four tests with 842 assertions on
PHP 8.3.6 and Joomla 6.1.2. It boots every provider through Joomla's extension
loader and `PluginHelper`, loads every installer through
`InstallerAdapter::setupScriptfile()`, checks lifecycle results, dispatches
API route and console events, and rejects deprecations during provider and
installer loading. The route assertions retain authenticated defaults and
the GUID CRUD routes; the console assertions verify command registration.

This tests native loader contracts and event behavior without installing a
live JCB site. It does not replace staging installation and end-to-end
component compilation.
