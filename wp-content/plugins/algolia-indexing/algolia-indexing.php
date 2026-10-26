<?php

/**
 * Plugin Name:     Algolia Indexing
 * Description:     Add Algolia Indexing for pages and posts
 * Text Domain:     algolia-indexing
 * Version:         1.0.0
 * Author:           Mude
 * @package         Algolia_Indexing
 */

// Your code starts here.
require_once __DIR__ . '/vendor/autoload.php';
// If you're using Composer, require the Composer autoload
// require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/wp-cli.php';


/**
 * Error log variable of any type
 */
if (!function_exists('elog')) {
  function elog($var) {
    error_log(print_r($var, true));
  }
}


function general_admin_notice($message, $type) {
  $class = '';
  switch ($type) {
    case 'error':
      $class = 'notice-error';
    case 'warning':
      $class = 'notice-warning';
  }
  global $pagenow;


  if ($pagenow == 'options-general.php' || $pagenow == 'index.php' || $pagenow == 'post.php') { //it is work pagewise 
    echo '<div class="notice ' . $class . '">
             <p>' . $message . '</p>
         </div>';
  }
}


function strip_origin($value) {
  if (substr($value, 0, 4) === "http") {
    $url = parse_url($value);
    return $url['path'];
  }
  return $value;
}


class AlgoliaIndexing {

  /**
   * The unique instance of the plugin.
   *
   * @var AlgoliaIndexing
   */
  private static $instance;

  /**
   * Gets an instance of our plugin.
   *
   * @return AlgoliaIndexing
   */
  public static function get_instance() {
    if (self::$instance === null) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  private $algolia_index_instance;

  private function __construct() {
    // setup page for algolia indexing
    $this->initiate_page_settings();

    // CHANGE: Use WordPress core save_post hook instead of ACF-specific
    add_action('save_post', array($this, 'index_post'), 20, 3);
    add_action('delete_post', array($this, 'delete_from_index'));

    // add action
    // add_action('acf/save_post', array($this, 'index_post'));
    // add_action('algolia/force_index_post', array($this, 'index_post'));
    add_filter('acf/load_field/key=field_6577e377a7495', [$this, 'populate_post_types_field']);
    // add_filter('should_index_post', [$this, 'disable_indexing_if_sensitive_terms_in_title'], 10, 2);
    add_filter('should_index_post', [$this, 'disable_indexing_if_invalid_post_type'], 10, 2);

    // ADD THIS: Index when courses are manually saved
    // add_action('save_post_courses', array($this, 'on_course_save'), 10, 3);
  }

  // populates which fields to index in algolia
  function populate_post_types_field($field) {
    $field['choices'] = [];
    /** @var WP_Post_Type[] $post_types */
    $post_types = get_post_types([
      'show_ui' => true,
    ], 'objects');
    foreach ($post_types as $post_type) {
      $field['choices'][$post_type->name] = $post_type->label;
    }
    return $field;
  }

  // NEW: Handle post deletion
  function delete_from_index($postId) {
    $post_type = get_post_type($postId);
    $post_types = (array) $this->get_post_types();
    if ($post_type && !in_array($post_type, $post_types, true)) {
      return;
    }

    $algolia = $this->get_algolia_indexing();
    if (!is_null($algolia)) {
      try {
        $algolia->deleteObject($postId);
      } catch (\Exception $exception) {
        elog("Failed to delete from Algolia: " . $exception->getMessage());
      }
    }
  }


  private function initiate_page_settings() {
    acf_add_options_page(array(
      'page_title' => __('Algolia Indexing'),
      'menu_title' => __('Algolia Indexing'),
      'redirect' => false,
      'show_in_graphql' => false,
      'parent_slug' => 'options-general.php',
    ));
    register_field_group(wp_json_file_decode(__DIR__ . '/fields.json', ['associative' => true]));
    add_action('acfe/fields/button/name=reindex_all_content', [$this, 'ajax_reindex']);
  }


  private function get_post_types() {
    $post_types_default = get_field('post_type_indexing', 'options');

    return apply_filters("should_index_post_types", $post_types_default);
  }

  private function get_algolia_indexing() {
    if (!is_null($this->algolia_index_instance)) {
      return $this->algolia_index_instance;
    }

    // fetch indexing for algolia
    $application_id = get_field('application_id', 'option');

    $application_key = get_field('application_secret_key', 'option');

    try {
      $algolia_instance = \Algolia\AlgoliaSearch\SearchClient::create("$application_id", "$application_key");

      $algolia_instance->isAlive();

      $index_key = get_field('algolia_index_key', 'option') ?? '';
      if (!$index_key) {
        throw new \Exception('Algolia index key is not set');
      }
      $index = $algolia_instance->initIndex($index_key);
      // custom ranking with priority
      $index->setSettings([
        'customRanking' => ['desc(priority)'],
      ]);
      $this->algolia_index_instance = $index;
      return $this->algolia_index_instance;
    } catch (\Exception $exception) {
      general_admin_notice("Invalid Algolia setup . Indexing will not working.", "error");
    }
  }


  function reindex_all_content() {
    try {
      $algolia = $this->get_algolia_indexing();
      $post_types = $this->get_post_types();

      // elog("Reindexing all content");
      // elog($post_types);

      $algolia->clearObjects();
      // $records = [];

      foreach ($post_types as $post_type) {
        $records = [];

        $paged = 1;
        $count = 0;
        do {
          $query_args = [
            'posts_per_page' => 400,
            'paged' => $paged,
            'post_type' => $post_type,
            'post_status' => 'publish',
            // 's' => '-' . 'melbourne',
          ];

          // elog($query_args);

          $query = new WP_Query($query_args);

          if (!$query->have_posts()) {
            break;
          }

          // foreach ($query->posts as $post) {
          //   $index_post = apply_filters('should_index_post', true, $post->ID);
          //   if (!$index_post) {
          //     continue;
          //   }
          //   $data_record = $this->get_index_record_data($post->ID);
          //   array_push($records, $data_record);
          //   $count++;
          // }

          foreach ($query->posts as $post) {
            $index_post = apply_filters('should_index_post', true, $post->ID);
            if (!$index_post) {
              continue;
            }

            $base_record = $this->get_index_record_data($post->ID);
            $final_record = apply_filters('index_post', $base_record, $post->ID);

            array_push($records, $final_record);
            $count++;
          }

          $paged++;
        } while (true);

        // elog("Saving {$count} records for post type: {$post_type}");

        $algolia->saveObjects($records, [
          'objectIDKey' => 'objectID',
        ]);
      }
    } catch (\Exception $exception) {
      throw $exception;
    }
  }

  function ajax_reindex() {
    try {
      $this->reindex_all_content();
    } catch (\Exception $exception) {
      wp_send_json_error("Reindexing Failed " . $exception->getMessage());
    }
    wp_send_json_success("Reindexing Completed");
  }

  function get_index_record_data($postId) {
    $post = get_post($postId);
    $record_data = array(
      'objectID' => $post->ID,
      'title' => $post->post_title,
      'post_type' => $post->post_type,
      'uri' => strip_origin(get_permalink($post->ID)),
    );
    $record_data['content'] = get_post_custom($post->ID);

    $record = apply_filters('index_post', $record_data, $postId);
    if (isset($record['content']) && is_string($record['content'])) {
      $record['content'] = mb_substr($record['content'], 0, 4000);
    } else {
      $record['content'] = '';
    }
    return $record;
  }

  function index_post($postId, $post = null, $update = false) {
    if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
      return;
    }

    if (is_null($post)) {
      $post = get_post($postId);
    }

    if (!$post) {
      return;
    }

    $post_type = $post->post_type;
    $post_types = (array) $this->get_post_types();
    if (!in_array($post_type, $post_types, true)) {
      return;
    }

    $should_index_post = apply_filters('should_index_post', true, $postId);
    if (!$should_index_post) {
      $this->delete_from_index($postId);
      return;
    }

    $algolia = $this->get_algolia_indexing();
    if (is_null($algolia)) {
      return;
    }

    try {
      $base_record = $this->get_index_record_data($postId);
      $final_record = apply_filters('index_post', $base_record, $postId);

      // Use saveObject to create or fully replace.
      $algolia->saveObject($final_record);
    } catch (\Exception $exception) {
      general_admin_notice("Indexing failed. " . $exception->getMessage(), "error");
      error_log("Algolia indexing error for post {$postId}: " . $exception->getMessage());
    }
  }

  function disable_indexing_if_invalid_post_type($index_post, $postId) {
    $post_type = get_post_type($postId);
    $post_types = (array) $this->get_post_types();
    if (!in_array($post_type, $post_types, true)) {
      return false;
    }
    return $index_post;
  }
}


AlgoliaIndexing::get_instance();
