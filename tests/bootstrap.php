<?php
/**
 * @package    Joomla.Component.Builder.Tests
 * @created    26th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

define('JCB_PLUGINS_ROOT', dirname(__DIR__));
$testProject = getenv('JCB_TEST_PROJECT') ?: JCB_PLUGINS_ROOT . '/.runtime/jcb/libraries/vendor_jcb/tests';

if (!is_file($testProject . '/bootstrap.php'))
{
	throw new RuntimeException('Prepare the pinned JCB test runtime described in tests/README.md, or set JCB_TEST_PROJECT.');
}

require $testProject . '/bootstrap.php';

if (!defined('JVERSION'))
{
	define('JVERSION', (new Joomla\CMS\Version())->getShortVersion());
}

foreach (glob(JCB_PLUGINS_ROOT . '/plg_*/src/Extension/*.php') as $pluginFile)
{
	require_once $pluginFile;
}

require_once __DIR__ . '/PluginTestCase.php';
