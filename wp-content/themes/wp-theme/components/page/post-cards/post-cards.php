<?php
$title_content = $fields['title_content'] ?? [];
$heading = in_array($title_content['heading_level'] ?? '', ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $title_content['heading_level'] : 'h2';
$button = !empty($fields['add_button']) ? ($fields['button'][0]['button'] ?? []) : [];
$source = $fields['source'] ?? 'latest';

$query = [
  'post_type' => 'post',
  'post_status' => 'publish',
  'ignore_sticky_posts' => true,
  'no_found_rows' => true,
  'fields' => 'ids',
  'posts_per_page' => max(1, (int) ($fields['number_of_posts'] ?? 3)),
  'post__not_in' => [get_the_ID()],
];
if ($source === 'category' && !empty($fields['category'])) {
  $query['cat'] = (int) $fields['category'];
}
if ($source === 'manual') {
  $query['post__in'] = $fields['posts'] ?: [0];
  $query['orderby'] = 'post__in';
  $query['posts_per_page'] = count($query['post__in']);
}
$post_ids = get_posts($query);

// Full class names so Tailwind can find them
$columns = [
  '2' => 'sm:grid-cols-2',
  '3' => 'sm:grid-cols-2 lg:grid-cols-3',
  '4' => 'sm:grid-cols-2 lg:grid-cols-4',
];
$grid = $columns[$fields['columns'] ?? ''] ?? $columns['3'];

if (!$post_ids) {
  return;
}
?>

<section <?= get_section_attributes($fields, 'post-cards') ?>>
  <div class="container">
    <?php if (!empty($title_content['title']) || !empty($title_content['content'])) : ?>
    <div class="max-w-2xl">
      <?php if (!empty($title_content['title'])) : ?>
      <<?= $heading ?> class="text-3xl font-bold tracking-tight sm:text-4xl">
        <?= esc_html($title_content['title']) ?>
      </<?= $heading ?>>
      <?php endif; ?>

      <?php if (!empty($title_content['content'])) : ?>
      <div class="prose prose-lg mt-4">
        <?= $title_content['content'] ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="mt-10 grid gap-8 <?= $grid ?>">
      <?php foreach ($post_ids as $post_id) : ?>
      <?php get_template_part('components/global/post-card/post-card', null, ['post' => $post_id]); ?>
      <?php endforeach; ?>
    </div>

    <?php if ($button) : ?>
    <div class="mt-10">
      <?= get_button($button) ?>
    </div>
    <?php endif; ?>
  </div>
</section>
