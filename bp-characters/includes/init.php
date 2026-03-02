<?php
defined('ABSPATH') || exit;

require_once dirname(__FILE__) . '/core/init.php';
require_once __DIR__ . '/helper/init.php';
require_once __DIR__ . '/fields.php';
require_once __DIR__ . '/admin/init.php';
require_once __DIR__ . '/render/init.php';
require_once __DIR__ . '/shortcodes/init.php';
require_once __DIR__ . '/utils/init.php';

$classes = glob(__DIR__ . '/classes/*.php');
if ($classes) {
    foreach ($classes as $class_file) {
        require_once $class_file;
    }
}
