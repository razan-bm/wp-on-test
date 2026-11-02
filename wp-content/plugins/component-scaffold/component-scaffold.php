<?php
/**
 * Plugin Name: Component Scaffold
 * Description: WP-CLI command to generate ACF page components: `wp scaffold component <name>`.
 * Version:     1.0.0
 * Author:      Rajan BM
 * License:     GPLv2 or later
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
  exit;
}

if (!defined('WP_CLI') || !WP_CLI) {
  return;
}

require_once __DIR__ . '/includes/class-component-scaffold-command.php';

WP_CLI::add_command('scaffold component', 'Component_Scaffold_Command', [
  'before_invoke' => function () {
    if (!function_exists('acf_get_field_groups')) {
      WP_CLI::error('ACF / Secure Custom Fields must be active.');
    }
  },
]);
