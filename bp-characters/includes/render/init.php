<?php

/** File: includes/render/init.php
 * Text Domain: bp-characters
 * version 2.0.0
 * @author greghacke
 * Function: Init helper functionality for the plugin
 */

defined('ABSPATH') || exit;

/** --- Require each helper file once --- */
require_once __DIR__ . '/render-admin.php';
require_once __DIR__ . '/render-user.php';
