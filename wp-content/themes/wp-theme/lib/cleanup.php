<?php

// Remove things WordPress adds to every page that this theme doesn't use.
// jQuery is left registered: nothing on the front end loads it, but plugins that need it still can.

// Emoji detection script and styles (browsers render emoji natively)
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');
remove_action('admin_enqueue_scripts', 'wp_enqueue_emoji_styles');
remove_filter('the_content_feed', 'wp_staticize_emoji');
remove_filter('comment_text_rss', 'wp_staticize_emoji');
remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
add_filter('emoji_svg_url', '__return_false');
add_filter('tiny_mce_plugins', function ($plugins) {
  return array_diff((array) $plugins, ['wpemoji']);
});

// Block editor styles (the theme uses Classic Editor + ACF components)
add_action('wp_enqueue_scripts', function () {
  wp_dequeue_style('wp-block-library');
  wp_dequeue_style('wp-block-library-theme');
  wp_dequeue_style('classic-theme-styles');
  wp_dequeue_style('global-styles');
}, 100);
remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');

// <head> tags nobody needs: version number, legacy editing clients, shortlink, oEmbed discovery
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'wp_oembed_add_host_js');
remove_action('template_redirect', 'rest_output_link_header', 11);
remove_action('template_redirect', 'wp_shortlink_header', 11);
add_filter('the_generator', '__return_empty_string');

// Comments are disabled, so drop the comments feed (the posts feed stays)
add_filter('feed_links_show_comments_feed', '__return_false');

// Pingbacks: no X-Pingback header
add_filter('wp_headers', function ($headers) {
  unset($headers['X-Pingback']);
  return $headers;
});
