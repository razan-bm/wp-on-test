<?php
// Fake content for `wp components seed`
return function ($faker, $image) {
  $pages = get_posts(['post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids']);

  return [
    'title_content' => [
      'title' => rtrim($faker->sentence(4), '.'),
      'heading_level' => 'h1',
      'content' => '<p>' . $faker->paragraph(2) . '</p>',
    ],
    'add_button' => 1,
    'button' => [
      ['button' => [
        'button_text' => 'Learn more',
        'button_type' => 'internal',
        'page_link' => $pages ? $faker->randomElement($pages) : '',
        'button_display_type' => 'primary',
      ]],
    ],
    'image' => $image(1200, 900),
    'image_position' => 'right',
    'layout' => [
      'background' => 'cream',
      'text_color' => 'auto',
      'spacing_top' => 'md',
      'spacing_bottom' => 'md',
    ],
    'settings' => [
      'section_id' => '',
      'hide_section' => 0,
    ],
  ];
};
