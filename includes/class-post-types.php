<?php
class DCBM_Post_Types {
    
    public function __construct() {
        add_action('init', array($this, 'register_car_post_type'));
        add_action('init', array($this, 'register_booking_post_type'));
        add_filter('single_template', array($this, 'car_single_template'));
    }
    
    public function register_car_post_type() {
        $labels = array(
            'name'               => __('Cars', 'digicells-cbm'),
            'singular_name'      => __('Car', 'digicells-cbm'),
            'menu_name'          => __('Car Booking', 'digicells-cbm'),
            'add_new'            => __('Add New Car', 'digicells-cbm'),
            'add_new_item'       => __('Add New Car', 'digicells-cbm'),
            'edit_item'          => __('Edit Car', 'digicells-cbm'),
            'new_item'           => __('New Car', 'digicells-cbm'),
            'view_item'          => __('View Car', 'digicells-cbm'),
            'search_items'       => __('Search Cars', 'digicells-cbm'),
            'not_found'          => __('No cars found', 'digicells-cbm'),
            'not_found_in_trash' => __('No cars found in trash', 'digicells-cbm'),
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'car'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-car',
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest'        => true, // Gutenberg support
        );
        
        register_post_type('dcbm_car', $args);
    }
    
    public function register_booking_post_type() {
        $labels = array(
            'name'               => __('Bookings', 'digicells-cbm'),
            'singular_name'      => __('Booking', 'digicells-cbm'),
            'menu_name'          => __('Bookings', 'digicells-cbm'),
            'add_new'            => __('Add New Booking', 'digicells-cbm'),
            'add_new_item'       => __('Add New Booking', 'digicells-cbm'),
            'edit_item'          => __('Edit Booking', 'digicells-cbm'),
            'new_item'           => __('New Booking', 'digicells-cbm'),
            'view_item'          => __('View Booking', 'digicells-cbm'),
            'search_items'       => __('Search Bookings', 'digicells-cbm'),
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => 'edit.php?post_type=dcbm_car',
            'query_var'           => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'supports'            => array('title'),
        );
        
        register_post_type('dcbm_booking', $args);
    }
    
    public function car_single_template($single) {
        global $post;
        
        if ($post->post_type == 'dcbm_car') {
            $template_path = DCBM_PLUGIN_DIR . 'templates/single-car.php';
            if (file_exists($template_path)) {
                return $template_path;
            }
        }
        
        return $single;
    }
}