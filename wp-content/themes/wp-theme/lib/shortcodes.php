<?php

// Add the colour shortcode
// Example: This is some [colour "Orange"]coloured text[/colour]
add_shortcode('colour', 'add_shortcode_colour');
function add_shortcode_colour($atts, $content = '') {
  $colour_field = get_field_object('field_60481fc01cbe5');
  if ($colour_field) {
    $colours = $colour_field['choices'];
    $colour = $atts[0];
    $found_colour_class = false;
    foreach ($colours as $colour_class => $colour_label) {
      if ($colour == $colour_class || $colour == $colour_label) {
        $found_colour_class = $colour_class;
        break;
      }
    }
    if ($found_colour_class) {
      return "<span class=\"text-$found_colour_class\">$content</span>";
    }
  }
  return $content;
}
