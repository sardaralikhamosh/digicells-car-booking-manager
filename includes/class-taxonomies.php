<?php
class DCBM_Taxonomies {
    
    public function __construct() {
        add_action('init', array($this, 'register_car_categories'));
    }
    
    public function register_car_categories() {
        $labels = array(
            'name'              => __('Car Categories', 'digicells-cbm'),
            'singular_name'     => __('Car Category', 'digicells-cbm'),
            'search_items'      => __('Search Categories', 'digicells-cbm'),
            'all_items'         => __('All Categories', 'digicells-cbm'),
            'parent_item'       => __('Parent Category', 'digicells-cbm'),
            'parent_item_colon' => __('Parent Category:', 'digicells-cbm'),
            'edit_item'         => __('Edit Category', 'digicells-cbm'),
            'update_item'       => __('Update Category', 'digicells-cbm'),
            'add_new_item'      => __('Add New Category', 'digicells-cbm'),
            'new_item_name'     => __('New Category Name', 'digicells-cbm'),
            'menu_name'         => __('Categories', 'digicells-cbm'),
        );
        
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'car-category'),
            'show_in_rest'      => true,
        );
        
        register_taxonomy('dcbm_car_category', array('dcbm_car'), $args);
        
        // Add default terms
        $default_categories = array('SUV', 'Sedan', 'Luxury', 'Economy', 'Van', '4x4');
        foreach ($default_categories as $category) {
            if (!term_exists($category, 'dcbm_car_category')) {
                wp_insert_term($category, 'dcbm_car_category');
            }
        }
    }
}