# Plugin regression tests

The suite runs the maintained plugin PHP and form XML directly. It verifies
native event dispatch, form fields and editor configuration, privacy item and
global-setting mutations, cache notifications, provider wiring, installer
contracts, and compilation of every plugin body through the JCB Joomla 6
extension renderer and Joomla Power injector.

The compiled content classes are also dispatched through Joomla's real event
dispatcher with their original form XML in the generated directory structure.
This verifies that code generation preserves working handlers, field/editor
changes and the Privacy Tabs item mutation. These fixtures are extracted from
the supplied PHP; they do not claim to be a complete maintained database export.
The companion compiler suite exercises `[[[API_ROUTES_METHOD]]]` generation,
placeholder expansion and native route dispatch. Keep that placeholder in the
maintained API Routes definition instead of copying generated methods back.

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

These focused regressions do not replace compilation and installation from the
complete maintained JCB definition package. Copy the reviewed source changes
back to those definitions before compiling JCB, keeping the older plugin
definitions for Joomla 4/5 as documented in the migration notes.
