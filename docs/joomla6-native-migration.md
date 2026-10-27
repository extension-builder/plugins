# Joomla 6 authored plugin migration

The maintained JCB definitions are the source of truth. This branch changes
authored methods and required imports in nine content plugins and the Privacy
Compiler plugin. The generated providers, installers, API route class and content
class declarations remain the original reference output. Compile installable
Joomla 6 packages after applying the authored changes to the maintained records.

The same ownership rule applies to every plugin: custom logic belongs in the
definition fields; shared class, provider and lifecycle structure belongs in JCB.

## Transfer into JCB

1. Follow the [exact field-by-field guide](plugin-definition-fields.md). Update
   the nine content plugins' native event methods and required header imports.
   Preserve existing Joomla Powers and import resolution. Property/method
   selections insert code into the editor; changing a catalogue entry alone does
   not update a previously stored plugin body.
2. Apply the Privacy Tabs save/cache methods and Privacy Compiler emitters listed
   there. The emitters select native APIs for target 6 while preserving targets
   3–5. Preserve the explicit console map; let JCB generate `jcb_ce_*`
   subscription maps from the compiler plugins' authored handlers.
3. Keep `[[[API_ROUTES_METHOD]]]` in the API Routes body. JCB expands the linked
   component's routes and generates the J6 subscriber contract. Do not add the
   generated route method, class declaration or inferred subscription map to the
   editable fields.
4. Leave existing preflight/postflight snippets alone. The Console Commands
   prerequisite remains its only relevant custom preflight action. JCB supplies
   lifecycle methods, initialization, compatibility checks and wrappers; none
   need to be copied into those fields.
5. Apply [compiler PR #61](https://github.com/extension-builder/joomla/pull/61),
   regenerate the packages from the maintained definitions, then install and
   exercise the generated Joomla 6 component with its linked plugins.

See [content-plugin-migration.md](content-plugin-migration.md),
[extension-plugin-migration.md](extension-plugin-migration.md), and
[infrastructure-migration.md](infrastructure-migration.md) for the contracts.
Installing reference PHP does not update JCB's database-backed definitions.

## Older Joomla versions

Preserve existing Joomla 4/5 distributions and compiler output. The nine content
handlers need separate Joomla 4 definitions because their concrete event classes
were introduced in Joomla 5. Their event APIs also exist in Joomla 5, but this
migration does not request changes to an existing Joomla 5 distribution.

Joomla 6 providers and native installer wrappers come from the J6 compiler. The
Joomla 3–5 generators remain separate. Privacy Compiler's target selection retains
its earlier-target output, and component-linked API tests compare complete Joomla
5 plugin output against the unchanged baseline.

## Validation boundary

The plugin suite compiles authored bodies and imports into temporary Joomla 6
classes and infrastructure before testing form mutations, cache behavior,
providers, native installers and event dispatch. Standard content subscription
maps and historical generated compiler-hook maps are absent from the inputs;
the deliberate console map remains, and the API input retains its route placeholder.
No generic lifecycle boilerplate is added to authored snippets.

The companion installed fixture stores the actual authored content body in a
disposable JCB database and compiles, installs and updates the resulting package.
These checks establish the exercised generation/runtime contracts. They do not
replace full JCB self-compilation from the complete maintained definition export,
which is not present in either repository. See [tests/README.md](../tests/README.md)
for the current test setup and evidence.
