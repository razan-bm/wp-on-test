<?php
$title_content = $fields['title_content'] ?? [];
$heading = in_array($title_content['heading_level'] ?? '', ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $title_content['heading_level'] : 'h2';
$buttons = $fields['button'] ?? [];
$split = ($fields['alignment'] ?? '') === 'split';
?>

<section <?= get_section_attributes($fields, 'cta-banner') ?>>
  <div class="container <?= $split ? 'flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between' : 'text-center' ?>">
    <div class="<?= $split ? 'max-w-2xl' : 'mx-auto max-w-2xl' ?>">
      <?php if (!empty($title_content['title'])) : ?>
      <<?= $heading ?> class="text-3xl font-bold tracking-tight sm:text-4xl">
        <?= esc_html($title_content['title']) ?>
      </<?= $heading ?>>
      <?php endif; ?>

      <?php if (!empty($title_content['content'])) : ?>
      <div class="prose prose-lg mt-4 <?= $split ? '' : 'mx-auto' ?>">
        <?= $title_content['content'] ?>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($buttons) : ?>
    <div class="flex shrink-0 flex-wrap gap-4 <?= $split ? '' : 'mt-8 justify-center' ?>">
      <?php foreach ($buttons as $row) : ?>
      <?= get_button($row['button'] ?? []) ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
