<?php

// Makes urls with custom post type pagination, e.g. /resources/page/2
// query a page and not the post type (e.g. resource)
add_action('init', function () {
  add_rewrite_rule(
    '(.?.+?)/page/?([0-9]{1,})/?$',
    'index.php?pagename=$matches[1]&paged=$matches[2]',
    'top'
  );
});