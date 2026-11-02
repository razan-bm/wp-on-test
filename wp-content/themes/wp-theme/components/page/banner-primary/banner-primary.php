<?php
$title_content = $fields['title_content'] ?? [];
$heading = in_array($title_content['heading_level'] ?? '', ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $title_content['heading_level'] : 'h2';
$desktop_image = $fields['desktop_background_image'] ?? '';
$mobile_image = ($fields['mobile_background_image'] ?? '') ?: $desktop_image;
$buttons = !empty($fields['add_button']) ? ($fields['button'] ?? []) : [];
?>

<section <?= get_section_attributes($fields, 'banner-primary relative isolate overflow-hidden') ?>>
  <?php if ($desktop_image) : ?>
  <div class="absolute inset-0 -z-10">
    <div class="h-full md:hidden"><?= get_img($mobile_image, '100vw', ['alt' => '']) ?></div>
    <div class="hidden h-full md:block"><?= get_img($desktop_image, '100vw', ['alt' => '']) ?></div>
    <div class="absolute inset-0 bg-ink/50"></div>
  </div>
  <?php endif; ?>

  <div class="container">
    <div class="max-w-3xl">
      <?php if (!empty($title_content['title'])) : ?>
      <<?= $heading ?> class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
        <?= esc_html($title_content['title']) ?>
      </<?= $heading ?>>
      <?php endif; ?>

      <?php if (!empty($title_content['content'])) : ?>
      <div class="prose prose-lg mt-6 text-current [--tw-prose-body:currentColor] [--tw-prose-headings:currentColor]">
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
  </div>
</section>
