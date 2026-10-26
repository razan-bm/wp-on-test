<?php
/**
 * Plugin Name: Secure Custom Fields (MU loader)
 * Description: Loads Secure Custom Fields from mu-plugins/secure-custom-fields. WordPress does not load plugins in mu-plugins subdirectories automatically.
 */

defined( 'ABSPATH' ) || exit;

require_once WPMU_PLUGIN_DIR . '/secure-custom-fields/secure-custom-fields.php';
