<?php

/** File: includes/core/init.php
 * Text Domain: bp-characters
 * version 2.0.0
 * @author greghacke
 * Function: Init helper functionality for the plugin
 */

defined('ABSPATH') || exit;

/** --- Require each helper file once --- */
require_once __DIR__ . '/activation.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/logging.php';
require_once __DIR__ . '/webhook-router.php';
