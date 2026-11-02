<?php
// Fake content for `wp components seed`. Shows the latest posts, so create some first:
// wp post generate --count=6, or FakerPress > Posts
return function ($faker, $image) {
  $blog = (int) get_option('page_for_posts');

  return [
    'title_content' => [
      'title' => 'Latest from the blog',
      'heading_level' => 'h2',
      'content' => '<p>' . $faker->sentence(12) . '</p>',
    ],
    'source' => 'latest',
    'number_of_posts' => 3,
    'add_button' => $blog ? 1 : 0,
    'button' => $blog ? [
      ['button' => [
        'button_text' => 'View all posts',
        'button_type' => 'internal',
        'page_link' => $blog,
        'button_display_type' => 'link',
      ]],
    ] : [],
    'columns' => '3',
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
