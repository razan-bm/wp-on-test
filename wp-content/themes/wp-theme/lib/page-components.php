<?php

/**
 * Page components each live in their own folder: components/page/<name>/
 *   <name>.php    front-end template (included by page.php)
 *   <name>.json   ACF field group titled "Component: <Label>". Edit it in the ACF UI, it saves back here.
 *   preview.png   what the component looks like, shown in the "Add Component" picker (png, jpg, webp or svg)
 *
 * The "Page Components" flexible content field is built from these folders,
 * so adding a component is just adding a folder.
 */

const PAGE_COMPONENT_PREFIX = 'Component: ';

// Categories for grouping components in the "Add Component" picker, in display order
const PAGE_COMPONENT_CATEGORIES = [
  'banners' => 'Banners',
  'content' => 'Content',
  'media' => 'Media',
  'cards' => 'Cards & Grids',
  'cta' => 'Calls to Action',
  'social-proof' => 'Social Proof',
  'forms' => 'Forms',
  'other' => 'Other',
];

function get_page_component_dirs() {
  return glob(get_template_directory() . '/components/page/*', GLOB_ONLYDIR) ?: [];
}

/**
 * Folder name for a "Component: ..." field group, or '' for any other group
 */
function get_page_component_slug($field_group) {
  $title = $field_group['title'] ?? '';
  if (!str_starts_with($title, PAGE_COMPONENT_PREFIX)) {
    return '';
  }
  return sanitize_title(substr($title, strlen(PAGE_COMPONENT_PREFIX)));
}

/**
 * All components with a field group, keyed by layout name (banner_primary)
 */
function get_page_components() {
  static $components;
  if (isset($components)) {
    return $components;
  }

  $components = [];
  foreach (get_page_component_dirs() as $dir) {
    $slug = basename($dir);
    $json = is_file("$dir/$slug.json") ? json_decode(file_get_contents("$dir/$slug.json"), true) : null;
    if (empty($json['key'])) {
      continue;
    }

    $preview = '';
    foreach (['webp', 'png', 'jpg', 'jpeg', 'svg'] as $ext) {
      if (is_file("$dir/preview.$ext")) {
        $preview = get_template_directory_uri() . "/components/page/$slug/preview.$ext";
        break;
      }
    }

    $components[str_replace('-', '_', $slug)] = [
      'label' => substr($json['title'], strlen(PAGE_COMPONENT_PREFIX)),
      'description' => $json['description'] ?? '',
      'category' => isset(PAGE_COMPONENT_CATEGORIES[$json['component_category'] ?? '']) ? $json['component_category'] : 'other',
      'group' => $json['key'],
      'preview' => $preview,
    ];
  }
  return $components;
}

// Load component field groups from their folders
add_filter('acf/json/load_paths', function ($paths) {
  return array_merge($paths, get_page_component_dirs());
});

// Save component field groups next to their template, as <name>.json
add_filter('acf/json/save_paths', function ($paths, $post) {
  $slug = get_page_component_slug($post);
  if (!$slug) {
    return $paths;
  }
  $dir = get_template_directory() . "/components/page/$slug";
  wp_mkdir_p($dir);
  return [$dir];
}, 10, 2);

add_filter('acf/json/save_file_name', function ($filename, $post) {
  $slug = get_page_component_slug($post);
  return $slug ? "$slug.json" : $filename;
}, 10, 2);

// "Component Category" setting on field groups (General tab), saved into the component's JSON
add_action('acf/field_group/render_additional_group_settings', function ($field_group) {
  acf_render_field_wrap([
    'label' => 'Component Category',
    'instructions' => 'Groups this component in the "Add Component" picker. Only used by "Component: ..." field groups.',
    'type' => 'select',
    'name' => 'component_category',
    'prefix' => 'acf_field_group',
    'value' => $field_group['component_category'] ?? '',
    'choices' => PAGE_COMPONENT_CATEGORIES,
    'allow_null' => 1,
    'placeholder' => 'Other',
  ], 'div', 'field');
});

// SCF's seamless clone expansion (acf_field_clone::acf_get_fields) counts the fields once before
// splicing, so in a list with several clones only the first gets expanded. Page Components has one
// clone per layout, so keep running it until nothing is left to expand.
add_filter('acf/get_fields', function ($fields, $parent) {
  $clone = acf_get_field_type('clone');
  if (!$clone || !is_array($fields)) {
    return $fields;
  }
  do {
    $count = count($fields);
    $fields = $clone->acf_get_fields($fields, $parent);
  } while (count($fields) !== $count);
  return $fields;
}, 6, 2);

// Build the "Page Components" field, one layout per component folder
add_action('acf/include_fields', function () {
  $layouts = [];
  foreach (get_page_components() as $name => $component) {
    $layouts["layout_$name"] = [
      'key' => "layout_$name",
      'name' => $name,
      'label' => $component['label'],
      'display' => 'block',
      'sub_fields' => [
        [
          'key' => "field_page_component_$name",
          'label' => $component['label'],
          'name' => $name,
          'type' => 'clone',
          'clone' => [$component['group']],
          'display' => 'seamless',
          'layout' => 'block',
          'prefix_label' => 0,
          'prefix_name' => 0,
        ],
      ],
      'min' => '',
      'max' => '',
    ];
  }

  acf_add_local_field_group([
    'key' => 'group_6154d0ae1f802',
    'title' => 'Page Components',
    'fields' => [
      [
        'key' => 'field_6154d0b263b04',
        'label' => 'Page Components',
        'name' => 'page_components',
        'type' => 'flexible_content',
        'layouts' => $layouts,
        'button_label' => 'Add Component',
      ],
    ],
    'location' => [
      [
        ['param' => 'post_type', 'operator' => '==', 'value' => 'page'],
      ],
    ],
    'hide_on_screen' => ['the_content', 'excerpt', 'discussion', 'comments', 'slug', 'author', 'format', 'page_attributes', 'featured_image', 'categories', 'tags', 'send-trackbacks'],
  ]);
});

// Replace the small "Add Component" popup with a full-screen picker showing every component,
// grouped by category, with its preview. ACF's own popup still opens (hidden), and picking a card
// clicks its link, so ACF keeps handling min/max limits and where the new layout is inserted.
add_action('acf/input/admin_footer', function () {
  $components = array_map(fn($component) => [
    'preview' => $component['preview'],
    'description' => $component['description'],
    'category' => $component['category'],
  ], get_page_components());
  if (!$components) {
    return;
  }
?>
<style>
  .acf-fc-popup.-page-components-hidden {
    visibility: hidden !important;
  }
  .page-components-picker {
    position: fixed;
    inset: 0;
    z-index: 160000;
    display: flex;
    flex-direction: column;
    background: #f6f7f7;
  }
  .page-components-picker__header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px 24px;
    background: #fff;
    border-bottom: 1px solid #dcdcde;
  }
  .page-components-picker__header h2 {
    margin: 0;
    font-size: 20px;
  }
  .page-components-picker__search {
    flex: 1;
    max-width: 360px;
    margin-left: auto !important;
  }
  .page-components-picker__close {
    padding: 4px 8px;
    font-size: 28px;
    line-height: 1;
    color: #50575e;
    background: none;
    border: 0;
    cursor: pointer;
  }
  .page-components-picker__close:hover {
    color: #1d2327;
  }
  .page-components-picker__main {
    display: flex;
    flex: 1;
    min-height: 0;
  }
  .page-components-picker__nav {
    flex: 0 0 220px;
    padding: 16px 12px;
    overflow-y: auto;
    background: #fff;
    border-right: 1px solid #dcdcde;
  }
  .page-components-picker__nav button {
    display: flex;
    justify-content: space-between;
    width: 100%;
    padding: 8px 12px;
    margin-bottom: 2px;
    font-size: 14px;
    color: #1d2327;
    text-align: left;
    background: none;
    border: 0;
    border-radius: 4px;
    cursor: pointer;
  }
  .page-components-picker__nav button:hover {
    background: #f0f0f1;
  }
  .page-components-picker__nav button.is-active {
    color: #fff;
    background: #2271b1;
  }
  .page-components-picker__nav .count {
    opacity: 0.7;
  }
  .page-components-picker__content {
    flex: 1;
    padding: 8px 32px 32px;
    overflow-y: auto;
  }
  .page-components-picker__section h3 {
    margin: 24px 0 12px;
    font-size: 15px;
    color: #1d2327;
  }
  .page-components-picker__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
  }
  .page-components-picker__card {
    display: flex;
    flex-direction: column;
    padding: 0;
    overflow: hidden;
    text-align: left;
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 6px;
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s;
  }
  .page-components-picker__card:hover,
  .page-components-picker__card:focus-visible {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
    outline: none;
  }
  .page-components-picker__card.disabled {
    opacity: 0.45;
    cursor: not-allowed;
  }
  .page-components-picker__image {
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 2 / 1;
    color: #8c8f94;
    background: #f0f0f1;
    border-bottom: 1px solid #dcdcde;
  }
  .page-components-picker__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  .page-components-picker__body {
    padding: 12px 14px 14px;
  }
  .page-components-picker__label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #1d2327;
  }
  .page-components-picker__description {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: #646970;
  }
  .page-components-picker__empty {
    margin: 48px 0;
    text-align: center;
    color: #646970;
  }
  @media (max-width: 782px) {
    .page-components-picker__nav {
      display: none;
    }
    .page-components-picker__content {
      padding: 8px 16px 24px;
    }
  }
</style>
<script type="text/javascript">
(function($) {
  var components = <?= wp_json_encode($components) ?>;
  var categories = <?= wp_json_encode(PAGE_COMPONENT_CATEGORIES) ?>;
  var $picker = null;
  var $popup = null;

  function close() {
    if (!$picker) return;
    $picker.remove();
    $picker = null;
    $('body').css('overflow', '');
    $(document).off('keydown.pageComponents');
  }

  // Closing without choosing also cancels ACF's hidden popup
  function cancel() {
    var $hiddenPopup = $popup;
    close();
    if ($hiddenPopup) {
      $('<a href="#" data-event="cancel" hidden></a>').appendTo($hiddenPopup).trigger('click');
    }
  }

  function card($link) {
    var component = components[$link.data('layout')] || {};
    var $card = $('<button type="button" class="page-components-picker__card">')
      .toggleClass('disabled', $link.hasClass('disabled'))
      .attr('title', $link.attr('title') || '')
      .data('link', $link);
    var $image = $('<span class="page-components-picker__image">');
    if (component.preview) {
      $image.append($('<img alt="" loading="lazy">').attr('src', component.preview));
    } else {
      $image.text('No preview');
    }
    var $body = $('<span class="page-components-picker__body">')
      .append($('<span class="page-components-picker__label">').text($link.text().trim()));
    if (component.description) {
      $body.append($('<span class="page-components-picker__description">').text(component.description));
    }
    return $card.append($image, $body);
  }

  function filter() {
    var category = $picker.find('.page-components-picker__nav .is-active').data('category');
    var query = $picker.find('.page-components-picker__search').val().toLowerCase().trim();
    $picker.find('.page-components-picker__card').each(function() {
      var $card = $(this);
      var inCategory = category === 'all' || $card.closest('.page-components-picker__section').data('category') === category;
      var matches = !query || $card.text().toLowerCase().indexOf(query) !== -1;
      $card.prop('hidden', !(inCategory && matches));
    });
    $picker.find('.page-components-picker__section').each(function() {
      $(this).prop('hidden', !$(this).find('.page-components-picker__card:not([hidden])').length);
    });
    $picker.find('.page-components-picker__empty').prop('hidden', $picker.find('.page-components-picker__card:not([hidden])').length > 0);
  }

  function open($hiddenPopup) {
    $popup = $hiddenPopup.addClass('-page-components-hidden');

    $picker = $(
      '<div class="page-components-picker" role="dialog" aria-modal="true" aria-label="Add Component">' +
        '<div class="page-components-picker__header">' +
          '<h2>Add Component</h2>' +
          '<input type="search" class="page-components-picker__search" placeholder="Search components…">' +
          '<button type="button" class="page-components-picker__close" aria-label="Close">&times;</button>' +
        '</div>' +
        '<div class="page-components-picker__main">' +
          '<nav class="page-components-picker__nav"></nav>' +
          '<div class="page-components-picker__content"></div>' +
        '</div>' +
      '</div>'
    );
    var $nav = $picker.find('.page-components-picker__nav');
    var $content = $picker.find('.page-components-picker__content');

    // Group the popup's links by category, in category order
    var byCategory = {};
    $popup.find('[data-layout]').each(function() {
      var category = (components[$(this).data('layout')] || {}).category || 'other';
      (byCategory[category] = byCategory[category] || []).push($(this));
    });

    var total = 0;
    $.each(categories, function(slug, label) {
      var links = byCategory[slug];
      if (!links) return;
      total += links.length;
      var $grid = $('<div class="page-components-picker__grid">').append(links.map(card));
      $content.append(
        $('<section class="page-components-picker__section">').data('category', slug)
          .append($('<h3>').text(label), $grid)
      );
      $nav.append(
        $('<button type="button">').data('category', slug)
          .append($('<span>').text(label), $('<span class="count">').text(links.length))
      );
    });
    $nav.prepend(
      $('<button type="button" class="is-active">').data('category', 'all')
        .append($('<span>').text('All components'), $('<span class="count">').text(total))
    );
    $content.append('<p class="page-components-picker__empty" hidden>No components match your search.</p>');

    // Keep clicks inside the picker away from ACF's "click anywhere to cancel" handler
    $picker.on('click', function(e) {
      e.stopPropagation();
    });
    $picker.on('click', '.page-components-picker__close', cancel);
    $picker.on('click', '.page-components-picker__nav button', function() {
      $nav.find('button').removeClass('is-active');
      $(this).addClass('is-active');
      $content.scrollTop(0);
      filter();
    });
    $picker.on('input', '.page-components-picker__search', filter);
    $picker.on('click', '.page-components-picker__card', function() {
      if ($(this).hasClass('disabled')) return;
      var $link = $(this).data('link');
      close();
      $link.trigger('click');
    });
    $(document).on('keydown.pageComponents', function(e) {
      if (e.key === 'Escape') cancel();
    });

    $('body').append($picker).css('overflow', 'hidden');
    $picker.find('.page-components-picker__search').trigger('focus');
  }

  // Swap in the picker whenever ACF opens a popup listing page components
  new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      $(mutation.addedNodes).filter('.acf-fc-popup:not(.acf-more-layout-actions)').each(function() {
        var isPageComponents = $(this).find('[data-layout]').toArray().some(function(link) {
          return components.hasOwnProperty($(link).data('layout'));
        });
        if (isPageComponents) open($(this));
      });
      if ($popup && $(mutation.removedNodes).is($popup)) {
        close();
        $popup = null;
      }
    });
  }).observe(document.body, { childList: true });
})(jQuery);
</script>
<?php
});
