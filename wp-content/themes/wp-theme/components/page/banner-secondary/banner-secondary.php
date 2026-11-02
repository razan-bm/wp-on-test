<?php
$title_content = $fields['title_content'] ?? [];
$heading = in_array($title_content['heading_level'] ?? '', ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $title_content['heading_level'] : 'h2';
$buttons = !empty($fields['add_button']) ? ($fields['button'] ?? []) : [];
$image = $fields['image'] ?? '';
$image_left = ($fields['image_position'] ?? '') === 'left';
?>

<section <?= get_section_attributes($fields, 'banner-secondary') ?>>
  <div class="container <?= $image ? 'grid items-center gap-10 lg:grid-cols-2 lg:gap-16' : '' ?>">
    <div class="max-w-3xl <?= $image && $image_left ? 'lg:order-last' : '' ?>">
      <?php if (!empty($title_content['title'])) : ?>
      <<?= $heading ?> class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
        <?= esc_html($title_content['title']) ?>
      </<?= $heading ?>>
      <?php endif; ?>

      <?php if (!empty($title_content['content'])) : ?>
      <div class="prose prose-lg mt-5">
        <?= $title_content['content'] ?>
      </div>
      <?php endif; ?>

      <?php if ($buttons) : ?>
      <div class="mt-8 flex flex-wrap gap-4">
        <?php foreach ($buttons as $row) : ?>
        <?= get_button($row['button'] ?? []) ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($image) : ?>
    <div class="aspect-4/3 overflow-hidden rounded-xl">
      <?= get_img($image, '(min-width: 1024px) 50vw, 100vw') ?>
    </div>
    <?php endif; ?>
  </div>
</section>
