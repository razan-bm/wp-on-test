<?php
// Fake content for `wp components seed`
return function ($faker, $image) {
  $pages = get_posts(['post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids']);

  return [
    'title_content' => [
      'title' => rtrim($faker->sentence(5), '.'),
      'heading_level' => 'h2',
      'content' => '<p>' . $faker->sentence(14) . '</p>',
    ],
    'button' => [
      ['button' => [
        'button_text' => 'Get in touch',
        'button_type' => 'mail',
        'link_email' => $faker->safeEmail(),
        'button_display_type' => 'primary',
      ]],
      ['button' => [
        'button_text' => 'See our work',
        'button_type' => 'internal',
        'page_link' => $pages ? $faker->randomElement($pages) : '',
        'button_display_type' => 'secondary',
      ]],
    ],
    'alignment' => $faker->randomElement(['center', 'split']),
    'layout' => [
      'background' => 'ink',
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
