<?php

/** File: includes/init.php
 * Text Domain: bp-characters
 * version 2.0.0
 * @author greghacke
 * Function:  Porvide a single entry point to load all plugin components in standard and class-based structure
 */

defined('ABSPATH') || exit;

// Load plugin core bootstraps first
require_once dirname(__FILE__) . '/core/init.php';

// Load helper functions
require_once __DIR__ . '/helper/init.php';

// Load field definitions
require_once __DIR__ . '/fields.php';

// Load admin components
require_once __DIR__ . '/admin/init.php';

// Load rendering components
require_once __DIR__ . '/render/init.php';

// Load shortcodes
require_once __DIR__ . '/shortcodes/init.php';

// Load utilities
require_once __DIR__ . '/utils/init.php';

// Autoload classes (optional PSR-like loader for 'classes/' dir)
$classes = glob(__DIR__ . '/classes/*.php');
if ($classes) {
    foreach ($classes as $class_file) {
        require_once $class_file;
    }
}
