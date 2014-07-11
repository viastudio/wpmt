<?php
// set global configurations
define('WP_CONTENT_DIR', $_SERVER['DOCUMENT_ROOT'] . '/wp-content');
define('WP_CONTENT_URL', 'http://' . $_SERVER['SERVER_NAME'] . '/wp-content');

// load site-specific configurations
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/wp-config.php';
