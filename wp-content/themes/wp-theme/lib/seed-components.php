<?php

/**
 * Fill pages with fake page components for development.
 *
 * Each component can have a seed.php next to its template that returns
 * function ($faker, $image) { return [ ...field values... ]; }
 * where $image($width, $height) downloads a placeholder image and returns its attachment ID.
 *
 *   wp components seed                       # fill the homepage with every seedable component
 *   wp components seed --page=12             # fill another page
 *   wp components seed --components=banner_primary,cta --yes
 *   wp components create-page                # new page with fake title and components
 *   wp components create-page --count=5 --publish
 *   wp components delete-seed-pages          # remove pages made by create-page
 *   wp components delete-seed-images         # remove the downloaded placeholder images
 *
 * Needs the FakerPress plugin, which bundles the Faker library.
 */

if (!defined('WP_CLI') || !WP_CLI) {
  return;
}

class Seed_Components_Command {
  const IMAGE_META = '_component_seed_image';
  const PAGE_META = '_component_seed_page';

  /**
   * Replace a page's components with fake content.
   *
   * ## OPTIONS
   *
   * [--page=<id>]
   * : Page ID to fill. Defaults to the homepage.
   *
   * [--components=<names>]
   * : Comma separated layout names, in order. Defaults to every component with a seed.php.
   *
   * [--[no-]images]
   * : Download placeholder images. Use --no-images to skip them.
   * ---
   * default: true
   * ---
   *
   * [--yes]
   * : Overwrite existing components without asking.
   */
  public function seed($args, $assoc_args) {
    $page_id = (int) ($assoc_args['page'] ?? get_option('page_on_front'));
    if (!$page_id || get_post_type($page_id) !== 'page') {
      WP_CLI::error('No page to fill. Pass --page=<id> or set a static homepage.');
    }

    $names = $this->get_component_names($assoc_args);

    $existing = get_field('page_components', $page_id) ?: [];
    if ($existing) {
      WP_CLI::confirm(sprintf('"%s" already has %d component(s). Replace them?', get_the_title($page_id), count($existing)), $assoc_args);
    }

    $count = $this->fill_page($page_id, $names, $this->get_faker(), \WP_CLI\Utils\get_flag_value($assoc_args, 'images', true));
    WP_CLI::success(sprintf('Filled "%s" with %d component(s): %s', get_the_title($page_id), $count, get_permalink($page_id)));
  }

  /**
   * Create new pages filled with fake components.
   *
   * ## OPTIONS
   *
   * [--count=<count>]
   * : How many pages to create.
   * ---
   * default: 1
   * ---
   *
   * [--title=<title>]
   * : Page title. Defaults to a fake one. With --count, a number is added.
   *
   * [--components=<names>]
   * : Comma separated layout names, in order. Defaults to every component with a seed.php.
   *
   * [--parent=<id>]
   * : Parent page ID.
   *
   * [--publish]
   * : Publish the pages. They are drafts by default.
   *
   * [--[no-]images]
   * : Download placeholder images. Use --no-images to skip them.
   * ---
   * default: true
   * ---
   *
   * ## EXAMPLES
   *
   *     wp components create-page
   *     wp components create-page --count=3 --publish --no-images
   *     wp components create-page --title="Style Guide" --components=banner_primary
   *
   * @subcommand create-page
   */
  public function create_page($args, $assoc_args) {
    $names = $this->get_component_names($assoc_args);
    $faker = $this->get_faker();
    $count = max(1, (int) $assoc_args['count']);
    $with_images = \WP_CLI\Utils\get_flag_value($assoc_args, 'images', true);

    for ($i = 1; $i <= $count; $i++) {
      $title = $assoc_args['title'] ?? rtrim($faker->sentence(3), '.');
      if (isset($assoc_args['title']) && $count > 1) {
        $title .= " $i";
      }
      $page_id = wp_insert_post([
        'post_type' => 'page',
        'post_title' => $title,
        'post_status' => isset($assoc_args['publish']) ? 'publish' : 'draft',
        'post_parent' => (int) ($assoc_args['parent'] ?? 0),
        'meta_input' => [self::PAGE_META => 1],
      ], true);
      if (is_wp_error($page_id)) {
        WP_CLI::error($page_id);
      }

      WP_CLI::log("\"$title\" (#$page_id)");
      $this->fill_page($page_id, $names, $faker, $with_images);
      WP_CLI::log('  ' . (isset($assoc_args['publish']) ? get_permalink($page_id) : get_preview_post_link($page_id)));
    }

    WP_CLI::success(sprintf('Created %d page(s). Remove them later with `wp components delete-seed-pages`.', $count));
  }

  /**
   * Delete pages made by `wp components create-page`.
   *
   * ## OPTIONS
   *
   * [--with-images]
   * : Also delete the placeholder images attached to them.
   *
   * [--yes]
   * : Don't ask for confirmation.
   *
   * @subcommand delete-seed-pages
   */
  public function delete_seed_pages($args, $assoc_args) {
    $ids = get_posts(['post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => self::PAGE_META]);
    if (!$ids) {
      WP_CLI::success('No seed pages to delete.');
      return;
    }
    WP_CLI::confirm(sprintf('Delete %d seed page(s) permanently?', count($ids)), $assoc_args);

    $images = 0;
    foreach ($ids as $id) {
      if (isset($assoc_args['with-images'])) {
        foreach (get_posts(['post_type' => 'attachment', 'post_parent' => $id, 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => self::IMAGE_META]) as $image_id) {
          wp_delete_attachment($image_id, true);
          $images++;
        }
      }
      wp_delete_post($id, true);
    }
    WP_CLI::success(sprintf('Deleted %d seed page(s)%s.', count($ids), isset($assoc_args['with-images']) ? " and $images image(s)" : ''));
  }

  /**
   * Delete placeholder images downloaded by `wp components seed`.
   *
   * @subcommand delete-seed-images
   */
  public function delete_seed_images() {
    $ids = get_posts(['post_type' => 'attachment', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => self::IMAGE_META]);
    foreach ($ids as $id) {
      wp_delete_attachment($id, true);
    }
    WP_CLI::success(sprintf('Deleted %d seed image(s).', count($ids)));
  }

  /**
   * Layout names to seed, from --components or every component with a seed.php
   */
  private function get_component_names($assoc_args) {
    $seeds = $this->get_seeds();
    $names = empty($assoc_args['components']) ? array_keys($seeds) : array_map('trim', explode(',', $assoc_args['components']));
    if (!$names) {
      WP_CLI::error('No components have a seed.php yet.');
    }
    foreach ($names as $name) {
      if (!isset($seeds[$name])) {
        WP_CLI::error("No seed.php for component '$name'. Seedable: " . implode(', ', array_keys($seeds)));
      }
    }
    return $names;
  }

  /**
   * Replace a page's components with fake content, returns how many were added
   */
  private function fill_page($page_id, $names, $faker, $with_images) {
    $seeds = $this->get_seeds();
    $image = fn($width, $height) => $with_images ? $this->download_image($width, $height, $page_id) : '';

    $rows = [];
    foreach ($names as $name) {
      $rows[] = ['acf_fc_layout' => $name] + (require $seeds[$name])($faker, $image);
      WP_CLI::log("  + $name");
    }
    update_field('page_components', $rows, $page_id);
    return count($rows);
  }

  private function get_seeds() {
    $seeds = [];
    foreach (get_page_components() as $name => $component) {
      $file = get_template_directory() . '/components/page/' . str_replace('_', '-', $name) . '/seed.php';
      if (is_file($file)) {
        $seeds[$name] = $file;
      }
    }
    return $seeds;
  }

  private function get_faker() {
    $class = '\FakerPress\ThirdParty\Faker\Factory';
    if (!class_exists($class) && is_file(WP_PLUGIN_DIR . '/fakerpress/vendor/autoload.php')) {
      require_once WP_PLUGIN_DIR . '/fakerpress/vendor/autoload.php';
    }
    if (!class_exists($class)) {
      WP_CLI::error('Faker not found. Install the FakerPress plugin.');
    }
    return $class::create();
  }

  private function download_image($width, $height, $page_id) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url("https://picsum.photos/{$width}/{$height}.jpg");
    if (is_wp_error($tmp)) {
      WP_CLI::warning('Could not download a placeholder image: ' . $tmp->get_error_message());
      return '';
    }
    $id = media_handle_sideload(['name' => "seed-{$width}x{$height}-" . wp_generate_password(6, false) . '.jpg', 'tmp_name' => $tmp], $page_id);
    if (is_wp_error($id)) {
      @unlink($tmp);
      WP_CLI::warning('Could not save a placeholder image: ' . $id->get_error_message());
      return '';
    }
    update_post_meta($id, self::IMAGE_META, 1);
    return $id;
  }
}

WP_CLI::add_command('components', 'Seed_Components_Command');
