</main>
<footer class="bg-ink text-cream">
  <div class="container py-12 lg:py-16">
    <div class="flex flex-col gap-8 md:flex-row md:items-start md:justify-between">
      <a href="<?= home_url() ?>" class="font-heading text-xl font-bold">
        <?= bloginfo('name') ?>
      </a>

      <?php if (has_nav_menu('footer')) : ?>
      <nav aria-label="Footer">
        <?php
        wp_nav_menu(array(
          'theme_location' => 'footer',
          'container' => false,
          'menu_class' => 'flex flex-wrap gap-x-8 gap-y-3',
          'a_class' => 'text-cream/80 transition hover:text-cream hover:underline underline-offset-4',
          'depth' => 1,
          'fallback_cb' => false,
        ));
        ?>
      </nav>
      <?php endif; ?>
    </div>

    <div class="mt-10 border-t border-cream/15 pt-6 text-sm text-cream/60">
      &copy; <?= date('Y') ?> <?= bloginfo('name') ?>. All rights reserved.
    </div>
  </div>
</footer>


<?php wp_footer(); ?>

</body>

</html>
