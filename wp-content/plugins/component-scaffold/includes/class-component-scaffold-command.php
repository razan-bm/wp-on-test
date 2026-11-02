<?php

/**
 * Generates a page component folder:
 *
 *   components/page/<name>/
 *     <name>.json   ACF field group "Component: <Label>" with Content / Layout / Settings tabs
 *     <name>.php    front-end template
 *     seed.php      fake content for `wp components seed` (if the theme has it)
 *     preview.svg   placeholder shown in the "Add Component" picker
 *
 * Defaults come from the active theme and can be changed with the `component_scaffold/config` filter.
 * A theme can replace any stub by adding component-scaffold/<stub> (e.g. template.php.stub) to the theme.
 */
class Component_Scaffold_Command {

  /**
   * Create a new page component.
   *
   * ## OPTIONS
   *
   * <name>
   * : Component name, e.g. cards-grid or "Cards Grid".
   *
   * [--label=<label>]
   * : Name shown to editors. Defaults to the name in Title Case.
   *
   * [--category=<category>]
   * : Category slug for the "Add Component" picker.
   *
   * [--description=<description>]
   * : One line shown under the component in the picker.
   *
   * [--[no-]title-content]
   * : Add the reusable title & content fields to the Content tab. Use --no-title-content to leave them out.
   * ---
   * default: true
   * ---
   *
   * [--[no-]seed]
   * : Create seed.php. Use --no-seed to skip it.
   * ---
   * default: true
   * ---
   *
   * [--force]
   * : Overwrite files that already exist.
   *
   * ## EXAMPLES
   *
   *     wp scaffold component cards-grid --category=cards --description="Grid of cards with image, title and link."
   *     wp scaffold component "Logo Strip" --category=social-proof --no-title-content
   *
   * @when after_wp_load
   */
  public function __invoke($args, $assoc_args) {
    $config = $this->get_config();
    $slug = sanitize_title($args[0]);
    if (!$slug) {
      WP_CLI::error('Give the component a name, e.g. cards-grid.');
    }
    $name = str_replace('-', '_', $slug);
    $label = $assoc_args['label'] ?? ucwords(str_replace('-', ' ', $slug));
    $category = $assoc_args['category'] ?? '';
    $description = $assoc_args['description'] ?? '';
    $force = \WP_CLI\Utils\get_flag_value($assoc_args, 'force', false);

    if ($category && $config['categories'] && !isset($config['categories'][$category])) {
      WP_CLI::error("Unknown category '$category'. Use one of: " . implode(', ', array_keys($config['categories'])));
    }
    if (!$category && $config['categories']) {
      WP_CLI::warning('No --category given, it will show under "Other". Categories: ' . implode(', ', array_keys($config['categories'])));
    }

    $title = $config['prefix'] . $label;
    foreach (acf_get_field_groups() as $group) {
      if ($group['title'] === $title && !$force) {
        WP_CLI::error("A field group called \"$title\" already exists.");
      }
    }

    $dir = trailingslashit($config['path']) . $slug;
    $reusable = $this->find_reusable_fields($config['reusable_group'], ['title_content', 'layout', 'settings']);
    if (\WP_CLI\Utils\get_flag_value($assoc_args, 'title-content', true) === false) {
      unset($reusable['title_content']);
    }

    $replace = [
      '{{slug}}' => $slug,
      '{{name}}' => $name,
      '{{label}}' => $label,
      '{{label_php}}' => var_export($label, true),
      '{{label_xml}}' => esc_html($label),
    ];

    $files = [
      "$slug.json" => wp_json_encode($this->build_field_group($title, $description, $category, $reusable), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
      "$slug.php" => strtr($this->get_stub(isset($reusable['title_content']) ? 'template.php.stub' : 'template-blank.php.stub'), $replace),
      'preview.svg' => strtr($this->get_stub('preview.svg.stub'), $replace),
    ];
    if ($config['seed'] && \WP_CLI\Utils\get_flag_value($assoc_args, 'seed', true)) {
      $files['seed.php'] = strtr($this->get_stub(isset($reusable['title_content']) ? 'seed.php.stub' : 'seed-blank.php.stub'), $replace);
    }

    if (!wp_mkdir_p($dir)) {
      WP_CLI::error("Could not create $dir");
    }
    foreach ($files as $file => $contents) {
      $path = "$dir/$file";
      if (file_exists($path) && !$force) {
        WP_CLI::warning("Skipped $file (exists, use --force to overwrite)");
        continue;
      }
      file_put_contents($path, $contents);
      WP_CLI::log('  created ' . str_replace(ABSPATH, '', $path));
    }

    WP_CLI::success("Created \"$label\". Add its fields in ACF > Field Groups > $title, then build $slug.php.");
  }

  /**
   * Theme defaults, overridable with the component_scaffold/config filter.
   */
  private function get_config() {
    return apply_filters('component_scaffold/config', [
      'path' => get_template_directory() . '/components/page',
      'prefix' => defined('PAGE_COMPONENT_PREFIX') ? PAGE_COMPONENT_PREFIX : 'Component: ',
      'categories' => defined('PAGE_COMPONENT_CATEGORIES') ? PAGE_COMPONENT_CATEGORIES : [],
      'reusable_group' => 'Reusable Components',
      'seed' => class_exists('Seed_Components_Command'),
    ]);
  }

  /**
   * Find the reusable group fields (by name) to clone into the new component.
   */
  private function find_reusable_fields($group_title, $names) {
    $found = [];
    foreach (acf_get_field_groups() as $group) {
      if ($group['title'] !== $group_title) {
        continue;
      }
      foreach (acf_get_fields($group) as $field) {
        if (in_array($field['name'], $names, true)) {
          $found[$field['name']] = $field['key'];
        }
      }
    }
    foreach (array_diff($names, array_keys($found)) as $missing) {
      WP_CLI::warning("No reusable '$missing' field found in \"$group_title\", leaving it out.");
    }
    return $found;
  }

  private function build_field_group($title, $description, $category, $reusable) {
    $fields = [$this->tab('Content')];
    if (isset($reusable['title_content'])) {
      $fields[] = $this->clone_field('Title Content', 'title_content', $reusable['title_content']);
    }
    if (isset($reusable['layout'])) {
      $fields[] = $this->tab('Layout');
      $fields[] = $this->clone_field('Layout', 'layout', $reusable['layout']);
    }
    if (isset($reusable['settings'])) {
      $fields[] = $this->tab('Settings');
      $fields[] = $this->clone_field('Settings', 'settings', $reusable['settings']);
    }

    $group = [
      'key' => $this->key('group_'),
      'title' => $title,
      'fields' => $fields,
      // Inactive with a harmless location: the group is only used through Page Components
      'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'post']]],
      'menu_order' => 0,
      'position' => 'normal',
      'style' => 'default',
      'label_placement' => 'top',
      'instruction_placement' => 'label',
      'hide_on_screen' => '',
      'active' => false,
      'description' => $description,
      'show_in_rest' => 0,
      'modified' => time(),
    ];
    if ($category) {
      $group['component_category'] = $category;
    }
    return $group;
  }

  private function tab($label) {
    return [
      'key' => $this->key(),
      'label' => $label,
      'name' => '',
      'type' => 'tab',
      'placement' => 'top',
      'endpoint' => 0,
    ];
  }

  private function clone_field($label, $name, $clone_key) {
    return [
      'key' => $this->key(),
      'label' => $label,
      'name' => $name,
      'type' => 'clone',
      'clone' => [$clone_key],
      'display' => 'seamless',
      'layout' => 'block',
      'prefix_label' => 0,
      'prefix_name' => 0,
    ];
  }

  private function key($prefix = 'field_') {
    return $prefix . substr(md5(uniqid('', true)), 0, 13);
  }

  private function get_stub($file) {
    $theme_stub = get_template_directory() . "/component-scaffold/$file";
    return file_get_contents(is_file($theme_stub) ? $theme_stub : dirname(__DIR__) . "/templates/$file");
  }
}
