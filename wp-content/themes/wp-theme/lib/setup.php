<?php

// Register menu locations
add_action('after_setup_theme', 'register_primary_menus');
function register_primary_menus() {
  register_nav_menus(array(
    'header' => 'Header',
    'footer' => 'Footer',
  ));
}

// Theme setup
add_action('after_setup_theme', 'add_theme_supports');
function add_theme_supports() {
  add_theme_support('post-thumbnails');
}

add_image_size('480w', '480');
add_image_size('640w', '640');
add_image_size('750w', '750');
add_image_size('828w', '828');
add_image_size('1080w', '1080');
add_image_size('1200w', '1200');
add_image_size('1920w', '1920');
add_image_size('2048w', '2048');
add_image_size('3840w', '3840');

add_filter('intermediate_image_sizes_advanced', 'remove_image_sizes', 10, 2);
function remove_image_sizes($sizes, $metadata) {
  unset($sizes['medium_large']);
  unset($sizes['large']);
  unset($sizes['1536x1536']);
  unset($sizes['2048x2048']);
  return $sizes;
}

// Give editor role capability to edit theme (e.g. menus)
$editor = get_role('editor');
$editor->add_cap('edit_theme_options');

add_filter('excerpt_length', 'custom_excerpt_length');
function custom_excerpt_length($length) {
  return 30;
}

add_filter('excerpt_more', 'custom_excerpt_more');
function custom_excerpt_more($more) {
  return '...';
}

// Add the "Site Config" & "Components" settings page in the wordpress admin dashboard
if (class_exists('ACF')) {
  acf_add_options_page(array(
    'page_title' => __('Site Configuration'),
    'menu_title' => __('Site Configuration'),
    'redirect' => false,
  ));

  acf_add_options_page(array(
    'page_title' => __('Global Components'),
    'menu_title' => __('Global Components'),
    'redirect' => false,
    'icon_url' => 'dashicons-screenoptions'
  ));
}

// Give editor role capability to edit theme (e.g. menus)
$editor = get_role('editor');
$editor->add_cap('edit_theme_options');

// Prevent Yoas Seo plugin from pinging google (100s of times) 
// to recrawl the sitemap.xml when syncing acf fields from json
add_action('admin_init', 'prevent_yoast_from_pinging_google_on_acf_field_sync', 10, 0);
function prevent_yoast_from_pinging_google_on_acf_field_sync() {
  if (isset($_GET['acfsync'])) {
    add_filter('wpseo_allow_xml_sitemap_ping', '__return_false');
  }
}

// Save reading_time metadata on post update
add_action('save_post_post', 'update_post_reading_time', 10, 2);
function update_post_reading_time($post_ID, $post) {
  $average_words_read_per_minute = 200;
  $word_count = str_word_count(strip_tags($post->post_content));
  $reading_time = ceil($word_count / $average_words_read_per_minute);
  update_post_meta($post_ID, 'reading_time', $reading_time);
}

add_filter('embed_oembed_html', 'wrap_embed_with_div');
function wrap_embed_with_div($html) {
  return '<div class="iframe-container">' . $html . '</div>';
}
