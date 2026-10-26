<!doctype html>
<html <?php language_attributes(); ?>>

<head>
  <meta charset="<?php bloginfo('charset'); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="<?= bloginfo('description') ?>">
  <title><?php wp_title('-', true, 'right'); ?> <?php bloginfo('name'); ?></title>
  <link rel="profile" href="https://gmpg.org/xfn/11" />
  <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">
  <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
  <?php wp_body_open(); ?>
  <header>
    <div id="header" class="top-0 inset-x-0 z-20 bg-white shadow-md"
      x-data="Components.popover({ open: false, focus: true })" x-init="init()" @keydown.escape="onEscape"
      @close-popover-group.window="onClosePopoverGroup">
      <div class="flex justify-between items-center container md:justify-start md:space-x-10">
        <div class="flex justify-start lg:w-0 lg:flex-1">
          <a href="<?= home_url() ?>">
            <span class="sr-only"><?= bloginfo('name') ?> Logo</span>
            <img class="w-auto max-h-16 md:max-h-24" src="<?= get_img_url('logo.png') ?>"
              alt="<?= bloginfo('name') ?> Logo">
          </a>
        </div>
        <div class="-mr-2 -my-2 md:hidden">
          <button type="button"
            class="bg-white rounded-md p-2 inline-flex items-center justify-center text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-green"
            @click="toggle" @mousedown="if (open) $event.preventDefault()" aria-expanded="false"
            :aria-expanded="open.toString()">
            <span class="sr-only">Open menu</span>
            <div class="h-6 w-6"><?= get_icon('menu') ?></div>
          </button>
        </div>
        <nav class="hidden md:flex space-x-8" x-data="Components.popoverGroup()" x-init="init()">
          <?php ob_start(); ?>
          <div class="ml-2 h-5 w-5">
            <?= get_icon('chevron-down', 'solid') ?>
          </div>
          <?php $button_after = ob_get_clean(); ?>

          <?php
          wp_nav_menu(array(
            'theme_location' => 'header',
            'a_class' => 'text-base px-2 py-2 font-medium text-gray-500 hover:text-green focus:text-green',
            'a_active_class' => 'text-green',
            'button_container_class' => 'relative',
            'button_class' => 'group px-2 py-2 text-gray-500 bg-white rounded-md inline-flex items-center text-base font-medium hover:text-green focus:text-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green',
            'button_active_class' => 'text-green',
            'sub_menu_class' => 'absolute z-20 -ml-4 mt-3 transform w-screen max-w-sm rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 overflow-hidden bg-white py-3',
            'sub_menu_a_class' => 'block px-5 py-3 hover:text-white hover:bg-green',
            'sub_menu_a_active_class' => 'text-green',
            'sub_menu_1_a_class' => 'pl-10',
            'button_after' => $button_after,
          ));
          ?>
        </nav>
      </div>
      <div class="fixed z-30 top-0 inset-x-0 p-2 transition transform origin-top-right md:hidden" x-show="open" x-cloak
        x-transition:enter="duration-200 ease-out" x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100" x-transition:leave="duration-100 ease-in"
        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-ref="panel"
        @click.away="open = false">
        <div class="shadow-lg ring-1 ring-black ring-opacity-5 bg-white">
          <div class="flex items-center justify-end pt-5 px-5">
            <div class="-mr-2">
              <button type="button"
                class="bg-white rounded-md p-2 inline-flex items-center justify-center text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-green"
                @click="toggle">
                <span class="sr-only">Close menu</span>
                <div class="h-6 w-6">
                  <?= get_icon('x') ?>
                </div>
              </button>
            </div>
          </div>
          <div class="p-2 pb-3 ">
            <nav class="space-y-1">
              <?php ob_start(); ?>
              <div class="ml-2 h-5 w-5 transform" :class="{ 'rotate-180' : open }">
                <?= get_icon('chevron-down', 'solid') ?>
              </div>
              <?php $button_after = ob_get_clean(); ?>

              <?php
              wp_nav_menu(array(
                'theme_location' => 'header',
                'a_class' => 'block py-2 px-8 text-base font-semibold font-semibold text-navy',
                'a_active_class' => 'text-green',
                'button_container_class' => 'relative',
                'button_class' => 'flex items-center w-full py-2 px-8 text-base font-semibold text-navy',
                'button_active_class' => 'text-green',
                'sub_menu_class' => 'pl-4',
                'sub_menu_a_class' => 'block px-8 py-2 text-base text-navy',
                'sub_menu_a_active_class' => 'text-green',
                'button_after' => $button_after,
                'type' => 'mobile'
              ));
              ?>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </header>
  <main>