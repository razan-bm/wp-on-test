<?php
// Fake content for `wp components seed`: a few H2/H3 sections so the table of contents has something to list
return function ($faker, $image) {
  $content = '<p>' . $faker->paragraph(4) . '</p>';
  for ($i = 0; $i < 4; $i++) {
    $content .= '<h2>' . rtrim($faker->sentence(3), '.') . '</h2>';
    $content .= '<p>' . $faker->paragraph(5) . '</p>';
    if ($i === 1) {
      $content .= '<ul><li>' . implode('</li><li>', $faker->sentences(3)) . '</li></ul>';
    }
    $content .= '<h3>' . rtrim($faker->sentence(4), '.') . '</h3>';
    $content .= '<p>' . $faker->paragraph(4) . '</p>';
    if ($i === 2) {
      $content .= '<blockquote><p>' . $faker->sentence(16) . '</p></blockquote>';
    }
  }
  $content .= '<p><a class="btn-primary" href="' . esc_url(home_url('/')) . '">Back to home</a> <a class="btn-secondary" href="mailto:' . $faker->safeEmail() . '">Email us</a></p>';

  return [
    'content' => $content,
    'show_toc' => 1,
    'toc_title' => 'On this page',
    'width' => 'narrow',
    'layout' => [
      'background' => 'white',
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
