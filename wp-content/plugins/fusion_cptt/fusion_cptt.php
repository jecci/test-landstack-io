<?php
/**
 * Plugin Name: Fusion Builder Custom Post Types and Taxonomies
 * Plugin URI: http://www.amunet.biz
 * Description: The plugin adds custom post types, custom taxonomies and custom field functionality to the Avada Builder.
 * Version: 7.10.0
 * Author: Amunet
 * Author URI: http://www.amunet.biz
 */

# Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once(plugin_dir_path(__FILE__) . 'cptt_activation.php');
require_once(plugin_dir_path(__FILE__) . 'includes/cptt_functions.php');


// Load shortcode elements.
function cpt_t_init_shortcodes()
{
    require_once(plugin_dir_path(__FILE__) . 'includes/fusion-portfolio-cpt.php');
    require_once(plugin_dir_path(__FILE__) . 'includes/fusion-blog-cpt.php');
    require_once(plugin_dir_path(__FILE__) . 'includes/fusion-recent-posts-cpt.php');
}

add_action('fusion_builder_shortcodes_init', 'cpt_t_init_shortcodes');


class Fusion_Cptt_AvadaCPTTHelper
{

    private static $all_info = [];
    private static $all_meta_keys = [];
    private static $has_run_cpt = false;
    private static $has_run_meta_keys = false;

//returns custom post type, taxonomies and terms in one array
    public static function am_all_custom_data()
    {
        if (!self::$has_run_cpt) {
            //Array to be returned by the function
            $all_post_types_taxonomy = array();

            // create array for post type options
            $conf_pt = array(
                'public' => true,
                '_builtin' => false
            );

            // get registered custom post types
            $custom_post_types_default = array('post' => 'post', 'page' => 'page');
            $custom_post_types_registered = get_post_types($conf_pt, 'names', 'and');
            $custom_post_types = array_merge($custom_post_types_default, $custom_post_types_registered);


            //assign taxonomies to posts
            $args_terms = array(
                'orderby' => 'count',
                'order' => 'DESC',
                'hide_empty' => 0,
            );

            foreach ($custom_post_types as $custom_post_type) {

                //get the list of taxonomies for the post tyep
                $taxonomy_objects = get_object_taxonomies($custom_post_type);

                $del_val = "yst_prominent_words";

                if (($key = array_search($del_val, $taxonomy_objects)) !== false) {
                    unset($taxonomy_objects[$key]);
                }

                //get the list of terms object for the taxonomy
                unset($custom_taxonomies);
                $custom_taxonomies = array();
                foreach ($taxonomy_objects as $taxonomy_object) {

                    $terms = get_terms($taxonomy_object, $args_terms);
                    //cretae the array of taxonomies
                    $custom_taxonomies[$taxonomy_object] = $terms;
                }

                //create the array of post types
                $all_post_types_taxonomy[$custom_post_type] = $custom_taxonomies;
            }
            self::$has_run_cpt = true;
            self::$all_info = $all_post_types_taxonomy;
        }
    }

    //returns custom post types array
    public static function am_custom_post_type_array()
    {

        self::am_all_custom_data();
        $all_custom_array = self::$all_info;
        $custom_post_types_formatted = array();

        foreach ($all_custom_array as $custom_post_type => $taxonomy) {
            $custom_post_types_formatted[$custom_post_type] = esc_attr__($custom_post_type, 'fusion-core');
        }
        return $custom_post_types_formatted;
    }

    //returns all taxonomy array
    public static function am_all_taxonomy_array()
    {

        $all_custom_array = self::$all_info;
        $custom_taxonomy_formarted = array();

        $custom_taxonomy_formarted['xxx__select_taxonomy'] = esc_attr__('Select Taxonomy', 'fusion-core');
        foreach ($all_custom_array as $custom_post_type => $taxonomies) {

            foreach ($taxonomies as $taxonomy => $terms) {

                $custom_taxonomy_formarted[$custom_post_type . '__' . $taxonomy] = esc_attr__($taxonomy, 'fusion-core');
            }
        }
        return $custom_taxonomy_formarted;
    }

    //returns custom taxonomy array
    public static function am_custom_taxonomy_array()
    {

        //build the list of built in taxonomies
        $args_tax = array(
            'public' => true,
            '_builtin' => true
        );

        $built_in_taxonomies = get_taxonomies($args_tax, 'names', 'and');
        $indexed_array_built_in_tax = array();
        foreach ($built_in_taxonomies as $built_in_taxonomy => $some_value) {
            $indexed_array_built_in_tax[] = $built_in_taxonomy;
        }

        $all_custom_array = self::$all_info;
        $custom_taxonomy_formarted = array();
        $custom_taxonomy_formarted['xxx__select_taxonomy'] = esc_attr__('Select Taxonomy', 'fusion-core');
        foreach ($all_custom_array as $custom_post_type => $taxonomies) {

            foreach ($taxonomies as $taxonomy => $terms) {

                $custom_taxonomy_formarted[$custom_post_type . '__' . $taxonomy] = esc_attr__($taxonomy, 'fusion-core');
            }
        }
        return $custom_taxonomy_formarted;
    }

    //	return custom terms
    public static function am_custom_terms_array()
    {
        $all_custom_array = self::$all_info;

        $custom_terms_formarted = array();
        foreach ($all_custom_array as $custom_post_type => $taxonomies) {

            foreach ($taxonomies as $taxonomy => $terms) {

                foreach ($terms as $term) {
                    $custom_terms_formarted[$taxonomy . '__' . $term->slug] = esc_attr__($term->name . ' (' . $term->count . ')', 'fusion-core');
                }

            }

        }
        return $custom_terms_formarted;
    }

    public static function am_get_meta_key()
    {
        if (!self::$has_run_meta_keys) {
            global $wpdb;
            $custom_keys_formarted = array();
            $custom_keys_formarted['select_cfield'] = esc_attr__('Select Custom Field', 'fusion-core');
            $metas = $wpdb->get_results("SELECT DISTINCT meta_key FROM $wpdb->postmeta");
            foreach ($metas as $meta) {
                $current_key = $meta->meta_key;

                $is_default = strpos($current_key, '_');
                $is_pyre = strpos($current_key, 'pyre_');
                $is_kd = strpos($current_key, 'kd_');
                $is_fusion = strpos($current_key, 'fusion_builder_');
                $is_sbg = strpos($current_key, 'sbg_');
                $is_avada_post_views_count = strpos($current_key, 'avada_post_views_count');

                if ($is_default !== 0 && $is_pyre !== 0 && $is_kd !== 0 && $is_fusion !== 0 && $is_sbg !== 0 && $is_avada_post_views_count !== 0) {
                    $custom_keys_formarted[$current_key] = esc_attr__($current_key, 'fusion-core');
                }
            }
            self::$has_run_meta_keys = true;
            self::$all_meta_keys = $custom_keys_formarted;
        }
        return self::$all_meta_keys;

    }
}

function fusion_cptt_scripts()
{
    wp_enqueue_script('fusion_custom_select', plugins_url('assets/js/fusion_cptt_select.js', __FILE__), array('jquery'), '1.29', true);
    wp_enqueue_style('fusion_cptt_styles', plugins_url('assets/css/fusion_cptt_styles.css', __FILE__), array(), '1.2');
}

add_action('admin_enqueue_scripts', 'fusion_cptt_scripts');
?>
