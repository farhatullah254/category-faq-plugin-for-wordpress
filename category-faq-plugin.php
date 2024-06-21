<?php
/**
 * Plugin Name: Allin One FAQ Plugin
 * Description: Adds an FAQ section to General categories, WooCommerce Categories, Posts, and Pages. Contact Customer Support akesohydra@gmail.com for Queries, Feedback, or Custom Layouts.
 * Version: 2.6.0
 * Author: Farhat Ullah
 * Author URI: https://wa.me/qr/3BLPEXHO74H7L1
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue necessary scripts and styles
function cfq_enqueue_scripts() {
    if (!is_admin()) {
        wp_enqueue_style('cfq-styles', plugin_dir_url(__FILE__) . 'style.css', array(), '2.5.8'); // Change version number here
        wp_enqueue_script('cfq-scripts', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '2.5.5', true);
    }
}
add_action('wp_enqueue_scripts', 'cfq_enqueue_scripts');

// Register Custom Post Type for FAQs
function cfq_register_faq_post_type() {
    $labels = array(
        'name' => 'FAQs',
        'singular_name' => 'FAQ',
        'menu_name' => 'FAQS MANAGEMENT',
        'name_admin_bar' => 'FAQ',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New FAQ',
        'new_item' => 'New FAQ',
        'edit_item' => 'Edit FAQ',
        'view_item' => 'View FAQ',
        'all_items' => 'All FAQs',
        'search_items' => 'Search FAQs',
        'not_found' => 'No FAQs found.',
        'not_found_in_trash' => 'No FAQs found in Trash.'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'faqs'),
        'supports' => array('title', 'editor'),
        'show_in_rest' => true
    );

    register_post_type('faq', $args);
}
add_action('init', 'cfq_register_faq_post_type');

// Add meta box to category, post, and page edit screen
function cfq_add_meta_boxes() {
    $taxonomies = ['category', 'post_tag', 'product_cat'];
    foreach ($taxonomies as $taxonomy) {
        add_action("{$taxonomy}_edit_form_fields", 'cfq_add_taxonomy_faq_metabox');
        add_action("{$taxonomy}_add_form_fields", 'cfq_add_taxonomy_faq_metabox');
    }
    add_action('add_meta_boxes', 'cfq_add_post_page_faq_metabox');
}
add_action('admin_init', 'cfq_add_meta_boxes');

function cfq_add_taxonomy_faq_metabox($term) {
    $faq_categories = get_option('cfq_categories', ['general' => 'General', 'trust-safety' => 'Trust & Safety', 'billing']);
    foreach ($faq_categories as $faq_cat_slug => $faq_cat_name) {
        $selected_faqs = get_term_meta($term->term_id, 'faq_list_' . $faq_cat_slug, true);
        $faqs = get_posts(array('post_type' => 'faq', 'numberposts' => -1));
        ?>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="faq_list_<?php echo $faq_cat_slug; ?>"><?php echo $faq_cat_name; ?> FAQs</label></th>
            <td>
                <select name="faq_list_<?php echo $faq_cat_slug; ?>[]" id="faq_list_<?php echo $faq_cat_slug; ?>" multiple="multiple" style="width: 100%; height: 150px;">
                    <?php foreach ($faqs as $faq) { ?>
                        <option value="<?php echo $faq->ID; ?>" <?php echo (is_array($selected_faqs) && in_array($faq->ID, $selected_faqs)) ? 'selected="selected"' : ''; ?>><?php echo $faq->post_title; ?></option>
                    <?php } ?>
                </select>
                <p class="description">Select FAQs for the <?php echo $faq_cat_name; ?> category.</p>
            </td>
        </tr>
        <?php
    }
}

function cfq_add_post_page_faq_metabox() {
    add_meta_box(
        'cfq_post_page_faq_metabox',
        'FAQ Categories',
        'cfq_render_post_page_faq_metabox',
        ['post', 'page'],
        'normal',
        'high'
    );
}

function cfq_render_post_page_faq_metabox($post) {
    $faq_categories = get_option('cfq_categories', ['general' => 'General', 'trust-safety' => 'Trust & Safety', 'billing']);
    foreach ($faq_categories as $faq_cat_slug => $faq_cat_name) {
        $selected_faqs = get_post_meta($post->ID, 'faq_list_' . $faq_cat_slug, true);
        $faqs = get_posts(array('post_type' => 'faq', 'numberposts' => -1));
        ?>
        <div class="form-field">
            <label for="faq_list_<?php echo $faq_cat_slug; ?>"><?php echo $faq_cat_name; ?> FAQs</label>
            <select name="faq_list_<?php echo $faq_cat_slug; ?>[]" id="faq_list_<?php echo $faq_cat_slug; ?>" multiple="multiple" style="width: 100%; height: 150px;">
                <?php foreach ($faqs as $faq) { ?>
                    <option value="<?php echo $faq->ID; ?>" <?php echo (is_array($selected_faqs) && in_array($faq->ID, $selected_faqs)) ? 'selected="selected"' : ''; ?>><?php echo $faq->post_title; ?></option>
                <?php } ?>
            </select>
            <p class="description">Select FAQs for the <?php echo $faq_cat_name; ?> category.</p>
        </div>
        <?php
    }
}

// Save category meta
function cfq_save_category_meta($term_id) {
    $faq_categories = array_keys(get_option('cfq_categories', ['general', 'trust-safety', 'billing']));
    foreach ($faq_categories as $faq_cat_slug) {
        if (isset($_POST['faq_list_' . $faq_cat_slug])) {
            update_term_meta($term_id, 'faq_list_' . $faq_cat_slug, array_map('intval', $_POST['faq_list_' . $faq_cat_slug]));
        }
    }
}
add_action('edited_category', 'cfq_save_category_meta');
add_action('create_category', 'cfq_save_category_meta');
add_action('edited_product_cat', 'cfq_save_category_meta');
add_action('create_product_cat', 'cfq_save_category_meta');

// Save post and page meta
function cfq_save_post_page_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['post_type']) && ( $_POST['post_type'] == 'post' || $_POST['post_type'] == 'page' )) {
        $faq_categories = array_keys(get_option('cfq_categories', ['general', 'trust-safety', 'billing']));
        foreach ($faq_categories as $faq_cat_slug) {
            if (isset($_POST['faq_list_' . $faq_cat_slug])) {
                update_post_meta($post_id, 'faq_list_' . $faq_cat_slug, array_map('intval', $_POST['faq_list_' . $faq_cat_slug]));
            }
        }
    }
}
add_action('save_post', 'cfq_save_post_page_meta');

// Display FAQ section in taxonomy description and post/page content
function cfq_display_faq($content) {
    static $faq_displayed = false;
    
    if ($faq_displayed) {
        return $content;
    }

    $term_id = null;
    $post_id = null;

    if (is_category() || is_tag() || is_tax('product_cat')) {
        $term_id = get_queried_object_id();
    } elseif (is_single() || is_page()) {
        $post_id = get_the_ID();
    }

    if ($term_id || $post_id) {
        $faq_categories = get_option('cfq_categories', ['general' => 'General', 'trust-safety' => 'Trust & Safety', 'billing']);
        $unique_id_prefix = ($post_id ? "post-$post_id" : "term-$term_id");

        ob_start();
        ?>
        <div class="cfq-faq-container" id="<?php echo $unique_id_prefix; ?>-faq-container">
            <div class="cfq-faq-categories">
                <?php 
                $has_faqs = false;
                foreach ($faq_categories as $faq_cat_slug => $faq_cat_name) {
                    if ($term_id) {
                        $selected_faqs = get_term_meta($term_id, 'faq_list_' . $faq_cat_slug, true);
                    } else {
                        $selected_faqs = get_post_meta($post_id, 'faq_list_' . $faq_cat_slug, true);
                    }
                    if ($selected_faqs) {
                        $has_faqs = true;
                        ?>
                        <h2 id="toggle-<?php echo $unique_id_prefix . '-' . $faq_cat_slug; ?>" onclick="showFaqs('<?php echo $unique_id_prefix . '-' . $faq_cat_slug; ?>')"><?php echo $faq_cat_name; ?></h2>
                        <?php 
                    }
                }
                ?>
            </div>
            <?php if ($has_faqs) { ?>
            <div class="cfq-faq-contents">
                <?php foreach ($faq_categories as $faq_cat_slug => $faq_cat_name) { 
                    if ($term_id) {
                        $selected_faqs = get_term_meta($term_id, 'faq_list_' . $faq_cat_slug, true);
                    } else {
                        $selected_faqs = get_post_meta($post_id, 'faq_list_' . $faq_cat_slug, true);
                    }
                    if ($selected_faqs) {
                        ?>
                        <div class="cfq-faq" id="<?php echo $unique_id_prefix . '-' . $faq_cat_slug; ?>-faqs" style="display: none;">
                            <?php foreach ($selected_faqs as $faq_id) {
                                $faq = get_post($faq_id);
                                ?>
                                <div class="cfq-faq-item">
                                    <h3 onclick="toggleAnswer(this)"><?php echo esc_html($faq->post_title); ?><span class="cfq-plus-icon">+</span></h3>
                                    <div class="cfq-faq-content" style="display: none;">
                                        <p><?php echo wpautop($faq->post_content); ?></p>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <?php 
                    }
                } ?>
            </div>
            <?php } ?>
        </div>
        <?php
        $faq_content = ob_get_clean();

        // Append or prepend FAQ content based on the context
        if ($post_id) {
            $faq_displayed = true;
            return $content . $faq_content;
        } else {
            $faq_displayed = true;
            echo $faq_content;
        }
    }

    return $content;
}
add_action('woocommerce_after_main_content', 'cfq_display_faq', 10);
add_action('category_description', 'cfq_display_faq', 10);
add_action('post_tag_description', 'cfq_display_faq', 10);
add_action('product_cat_description', 'cfq_display_faq', 10);
add_filter('the_content', 'cfq_display_faq', 10);

// Add submenu page under FAQs
function cfq_add_settings_page() {
    add_submenu_page(
        'edit.php?post_type=faq',
        'FAQ Categories',
        'FAQ Categories',
        'manage_options',
        'cfq-settings',
        'cfq_render_settings_page'
    );
}
add_action('admin_menu', 'cfq_add_settings_page');

function cfq_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>FAQ Categories</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cfq_settings_group');
            do_settings_sections('cfq-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function cfq_register_settings() {
    register_setting('cfq_settings_group', 'cfq_categories', 'cfq_validate_categories');
    add_settings_section('cfq_main_section', 'Main Settings', null, 'cfq-settings');
    add_settings_field('cfq_categories_field', 'Categories', 'cfq_render_categories_field', 'cfq-settings', 'cfq_main_section');
}
add_action('admin_init', 'cfq_register_settings');

function cfq_validate_categories($input) {
    $valid = array();
    if (isset($input['new_names']) && isset($input['new_slugs'])) {
        foreach ($input['new_names'] as $index => $name) {
            if (!empty($name) && !empty($input['new_slugs'][$index])) {
                $valid[$input['new_slugs'][$index]] = sanitize_text_field($name);
            }
        }
    }
    if (isset($input['existing'])) {
        foreach ($input['existing'] as $slug => $name) {
            if (!empty($name) && !empty($slug)) {
                $valid[$slug] = sanitize_text_field($name);
            }
        }
    }
    return $valid;
}

function cfq_render_categories_field() {
    $categories = get_option('cfq_categories', ['general' => 'General', 'trust-safety' => 'Trust & Safety', 'billing']);
    ?>
    <table id="cfq_categories_table">
        <?php foreach ($categories as $slug => $name) { ?>
            <tr>
                <td><input type="text" name="cfq_categories[existing][<?php echo esc_attr($slug); ?>]" value="<?php echo esc_attr($name); ?>" /></td>
                <td><button type="button" class="cfq_remove_category">Remove</button></td>
            </tr>
        <?php } ?>
    </table>
    <button type="button" id="cfq_add_category">Add Category</button>
    <script>
        document.getElementById('cfq_add_category').addEventListener('click', function() {
            var table = document.getElementById('cfq_categories_table');
            var row = table.insertRow();
            var cell1 = row.insertCell(0);
            var cell2 = row.insertCell(1);
            cell1.innerHTML = '<input type="text" name="cfq_categories[new_names][]" value="" />';
            cell2.innerHTML = '<input type="text" name="cfq_categories[new_slugs][]" value="" />';
            var cell3 = row.insertCell(2);
            cell3.innerHTML = '<button type="button" class="cfq_remove_category">Remove</button>';
        });

        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('cfq_remove_category')) {
                var row = event.target.closest('tr');
                row.remove();
            }
        });
    </script>
    <?php
}
?>
