<?php
// A post as a card: image, category, title, excerpt, date and reading time.
// Usage: get_template_part('components/global/post-card/post-card', null, ['post' => $post_id]);
$post_id = $args['post'] ?? get_the_ID();
$category = get_the_category($post_id)[0] ?? null;
$reading_time = (int) get_post_meta($post_id, 'reading_time', true);
?>

<article class="group relative flex flex-col overflow-hidden rounded-xl bg-white text-ink ring-1 ring-ink/10 transition hover:shadow-lg dark:bg-cream/5 dark:text-cream dark:ring-cream/10">
  <div class="aspect-video overflow-hidden bg-cream dark:bg-cream/10">
    <?php if (has_post_thumbnail($post_id)) : ?>
    <?= get_img(get_post_thumbnail_id($post_id), '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw', ['class' => 'h-full w-full object-cover transition duration-300 group-hover:scale-105', 'alt' => '']) ?>
    <?php endif; ?>
  </div>

  <div class="flex flex-1 flex-col p-6">
    <?php if ($category) : ?>
    <p class="text-sm font-semibold text-green"><?= esc_html($category->name) ?></p>
    <?php endif; ?>

    <h3 class="mt-2 text-xl font-bold leading-snug">
      <a href="<?= esc_url(get_permalink($post_id)) ?>" class="focus:outline-hidden">
        <span class="absolute inset-0" aria-hidden="true"></span>
        <?= esc_html(get_the_title($post_id)) ?>
      </a>
    </h3>

    <p class="mt-3 flex-1 text-ink/70 dark:text-cream/70"><?= esc_html(get_the_excerpt($post_id)) ?></p>

    <p class="mt-6 flex items-center gap-2 text-sm text-ink/60 dark:text-cream/60">
      <time datetime="<?= esc_attr(get_the_date('c', $post_id)) ?>"><?= esc_html(get_the_date('', $post_id)) ?></time>
      <?php if ($reading_time) : ?>
      <span aria-hidden="true">&middot;</span>
      <span><?= $reading_time ?> min read</span>
      <?php endif; ?>
    </p>
  </div>
</article>
