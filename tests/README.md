# Plugin regression tests

The suite compiles authored plugin bodies/imports and copies their form XML
into temporary Joomla 6 packages before loading the resulting PHP. It verifies
native event dispatch, form fields/editor configuration, privacy item/global
settings, cache notifications, provider wiring and installer contracts. It uses
JCB's actual plugin loader, extension/header/provider/installer generators and
Joomla Power injector. Checked-in historical infrastructure is not executed as
the implementation under test.

The compiled content classes are also dispatched through Joomla's real event
dispatcher with their original form XML in the generated directory structure.
This verifies that code generation preserves working handlers, field/editor
changes and the Privacy Tabs item mutation. Inputs contain no generated default
subscription maps. Existing authored console/compiler maps are retained.
These focused fixtures use authored regions of the supplied PHP; they do not
claim to be a complete maintained database export. API input starts with
`[[[API_ROUTES_METHOD]]]` and representative component API settings, exercising
the real route generator and native dispatch. Keep that placeholder in the
maintained API Routes definition instead of copying generated methods back.
Installer inputs contain only existing cleanup metadata and the Console Commands
prerequisite snippet. No generic preflight/postflight additions are supplied.

The compiler, test support and Composer dependencies come from one reviewed JCB
checkout. This avoids maintaining a second copy of its dependency graph. CI
pins JCB at `eae8a18a91f781bccce915c8abe6d285a35cd235`, Joomla CMS at the 6.1.2
source revision, and installs JCB's committed Composer lock. The plugin code
under test always comes from the current checkout. Network access occurs only
during setup, never during a test.

With PHP 8.3 or 8.4 and Composer 2 installed, prepare the runtime once:

```bash
git clone --no-checkout https://github.com/extension-builder/joomla.git .runtime/jcb
git -C .runtime/jcb checkout eae8a18a91f781bccce915c8abe6d285a35cd235
git clone --branch 6.1.2 --depth 1 https://github.com/joomla/joomla-cms.git \
  .runtime/jcb/libraries/vendor_jcb/tests/.runtime/joomla-cms
composer --working-dir=.runtime/jcb/libraries/vendor_jcb/tests install
bash tests/run.sh
```

An already prepared JCB test project can be used while developing both PRs:

```bash
JCB_TEST_PROJECT=/absolute/path/to/joomla/libraries/vendor_jcb/tests bash tests/run.sh
```

PHP deprecations, including suppressed deprecations from old CMSPlugin
construction, fail the suite. External application/database/cache boundaries
are mocked; Joomla's event classes, dispatcher, forms, table and compiler
renderers are real. The tests do not boot or write to an installed Joomla site.

During definition loading, the pinned Joomla 6.1.2 date formatter calls its own
deprecated language accessor twice. Fixture preparation records and asserts
only that exact core message and source file; every other deprecation remains
an error. Generated provider/installer loading and plugin event execution have
no such exception. Installer cleanup tests use isolated temporary roots and
verify real deletion plus the Console prerequisite's abort-before-cleanup behavior.

These focused regressions do not replace compilation and installation from the
complete maintained JCB definition package. Copy the reviewed source changes
back to those definitions before compiling JCB, keeping the older plugin
definitions for Joomla 4/5 as documented in the migration notes.
