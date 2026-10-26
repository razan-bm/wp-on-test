<?php
$pages = paginate_links_custom(get_query_var('url_append', ''));
$colors = 'border-green text-green';
?>

<?php if ($pages) : ?>
<nav class="border-t border-gray-200 px-4 flex items-center justify-between sm:px-0">
  <div class="-mt-px w-0 flex-1 flex">
    <?php if ($pages['prev']) : ?>
    <a href="<?= $pages['prev'] ?>"
      class="border-t-2 border-transparent pt-4 pr-1 inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
      <div class="mr-3 h-5 w-5 text-gray-400">
        <?= get_icon('arrow-narrow-left') ?>
      </div>
      Previous
    </a>
    <?php endif; ?>

  </div>
  <div class="hidden md:-mt-px md:flex">
    <?php foreach ($pages['numbers'] as $num) : ?>
    <?php if ($num['type'] == 'num') : ?>
    <?php $class = $num['current'] ? $colors : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>
    <a href="<?= $num['current'] ? 'javascript:void(0);' : $num['link'] ?>"
      class="<?= $class ?> border-t-2 pt-4 px-4 inline-flex items-center text-sm font-medium"
      <?= $num['current'] ? 'aria-current="page"' : '' ?>>
      <?= $num['num'] ?>
    </a>
    <?php endif; ?>
    <?php if ($num['type'] == 'dots') : ?>
    <span class="border-transparent text-gray-500 border-t-2 pt-4 px-4 inline-flex items-center text-sm font-medium">
      ...
    </span>
    <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <div class="-mt-px w-0 flex-1 flex justify-end">
    <?php if ($pages['next']) : ?>
    <a href="<?= $pages['next'] ?>"
      class="border-t-2 border-transparent pt-4 pl-1 inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
      Next
      <div class="ml-3 h-5 w-5 text-gray-400">
        <?= get_icon('arrow-narrow-right') ?>
      </div>
    </a>
    <?php endif; ?>
  </div>
</nav>
<?php endif; ?>