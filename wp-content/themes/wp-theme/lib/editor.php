<?php

// Make the Classic Editor and ACF WYSIWYG fields look like the front end:
// load the theme CSS inside TinyMCE and style the content with prose.

add_filter('mce_css', function ($css) {
  $path = get_stylesheet_directory() . '/dist/manifest.json';
  if (!file_exists($path)) {
    return $css;
  }
  $manifest = json_decode(file_get_contents($path), true);
  $url = get_template_directory_uri() . '/dist/' . $manifest['main.css'];
  return $css ? "$css,$url" : $url;
});

add_filter('tiny_mce_before_init', function ($init) {
  $init['body_class'] = trim(($init['body_class'] ?? '') . ' prose prose-lg max-w-none');
  // WordPress's own editor CSS sets a serif font and isn't in a CSS layer, so it beats the theme's body rule
  $init['content_style'] = ($init['content_style'] ?? '') . ' body#tinymce { padding: 1rem 1.5rem; font-family: var(--font-body); color: var(--color-ink); }';

  // Formats dropdown: select a link, then pick a button style
  $init['style_formats'] = wp_json_encode([
    ['title' => 'Button Primary', 'selector' => 'a', 'classes' => 'btn-primary'],
    ['title' => 'Button Secondary', 'selector' => 'a', 'classes' => 'btn-secondary'],
    ['title' => 'Button Link', 'selector' => 'a', 'classes' => 'btn-link'],
  ]);
  return $init;
});

// Add the Formats dropdown to the first toolbar row, after Paragraph/Heading
// (ACF's Full toolbar uses the same filter)
add_filter('mce_buttons', function ($buttons) {
  $index = array_search('formatselect', $buttons, true);
  array_splice($buttons, $index === false ? 0 : $index + 1, 0, 'styleselect');
  return $buttons;
});

add_filter('acf/fields/wysiwyg/toolbars', function ($toolbars) {
  if (isset($toolbars['Basic'][1])) {
    $toolbars['Basic'][1][] = 'styleselect';
  }
  return $toolbars;
});
