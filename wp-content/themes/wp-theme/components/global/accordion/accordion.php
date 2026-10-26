<?php
global $accordion_index;
if (!isset($accordion_index)) {
  $accordion_index = 0;
}
$accordion_index += 1;
if (!isset($fields['open'])) {
  $fields['open'] = false;
}
$open = $fields['open'] ? 'true' : 'false';
?>

<div class="border-b border-black">
  <dt>
    <button type="button"
      class="bg-gray w-full pt-4 pb-3 focus:outline-none text-lg 4k:text-xl font-subheading uppercase text-left transition-colors hover:text-gray-dark"
      @click="index = <?= $fields['index'] ?>; allCollapsed = false" aria-controls="accordion-<?= $accordion_index ?>"
      x-bind:aria-expanded="index == <?= $fields['index'] ?> ? 'true' : 'false'">
      <?= $fields['title'] ?>
    </button>
  </dt>
  <dd id="accordion-<?= $accordion_index ?>" x-show="index == <?= $fields['index'] ?> && !allCollapsed"
    x-collapse.duration.500ms x-cloak>
    <div class="py-4 border-t border-black">
      <div class="prose max-w-full text-gray">
        <?= $fields['content'] ?>
      </div>
    </div>
  </dd>
</div>