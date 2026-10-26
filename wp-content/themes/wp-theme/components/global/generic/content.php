<?php
global $fields;
// WP copies query vars into globals on singular views, so $fields can be the string 'all'
$fields = is_array($fields) ? $fields : [];
$fields['content'] = $fields['content'] ?? apply_filters('the_content', get_the_content());
?>

<section class="generic-content">
  <div class="container max-w-prose prose pt-6 pb-12 sm:pt-8 lg:pt-16 lg:pb-24">
    <?= $fields['content'] ?>
  </div>
</section>