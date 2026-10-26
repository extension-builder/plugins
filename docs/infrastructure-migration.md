# Joomla 6 plugin infrastructure generation

JCB owns infrastructure for all 18 plugins. This branch changes only authored
plugin methods and their required imports; the existing `services/provider.php`,
`script.php`, API route class and generated content class declarations remain the
original reference output. They are not manually upgraded Joomla 6 packages.
Apply the [field changes](plugin-definition-fields.md) to maintained JCB records
and compile the packages with the reviewed Joomla 6 compiler.

## Providers and class structure

`Compiler/Architecture/JoomlaSix/Plugin/Provider.php` already constructs the plugin
with its configuration array, injects the application, and incorporates services
selected by the plugin loader. The Language Packaging database injection comes
from that loader's database-trait detection. None of this belongs in a plugin's
authored methods or custom header.

`Compiler/JoomlaSix/Header.php` supplies the provider imports. The paired repair
removes its unused `DispatcherInterface` import; Joomla 3–5 retain their existing
headers and construction. The interface import itself did not invoke deprecated
behavior: the obsolete dispatcher-first constructor call was the runtime issue.

`Compiler/Architecture/JoomlaSix/Plugin/Extension.php` emits the class declaration
and `SubscriberInterface` through Joomla Powers. It generates default subscriptions
from eligible native `on…` handlers and retains deliberately authored maps. The
content definitions therefore need their event handlers and required imports,
not a manually added class declaration or generated subscription method.

## Installer lifecycle

`Compiler/Extension/JoomlaSix/InstallScript.php` generates the installer class,
`InstallerScriptInterface`, constructor, typed lifecycle methods, version check,
cleanup handling and returned instance. It incorporates the existing configured
hook bodies while retaining their application/adapter context and return behavior.

Do not add common initialization, version checks, lifecycle signatures, interface
methods or success-return boilerplate to preflight/postflight fields. These are
compiler responsibilities. Preserve the actual custom actions already configured
for each plugin. The Console Commands preflight-install prerequisite is unchanged;
the other plugins need no new preflight/postflight snippets for this migration.

## API and console plugins

Keep `[[[API_ROUTES_METHOD]]]` in the webservices plugin's authored body. JCB
generates routes from the linked component's API configuration, then infers the
Joomla 6 subscription. Do not paste the expanded route method or its inferred map
back into the definition. Joomla 5 generation stays unchanged.

The console plugin's `application.before_execute` map and command registration
are deliberately authored and remain unchanged. The seven compiler plugins also
retain their explicit JCB hook maps and positional/by-reference argument contracts.

## Verification and distributions

Tests generate extension classes, provider headers/bodies and installers through
the actual J6 compiler into temporary directories before exercising Joomla's
loader and event dispatch. They must not use manually upgraded infrastructure as
the implementation under test. The installed companion fixture likewise starts
with authored content methods and has JCB supply the subscriber and installer.

Generated Joomla 6 packages require Joomla 6. Keep existing Joomla 4/5 definitions
and distributions separate; do not distribute this branch's reference trees as
completed Joomla 6 builds. Full release regeneration still requires the maintained
JCB definition package, which is not included in these repositories.
