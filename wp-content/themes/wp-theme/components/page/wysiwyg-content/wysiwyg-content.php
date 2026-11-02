<?php
$toc = !empty($fields['show_toc']) ? get_content_toc($fields['content'] ?? '') : null;
$content = $toc ? $toc['content'] : ($fields['content'] ?? '');
$headings = $toc['headings'] ?? [];
$toc_title = ($fields['toc_title'] ?? '') ?: 'On this page';
$narrow = ($fields['width'] ?? 'narrow') !== 'wide';

if (!trim($content)) {
  return;
}
?>

<section <?= get_section_attributes($fields, 'wysiwyg-content') ?>>
  <div class="container <?= $headings ? 'lg:grid lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-16' : '' ?>">
    <?php if ($headings) : ?>
    <nav aria-label="<?= esc_attr($toc_title) ?>" class="mb-10 lg:mb-0" x-data="Components.toc()">
      <div class="lg:sticky lg:top-28" x-data="{ open: false }">
        <button type="button"
          class="flex w-full items-center justify-between rounded-lg border border-current/15 px-4 py-3 font-heading font-semibold lg:hidden"
          @click="open = !open" :aria-expanded="open.toString()" aria-expanded="false">
          <?= esc_html($toc_title) ?>
          <span class="size-5 transition" :class="{ 'rotate-180': open }"><?= get_icon('chevron-down', 'solid') ?></span>
        </button>
        <p class="hidden font-heading text-sm font-semibold uppercase tracking-wider opacity-60 lg:block"><?= esc_html($toc_title) ?></p>

        <div class="max-lg:hidden" :class="{ 'max-lg:hidden': !open }">
          <ol class="mt-3 space-y-1 border-l border-current/15 text-sm lg:mt-4">
            <?php foreach ($headings as $heading) : ?>
            <li>
              <a href="#<?= esc_attr($heading['id']) ?>" @click="open = false"
                class="-ml-px block border-l-2 border-transparent py-1.5 opacity-70 transition hover:opacity-100 <?= $heading['level'] === 3 ? 'pl-8' : 'pl-4' ?>"
                :class="{ 'border-green! opacity-100! font-semibold': active === '<?= esc_js($heading['id']) ?>' }">
                <?= esc_html($heading['text']) ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ol>
        </div>
      </div>
    </nav>
    <?php endif; ?>

    <div class="prose prose-lg <?= $narrow ? 'max-w-3xl' : 'max-w-none' ?> <?= $narrow && !$headings ? 'mx-auto' : '' ?>">
      <?= $content ?>
    </div>
  </div>
</section>
