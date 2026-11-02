<?php

/**
 * Repeat an array n times.
 * Useful for creating dummy data out of only a few items
 */
function array_repeat($array, $n) {
  $result = [];
  $eCount = count($array);
  for ($j = 0; $j < $n; $j++) {
    for ($i = 0; $i < $eCount; $i++) {
      $result[] = $array[$i];
    }
  }
  return $result;
}
;

/**
 * Error log variable of any type
 */
if (!function_exists("elog")) {
  function elog($var) {
    error_log(print_r($var, true));
  }
}

/**
 * Dump a variable of any type with pre formatting
 */
function vdump($var) {
  echo '<pre>';
  var_dump($var);
  echo '</pre>';
}


function get_svg($svg_name) {
  return file_get_contents(get_stylesheet_directory() . '/src/img/svgs/' . $svg_name . '.svg');
}

function get_icon($icon_name, $type = 'outline') {
  return get_svg("heroicons/$type/$icon_name");
}

function get_social_icon($icon_name) {
  return get_svg('socials/' . $icon_name);
}

function json_encode_alpine($data) {
  return str_replace('"', "'", json_encode($data));
}
function get_img($image_id, $sizes, $atts = []) {
  if (!isset($atts['sizes'])) {
    $atts['sizes'] = $sizes;
  }
  if (!isset($atts['class'])) {
    $atts['class'] = 'h-full w-full object-cover';
  }
  return wp_get_attachment_image($image_id, 'full', false, $atts);
}


function get_img_url($img_name) {
  return get_template_directory_uri() . '/src/img/' . $img_name;
}

function get_component_path($component_path) {
  return get_template_directory() . "/components/$component_path.php";
}

function get_page_component_path($component_name) {
  return get_component_path("page/$component_name/$component_name");
}

/**
 * Build the id and class attributes for a page component's <section>
 * from its "Layout" and "Settings" tabs (see Reusable Components).
 * Usage: <section <?= get_section_attributes($fields, 'banner-primary') ?>>
 */
function get_section_attributes($fields, $class = '') {
  $layout = $fields['layout'] ?? [];
  $settings = $fields['settings'] ?? [];
  $backgrounds = [
    'white' => 'bg-white',
    'cream' => 'bg-cream',
    'green' => 'bg-green',
    'ink' => 'bg-ink',
  ];
  $text_colors = [
    'ink' => 'text-ink',
    'cream' => 'text-cream',
  ];
  // Light backgrounds switch to ink in dark mode, cream keeps a slightly lighter tint
  $dark_backgrounds = [
    'white' => 'dark:bg-ink',
    'cream' => 'dark:bg-cream/5',
  ];
  // "auto" picks a readable text color for the background
  $auto_text = ['ink' => 'cream'];
  $background = isset($backgrounds[$layout['background'] ?? '']) ? $layout['background'] : 'white';
  $text = $layout['text_color'] ?? 'auto';
  $text = isset($text_colors[$text]) ? $text : ($auto_text[$background] ?? 'ink');
  // Full class names so Tailwind can find them
  $spacing_top = ['none' => 'pt-0', 'sm' => 'pt-8 lg:pt-12', 'md' => 'pt-12 lg:pt-20', 'lg' => 'pt-16 lg:pt-32'];
  $spacing_bottom = ['none' => 'pb-0', 'sm' => 'pb-8 lg:pb-12', 'md' => 'pb-12 lg:pb-20', 'lg' => 'pb-16 lg:pb-32'];

  $classes = array_filter([
    $class,
    $backgrounds[$background],
    $dark_backgrounds[$background] ?? '',
    $text_colors[$text],
    isset($dark_backgrounds[$background]) && $text === 'ink' ? 'dark:text-cream' : '',
    $spacing_top[$layout['spacing_top'] ?? ''] ?? $spacing_top['md'],
    $spacing_bottom[$layout['spacing_bottom'] ?? ''] ?? $spacing_bottom['md'],
  ]);

  $atts = 'class="' . esc_attr(implode(' ', $classes)) . '"';
  if (!empty($settings['section_id'])) {
    $atts = 'id="' . esc_attr(sanitize_title($settings['section_id'])) . '" ' . $atts;
  }
  return $atts;
}

/**
 * Button from the reusable "button" group as a string (see components/global/button/button.php)
 */
function get_button($button, $class = '') {
  ob_start();
  get_template_part('components/global/button/button', null, ['button' => $button, 'class' => $class]);
  return ob_get_clean();
}

function paginate_links_custom($url_append = '', $end_size = 1, $mid_size = 2, $prev_next = true) {
  $page_var = is_front_page() ? 'page' : 'paged';
  $big = 9999999;
  $args = array(
    'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big)) . $url_append),
    'format' => '?paged=%#%',
    'total' => get_query_var('max_num_pages') ?? 1,
    'current' => max(1, get_query_var($page_var)),

    'prev_next' => $prev_next,
    'end_size' => $end_size,
    'mid_size' => $mid_size,
  );


  // Who knows what else people pass in $args.
  $total = (int) $args['total'];
  if ($total < 2) {
    return;
  }
  $current = $args['current'];

  $page_links = array();
  $page_links['prev'] = false;
  $page_links['next'] = false;
  $page_links['numbers'] = array();
  $dots = false;

  if ($args['prev_next'] && $current && $current > 1):
    $link = str_replace('%_%', 2 == $current ? '' : $args['format'], $args['base']);
    $link = str_replace('%#%', $current - 1, $link);
    $link = esc_url(apply_filters('paginate_links', $link));
    $page_links['prev'] = $link;
  endif;

  for ($n = 1; $n <= $total; $n++):
    if ($n == $current):
      $page_links['numbers'][] = array(
        'type' => 'num',
        'current' => true,
        'num' => number_format_i18n($n),
      );
      $dots = true;
    else:
      if (($n <= $end_size || ($current && $n >= $current - $mid_size && $n <= $current + $mid_size) || $n > $total - $end_size)):
        $link = str_replace('%_%', 1 == $n ? '' : $args['format'], $args['base']);
        $link = str_replace('%#%', $n, $link);
        $link = esc_url(apply_filters('paginate_links', $link));
        $page_links['numbers'][] = array(
          'type' => 'num',
          'current' => false,
          'num' => number_format_i18n($n),
          'link' => $link
        );

        $dots = true;
      elseif ($dots):
        $page_links['numbers'][] = array(
          'type' => 'dots',
        );
        $dots = false;
      endif;
    endif;
  endfor;

  if ($args['prev_next'] && $current && $current < $total):
    $link = str_replace('%_%', $args['format'], $args['base']);
    $link = str_replace('%#%', $current + 1, $link);
    $link = esc_url(apply_filters('paginate_links', $link));
    $page_links['next'] = $link;
  endif;

  return $page_links;
}