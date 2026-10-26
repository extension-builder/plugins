# Joomla 6 native plugin migration

This change prepares the plugins in this repository for native Joomla 6
compilation and installation. It is paired with the compiler regression repair
in `extension-builder/joomla`.

The maintained JCB plugin definitions remain the source of truth. Copy authored
handler changes back into those definitions before rebuilding JCB. Keep generated
content such as `[[[API_ROUTES_METHOD]]]` in its existing placeholder: the compiler
owns its expansion. Installing a compiler update alone cannot change an existing
plugin's authored `main_class_code`.

## Scope

- Migrate content form handlers to the native Joomla event contract.
- Use native plugin construction, explicit subscriptions, and installer discovery.
- Preserve JCB's custom compiler event contracts and generated Joomla 3–5 output.
- Exercise the actual plugin source and forms in regression tests.
- Record compatibility and the exact changes to transfer back into JCB.

## Transfer into JCB

1. Apply the reviewed handler and import changes to the nine content plugin
   definitions listed in [content-plugin-migration.md](content-plugin-migration.md).
   Retain Joomla Powers and their existing import/alias resolution when editing
   the maintained definitions. The resolved imports here are generated PHP.
2. Keep `[[[API_ROUTES_METHOD]]]` in the API Routes definition. The companion
   compiler generates the target-specific route handler and, for Joomla 6,
   its native subscriber contract. Do not paste the generated route table or
   another `getSubscribedEvents()` method into the maintained definition. An
   intentionally authored custom subscription map remains authoritative.
3. Copy the changed Privacy Compiler emitters and `isJoomlaSixTarget()` method
   described in [extension-plugin-migration.md](extension-plugin-migration.md).
   They select native APIs for generated target 6 only.
4. Regenerate Joomla 6 providers and installers with the repaired compiler.
   [infrastructure-migration.md](infrastructure-migration.md) identifies their
   native contract. These generated files do not belong in `main_class_code`.
5. Apply the companion [compiler PR](https://github.com/extension-builder/joomla/pull/61),
   compile the complete JCB definition package, and test the resulting component
   with these linked plugins before merging the release.

Installing these plugin files does not update the database-backed definitions
used by a subsequent JCB compilation. Compiler-owned placeholders are regenerated
from the compiler; authored handler migrations still require the copy-back above.

## Older Joomla versions

All 18 deployable packages on this branch require Joomla 6. Keep separate
generated Joomla 4/5 packages; their existing compiler targets retain their own
providers and installers. Do not copy these generated infrastructure files into
an older Joomla installation.

The nine content handlers require separate Joomla 4 definitions because their
concrete event classes were introduced in Joomla 5. Their event APIs also work
on Joomla 5, so this does not itself require separate Joomla 5 and 6 handler
bodies. The Privacy Compiler emitter keeps its existing Joomla 3–5 output.
The other compiler hooks keep their existing explicit maps and positional,
by-reference JCB contract. The detailed notes distinguish authored definitions
from deployable packages; no new definition-versioning mechanism is introduced.

## Validation

The test suite uses the actual plugin classes, service providers, installer
scripts, and form XML against the pinned Joomla runtime. It covers behavior
instead of accepting the former compilation exception as proof of completion.
The renderer regression now dispatches native events into all nine compiled
content plugin classes using their unchanged form assets. It checks field/editor
behavior and Privacy Tabs' before-save item mutation after code generation.
See [tests/README.md](../tests/README.md) for the reproducible commands and CI.

Local validation passes 48 tests / 1,354 assertions on PHP 8.3.6 with Joomla
6.1.2, plus PHP syntax, Bash syntax and whitespace checks. The workflow repeats
the plugin suite on PHP 8.3 and 8.4. This change does not claim a complete JCB
self-compilation: that still needs the maintainer's full definition package.
