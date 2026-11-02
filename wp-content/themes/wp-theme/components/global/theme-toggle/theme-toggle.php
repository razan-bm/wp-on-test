<?php
// Light / dark switch. The saved choice is applied before paint by the inline script in header.php.
?>
<button type="button"
  class="rounded-md p-2 inline-flex items-center justify-center text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:text-cream/70 dark:hover:text-cream dark:hover:bg-cream/10 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-green"
  x-data="{ dark: document.documentElement.classList.contains('dark') }"
  @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); try { localStorage.setItem('theme', dark ? 'dark' : 'light') } catch (e) {}"
  :aria-pressed="dark.toString()" aria-pressed="false">
  <span class="sr-only">Dark mode</span>
  <span class="block size-6 dark:hidden"><?= get_icon('moon') ?></span>
  <span class="hidden size-6 dark:block"><?= get_icon('sun') ?></span>
</button>
