<?php
// Fake content for `wp components seed` (see lib/seed-components.php)
return function ($faker, $image) {
  $pages = get_posts(['post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids']);

  return [
    'desktop_background_image' => $image(1920, 1080),
    'mobile_background_image' => $image(800, 1200),
    'title_content' => [
      'title' => rtrim($faker->sentence(5), '.'),
      'heading_level' => 'h1',
      'content' => '<p>' . $faker->paragraph(2) . '</p>',
    ],
    'add_button' => 1,
    'button' => [
      ['button' => [
        'button_text' => 'Get started',
        'button_type' => 'internal',
        'page_link' => $pages ? $faker->randomElement($pages) : '',
        'button_display_type' => 'primary',
      ]],
      ['button' => [
        'button_text' => 'Contact us',
        'button_type' => 'mail',
        'link_email' => $faker->safeEmail(),
        'button_display_type' => 'secondary',
      ]],
    ],
    'layout' => [
      'background' => 'ink',
      'text_color' => 'cream',
      'spacing_top' => 'lg',
      'spacing_bottom' => 'lg',
    ],
    'settings' => [
      'section_id' => '',
      'hide_section' => 0,
    ],
  ];
};
