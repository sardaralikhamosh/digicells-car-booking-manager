<?php
/**
 * Plugin Name: Digicells Car Booking Manager
 * Version: 2.2.1
 * Author: Sardar Ali Khamosh (Digicells)
 * Text Domain: digicells-cbm
 * Description: Professional car booking system with agents, locations, extra services, load more listings, and automated emails.
 */

if (!defined('ABSPATH')) exit;

define('DCBM_VERSION', '2.2.1');
define('DCBM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DCBM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Activation hook
register_activation_hook(__FILE__, 'dcbm_activate');
function dcbm_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $bookings_table = $wpdb->prefix . 'dcbm_bookings';
    $bookings_sql = "CREATE TABLE IF NOT EXISTS $bookings_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        booking_number varchar(50) NOT NULL,
        car_id mediumint(9) NOT NULL,
        customer_name varchar(255) NOT NULL,
        customer_phone varchar(50) NOT NULL,
        customer_email varchar(255) NOT NULL,
        pickup_date date NOT NULL,
        number_of_days int NOT NULL,
        pickup_location varchar(255) NOT NULL,
        destination varchar(255) DEFAULT '',
        driver_needed varchar(10) DEFAULT 'no',
        car_rental_price decimal(10,2) NOT NULL,
        extra_services_total decimal(10,2) DEFAULT 0,
        total_amount decimal(10,2) NOT NULL,
        selected_services longtext,
        status varchar(50) DEFAULT 'pending',
        booking_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY booking_number (booking_number),
        KEY car_id (car_id),
        KEY status (status)
    ) $charset_collate;";
    
    $services_table = $wpdb->prefix . 'dcbm_extra_services';
    $services_sql = "CREATE TABLE IF NOT EXISTS $services_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        icon varchar(50) DEFAULT '🚗',
        price_type varchar(20) DEFAULT 'paid',
        price decimal(10,2) DEFAULT 0,
        price_model varchar(20) DEFAULT 'per_booking',
        status varchar(20) DEFAULT 'enabled',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($bookings_sql);
    dbDelta($services_sql);
    
    // Add missing columns to bookings table (for upgrades)
    $existing_columns = $wpdb->get_col("DESCRIBE $bookings_table");
    $required_columns = array(
        'destination' => "ALTER TABLE $bookings_table ADD destination varchar(255) DEFAULT ''",
        'number_of_days' => "ALTER TABLE $bookings_table ADD number_of_days int NOT NULL DEFAULT 1",
        'car_rental_price' => "ALTER TABLE $bookings_table ADD car_rental_price decimal(10,2) NOT NULL DEFAULT 0",
        'extra_services_total' => "ALTER TABLE $bookings_table ADD extra_services_total decimal(10,2) DEFAULT 0",
        'selected_services' => "ALTER TABLE $bookings_table ADD selected_services longtext",
        'driver_needed' => "ALTER TABLE $bookings_table ADD driver_needed varchar(10) DEFAULT 'no'"
    );
    foreach ($required_columns as $col => $sql) {
        if (!in_array($col, $existing_columns)) {
            $wpdb->query($sql);
        }
    }
    
    // Add price_model column to services table if missing
    $services_columns = $wpdb->get_col("DESCRIBE $services_table");
    if (!in_array('price_model', $services_columns)) {
        $wpdb->query("ALTER TABLE $services_table ADD price_model varchar(20) DEFAULT 'per_booking'");
    }
    
    // Insert default extra services if empty
    $service_count = $wpdb->get_var("SELECT COUNT(*) FROM $services_table");
    if ($service_count == 0) {
        $default_services = array(
            array('Driver', '👨‍✈️', 'paid', 1000, 'per_booking'),
            array('Fuel', '⛽', 'paid', 2000, 'per_booking'),
            array('Meal (per day)', '🍽️', 'paid', 500, 'per_day'),
            array('Accommodation (per day)', '🏨', 'paid', 3000, 'per_day'),
            array('Airport Pickup', '✈️', 'paid', 1500, 'per_booking'),
            array('Child Seat', '👶', 'paid', 500, 'per_booking'),
            array('Tour Guide (per day)', '🗺️', 'paid', 2000, 'per_day'),
        );
        foreach ($default_services as $service) {
            $wpdb->insert($services_table, array(
                'name' => $service[0],
                'icon' => $service[1],
                'price_type' => $service[2],
                'price' => $service[3],
                'price_model' => $service[4],
                'status' => 'enabled'
            ));
        }
    }
    
    // Register post types and flush rewrite rules
    dcbm_register_post_types();
    dcbm_register_taxonomies();
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'dcbm_deactivate');
function dcbm_deactivate() {
    flush_rewrite_rules();
}

add_action('plugins_loaded', 'dcbm_init');
function dcbm_init() {
    load_plugin_textdomain('digicells-cbm', false, dirname(plugin_basename(__FILE__)) . '/languages');
    add_action('init', 'dcbm_register_post_types');
    add_action('init', 'dcbm_register_taxonomies');
    add_action('add_meta_boxes', 'dcbm_add_car_meta_boxes');
    add_action('save_post_dcbm_car', 'dcbm_save_car_meta');
    add_action('admin_enqueue_scripts', 'dcbm_admin_scripts');
    add_action('wp_enqueue_scripts', 'dcbm_frontend_scripts');
    add_action('admin_menu', 'dcbm_admin_menus');
    
    // Custom admin UI for Locations and Agents
    add_action('add_meta_boxes', 'dcbm_add_location_metaboxes');
    add_action('add_meta_boxes', 'dcbm_add_agent_metaboxes');
    add_action('save_post_dcbm_location', 'dcbm_save_location_meta');
    add_action('save_post_dcbm_agent', 'dcbm_save_agent_meta');
    add_filter('manage_dcbm_location_posts_columns', 'dcbm_location_columns');
    add_action('manage_dcbm_location_posts_custom_column', 'dcbm_location_column_content', 10, 2);
    add_filter('manage_dcbm_agent_posts_columns', 'dcbm_agent_columns');
    add_action('manage_dcbm_agent_posts_custom_column', 'dcbm_agent_column_content', 10, 2);
    
    // AJAX handlers
    add_action('wp_ajax_dcbm_get_extra_services', 'dcbm_ajax_get_extra_services');
    add_action('wp_ajax_nopriv_dcbm_get_extra_services', 'dcbm_ajax_get_extra_services');
    add_action('wp_ajax_dcbm_submit_booking', 'dcbm_ajax_submit_booking');
    add_action('wp_ajax_nopriv_dcbm_submit_booking', 'dcbm_ajax_submit_booking');
    add_action('wp_ajax_dcbm_advanced_search', 'dcbm_ajax_advanced_search_loadmore');
    add_action('wp_ajax_nopriv_dcbm_advanced_search', 'dcbm_ajax_advanced_search_loadmore');
    add_action('wp_ajax_dcbm_load_more_listing', 'dcbm_ajax_load_more_listing');
    add_action('wp_ajax_nopriv_dcbm_load_more_listing', 'dcbm_ajax_load_more_listing');
    add_action('wp_ajax_dcbm_update_booking_status', 'dcbm_ajax_update_booking_status');
    
    // Shortcodes
    add_shortcode('digicells_advanced_search', 'dcbm_advanced_search_shortcode');
    add_shortcode('digicells_car_listing_loadmore', 'dcbm_car_listing_loadmore_shortcode');
    add_shortcode('digicells_featured_cars', 'dcbm_featured_cars_shortcode');
    add_shortcode('digicells_cars_listing_simple', 'dcbm_cars_listing_simple_shortcode');
    
    add_filter('single_template', 'dcbm_single_car_template');
    add_action('wp_footer', 'dcbm_booking_modal');
}

// Register Post Types
function dcbm_register_post_types() {
    $car_labels = array(
        'name' => __('Cars', 'digicells-cbm'),
        'singular_name' => __('Car', 'digicells-cbm'),
        'menu_name' => __('Car Booking', 'digicells-cbm'),
        'add_new' => __('Add New Car', 'digicells-cbm'),
        'add_new_item' => __('Add New Car', 'digicells-cbm'),
    );
    $car_args = array(
        'labels' => $car_labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'car'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-car',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        'show_in_rest' => true,
    );
    register_post_type('dcbm_car', $car_args);
    
    $agent_labels = array(
        'name' => __('Agents', 'digicells-cbm'),
        'singular_name' => __('Agent', 'digicells-cbm'),
        'menu_name' => __('Agents', 'digicells-cbm'),
        'add_new' => __('Add New Agent', 'digicells-cbm'),
        'add_new_item' => __('Add New Agent', 'digicells-cbm'),
    );
    $agent_args = array(
        'labels' => $agent_labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => false,
        'supports' => array('title', 'thumbnail'),
        'show_in_rest' => true,
    );
    register_post_type('dcbm_agent', $agent_args);
    
    $location_labels = array(
        'name' => __('Locations', 'digicells-cbm'),
        'singular_name' => __('Location', 'digicells-cbm'),
        'menu_name' => __('Locations', 'digicells-cbm'),
        'add_new' => __('Add New Location', 'digicells-cbm'),
        'add_new_item' => __('Add New Location', 'digicells-cbm'),
        'parent_item' => __('Parent Location', 'digicells-cbm'),
        'parent_item_colon' => __('Parent Location:', 'digicells-cbm'),
        'edit_item' => __('Edit Location', 'digicells-cbm'),
        'view_item' => __('View Location', 'digicells-cbm'),
        'search_items' => __('Search Locations', 'digicells-cbm'),
        'not_found' => __('No locations found', 'digicells-cbm'),
    );
    $location_args = array(
        'labels' => $location_labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => false,
        'hierarchical' => true,
        'supports' => array('title', 'thumbnail', 'page-attributes'),
        'show_in_rest' => true,
    );
    register_post_type('dcbm_location', $location_args);
}

function dcbm_register_taxonomies() {
    $labels = array(
        'name' => __('Car Categories', 'digicells-cbm'),
        'singular_name' => __('Car Category', 'digicells-cbm'),
        'menu_name' => __('Categories', 'digicells-cbm'),
    );
    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'car-category'),
        'show_in_rest' => true,
    );
    register_taxonomy('dcbm_car_category', array('dcbm_car'), $args);
}

// ---------------------------
// Locations Admin
// ---------------------------
function dcbm_add_location_metaboxes() {
    add_meta_box('dcbm_location_image', __('Featured Image', 'digicells-cbm'), 'dcbm_location_image_metabox', 'dcbm_location', 'side', 'default');
}

function dcbm_location_image_metabox($post) {
    $thumbnail_id = get_post_thumbnail_id($post->ID);
    $image = $thumbnail_id ? wp_get_attachment_image($thumbnail_id, 'thumbnail') : '';
    echo '<div id="location-image-wrapper">' . $image . '</div>';
    echo '<button type="button" class="button" id="upload_location_image">Set Featured Image</button>';
    echo '<input type="hidden" name="location_thumbnail_id" id="location_thumbnail_id" value="' . $thumbnail_id . '">';
    echo '<button type="button" class="button" id="remove_location_image" style="display:' . ($thumbnail_id ? 'inline-block' : 'none') . '">Remove Image</button>';
    ?>
    <script>
    jQuery(function($) {
        var frame;
        $('#upload_location_image').on('click', function(e) {
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({ title: 'Choose Image', button: { text: 'Use this image' }, multiple: false });
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#location_thumbnail_id').val(attachment.id);
                $('#location-image-wrapper').html('<img src="'+attachment.url+'" style="max-width:100%;">');
                $('#remove_location_image').show();
            });
            frame.open();
        });
        $('#remove_location_image').on('click', function(e) {
            e.preventDefault();
            $('#location_thumbnail_id').val('');
            $('#location-image-wrapper').html('');
            $(this).hide();
        });
    });
    </script>
    <?php
}

function dcbm_save_location_meta($post_id) {
    if (isset($_POST['location_thumbnail_id'])) {
        set_post_thumbnail($post_id, intval($_POST['location_thumbnail_id']));
    }
}

function dcbm_location_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['thumbnail'] = __('Image', 'digicells-cbm');
    $new_columns['title'] = __('Name', 'digicells-cbm');
    $new_columns['slug'] = __('Slug', 'digicells-cbm');
    $new_columns['parent'] = __('Parent', 'digicells-cbm');
    $new_columns['count'] = __('Cars', 'digicells-cbm');
    return $new_columns;
}

function dcbm_location_column_content($column, $post_id) {
    switch ($column) {
        case 'thumbnail':
            echo get_the_post_thumbnail($post_id, array(40, 40));
            break;
        case 'slug':
            $post = get_post($post_id);
            echo $post->post_name;
            break;
        case 'parent':
            $parent_id = wp_get_post_parent_id($post_id);
            echo $parent_id ? get_the_title($parent_id) : '—';
            break;
        case 'count':
            $count = count(get_posts(array('post_type' => 'dcbm_car', 'meta_key' => '_dcbm_location_id', 'meta_value' => $post_id, 'posts_per_page' => -1, 'fields' => 'ids')));
            echo $count;
            break;
    }
}

// ---------------------------
// Agents Admin
// ---------------------------
function dcbm_add_agent_metaboxes() {
    add_meta_box('dcbm_agent_details', __('Agent Details', 'digicells-cbm'), 'dcbm_agent_details_metabox', 'dcbm_agent', 'normal', 'high');
    add_meta_box('dcbm_agent_image', __('Featured Image', 'digicells-cbm'), 'dcbm_agent_image_metabox', 'dcbm_agent', 'side', 'default');
}

function dcbm_agent_details_metabox($post) {
    $email = get_post_meta($post->ID, '_dcbm_agent_email', true);
    $phone = get_post_meta($post->ID, '_dcbm_agent_phone', true);
    $address = get_post_meta($post->ID, '_dcbm_agent_address', true);
    $location_id = get_post_meta($post->ID, '_dcbm_agent_location_id', true);
    $locations = get_posts(array('post_type' => 'dcbm_location', 'posts_per_page' => -1, 'orderby' => 'title'));
    ?>
    <p><label>Email:</label><br><input type="email" name="agent_email" value="<?php echo esc_attr($email); ?>" style="width:100%"></p>
    <p><label>Phone:</label><br><input type="text" name="agent_phone" value="<?php echo esc_attr($phone); ?>" style="width:100%"></p>
    <p><label>Address:</label><br><textarea name="agent_address" style="width:100%"><?php echo esc_textarea($address); ?></textarea></p>
    <p><label>Assigned Location (sync with car location):</label><br>
    <select name="agent_location_id" style="width:100%">
        <option value="">Select Location</option>
        <?php foreach ($locations as $loc): ?>
            <option value="<?php echo $loc->ID; ?>" <?php selected($location_id, $loc->ID); ?>><?php echo esc_html($loc->post_title); ?></option>
        <?php endforeach; ?>
    </select></p>
    <?php
}

function dcbm_agent_image_metabox($post) {
    $thumbnail_id = get_post_thumbnail_id($post->ID);
    $image = $thumbnail_id ? wp_get_attachment_image($thumbnail_id, 'thumbnail') : '';
    echo '<div id="agent-image-wrapper">' . $image . '</div>';
    echo '<button type="button" class="button" id="upload_agent_image">Set Featured Image</button>';
    echo '<input type="hidden" name="agent_thumbnail_id" id="agent_thumbnail_id" value="' . $thumbnail_id . '">';
    echo '<button type="button" class="button" id="remove_agent_image" style="display:' . ($thumbnail_id ? 'inline-block' : 'none') . '">Remove Image</button>';
    ?>
    <script>
    jQuery(function($) {
        var frame;
        $('#upload_agent_image').on('click', function(e) {
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({ title: 'Choose Image', button: { text: 'Use this image' }, multiple: false });
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#agent_thumbnail_id').val(attachment.id);
                $('#agent-image-wrapper').html('<img src="'+attachment.url+'" style="max-width:100%;">');
                $('#remove_agent_image').show();
            });
            frame.open();
        });
        $('#remove_agent_image').on('click', function(e) {
            e.preventDefault();
            $('#agent_thumbnail_id').val('');
            $('#agent-image-wrapper').html('');
            $(this).hide();
        });
    });
    </script>
    <?php
}

function dcbm_save_agent_meta($post_id) {
    if (isset($_POST['agent_email'])) update_post_meta($post_id, '_dcbm_agent_email', sanitize_email($_POST['agent_email']));
    if (isset($_POST['agent_phone'])) update_post_meta($post_id, '_dcbm_agent_phone', sanitize_text_field($_POST['agent_phone']));
    if (isset($_POST['agent_address'])) update_post_meta($post_id, '_dcbm_agent_address', sanitize_textarea_field($_POST['agent_address']));
    if (isset($_POST['agent_location_id'])) update_post_meta($post_id, '_dcbm_agent_location_id', intval($_POST['agent_location_id']));
    if (isset($_POST['agent_thumbnail_id'])) set_post_thumbnail($post_id, intval($_POST['agent_thumbnail_id']));
}

function dcbm_agent_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['thumbnail'] = __('Image', 'digicells-cbm');
    $new_columns['title'] = __('Name', 'digicells-cbm');
    $new_columns['email'] = __('Email', 'digicells-cbm');
    $new_columns['phone'] = __('Phone', 'digicells-cbm');
    $new_columns['location'] = __('Location', 'digicells-cbm');
    return $new_columns;
}

function dcbm_agent_column_content($column, $post_id) {
    switch ($column) {
        case 'thumbnail':
            echo get_the_post_thumbnail($post_id, array(40, 40));
            break;
        case 'email':
            echo get_post_meta($post_id, '_dcbm_agent_email', true);
            break;
        case 'phone':
            echo get_post_meta($post_id, '_dcbm_agent_phone', true);
            break;
        case 'location':
            $loc_id = get_post_meta($post_id, '_dcbm_agent_location_id', true);
            echo $loc_id ? get_the_title($loc_id) : '—';
            break;
    }
}

// ---------------------------
// Car Meta Boxes
// ---------------------------
function dcbm_add_car_meta_boxes() {
    add_meta_box('dcbm_car_details', __('Car Details', 'digicells-cbm'), 'dcbm_render_car_details_metabox', 'dcbm_car', 'normal', 'high');
    add_meta_box('dcbm_car_location', __('Location & Agent', 'digicells-cbm'), 'dcbm_render_location_agent_metabox', 'dcbm_car', 'side', 'default');
}

function dcbm_render_car_details_metabox($post) {
    wp_nonce_field('dcbm_save_car_meta', 'dcbm_car_meta_nonce');
    $car_model = get_post_meta($post->ID, '_dcbm_car_model', true);
    $manufacturer = get_post_meta($post->ID, '_dcbm_manufacturer', true);
    $reg_year = get_post_meta($post->ID, '_dcbm_registration_year', true);
    $car_number = get_post_meta($post->ID, '_dcbm_car_number', true);
    $transmission = get_post_meta($post->ID, '_dcbm_transmission', true);
    $fuel_type = get_post_meta($post->ID, '_dcbm_fuel_type', true);
    $passenger_capacity = get_post_meta($post->ID, '_dcbm_passenger_capacity', true);
    $price = get_post_meta($post->ID, '_dcbm_price_per_day', true);
    $availability = get_post_meta($post->ID, '_dcbm_availability', true);
    $car_color = get_post_meta($post->ID, '_dcbm_car_color', true);
    $unlimited_miles = get_post_meta($post->ID, '_dcbm_unlimited_miles', true);
    if (!$availability) $availability = 'available';
    ?>
    <div class="dcbm-meta-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;">
        <div class="dcbm-meta-field"><label>Car Model:</label><input type="text" name="dcbm_car_model" value="<?php echo esc_attr($car_model); ?>"></div>
        <div class="dcbm-meta-field"><label>Manufacturer:</label><input type="text" name="dcbm_manufacturer" value="<?php echo esc_attr($manufacturer); ?>"></div>
        <div class="dcbm-meta-field"><label>Registration Year:</label><select name="dcbm_registration_year"><option value="">Select Year</option><?php for($y=date('Y');$y>=2000;$y--): ?><option value="<?php echo $y; ?>" <?php selected($reg_year,$y); ?>><?php echo $y; ?></option><?php endfor; ?></select></div>
        <div class="dcbm-meta-field"><label>Car Number:</label><input type="text" name="dcbm_car_number" value="<?php echo esc_attr($car_number); ?>"></div>
        <div class="dcbm-meta-field"><label>Transmission:</label><select name="dcbm_transmission"><option value="automatic" <?php selected($transmission,'automatic'); ?>>Automatic</option><option value="manual" <?php selected($transmission,'manual'); ?>>Manual</option></select></div>
        <div class="dcbm-meta-field"><label>Fuel Type:</label><select name="dcbm_fuel_type"><option value="petrol" <?php selected($fuel_type,'petrol'); ?>>Petrol</option><option value="diesel" <?php selected($fuel_type,'diesel'); ?>>Diesel</option><option value="hybrid" <?php selected($fuel_type,'hybrid'); ?>>Hybrid</option><option value="electric" <?php selected($fuel_type,'electric'); ?>>Electric</option></select></div>
        <div class="dcbm-meta-field"><label>Passenger Capacity:</label><input type="number" name="dcbm_passenger_capacity" value="<?php echo esc_attr($passenger_capacity); ?>" min="1"></div>
        <div class="dcbm-meta-field"><label>Price Per Day (PKR):</label><input type="number" name="dcbm_price_per_day" value="<?php echo esc_attr($price); ?>"></div>
        <div class="dcbm-meta-field"><label>Availability:</label><select name="dcbm_availability"><option value="available" <?php selected($availability,'available'); ?>>Available</option><option value="not_available" <?php selected($availability,'not_available'); ?>>Not Available</option><option value="maintenance" <?php selected($availability,'maintenance'); ?>>Maintenance</option></select></div>
        <div class="dcbm-meta-field"><label>Car Color:</label><input type="text" name="dcbm_car_color" value="<?php echo esc_attr($car_color); ?>"></div>
        <div class="dcbm-meta-field"><label>Unlimited Miles:</label><select name="dcbm_unlimited_miles"><option value="yes" <?php selected($unlimited_miles,'yes'); ?>>Yes</option><option value="no" <?php selected($unlimited_miles,'no'); ?>>No</option></select></div>
    </div>
    <?php
}

function dcbm_render_location_agent_metabox($post) {
    $selected_location = get_post_meta($post->ID, '_dcbm_location_id', true);
    $selected_agent = get_post_meta($post->ID, '_dcbm_agent_id', true);
    
    $locations = get_posts(array('post_type' => 'dcbm_location', 'posts_per_page' => -1, 'orderby' => 'title'));
    $agents = get_posts(array('post_type' => 'dcbm_agent', 'posts_per_page' => -1, 'orderby' => 'title'));
    ?>
    <p><label>Location:</label><br><select name="dcbm_location_id" style="width:100%"><option value="">Select Location</option><?php foreach($locations as $loc): ?><option value="<?php echo $loc->ID; ?>" <?php selected($selected_location, $loc->ID); ?>><?php echo esc_html($loc->post_title); ?></option><?php endforeach; ?></select></p>
    <p><label>Agent:</label><br><select name="dcbm_agent_id" style="width:100%"><option value="">Select Agent</option><?php foreach($agents as $agent): ?><option value="<?php echo $agent->ID; ?>" <?php selected($selected_agent, $agent->ID); ?>><?php echo esc_html($agent->post_title); ?></option><?php endforeach; ?></select></p>
    <?php
}

function dcbm_save_car_meta($post_id) {
    if (!isset($_POST['dcbm_car_meta_nonce']) || !wp_verify_nonce($_POST['dcbm_car_meta_nonce'], 'dcbm_save_car_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $fields = array('dcbm_car_model','dcbm_manufacturer','dcbm_registration_year','dcbm_car_number','dcbm_transmission','dcbm_fuel_type','dcbm_passenger_capacity','dcbm_price_per_day','dcbm_availability','dcbm_car_color','dcbm_unlimited_miles');
    foreach ($fields as $field) {
        if (isset($_POST[$field])) update_post_meta($post_id, '_'.$field, sanitize_text_field($_POST[$field]));
    }
    if (isset($_POST['dcbm_location_id'])) update_post_meta($post_id, '_dcbm_location_id', intval($_POST['dcbm_location_id']));
    if (isset($_POST['dcbm_agent_id'])) update_post_meta($post_id, '_dcbm_agent_id', intval($_POST['dcbm_agent_id']));
}

// Admin Menus
function dcbm_admin_menus() {
    add_submenu_page('edit.php?post_type=dcbm_car', __('Extra Services', 'digicells-cbm'), __('Extra Services', 'digicells-cbm'), 'manage_options', 'dcbm-services', 'dcbm_services_page');
    add_submenu_page('edit.php?post_type=dcbm_car', __('Bookings', 'digicells-cbm'), __('Bookings', 'digicells-cbm'), 'manage_options', 'dcbm-bookings', 'dcbm_bookings_page');
    add_submenu_page('edit.php?post_type=dcbm_car', __('Agents', 'digicells-cbm'), __('Agents', 'digicells-cbm'), 'manage_options', 'edit.php?post_type=dcbm_agent');
    add_submenu_page('edit.php?post_type=dcbm_car', __('Locations', 'digicells-cbm'), __('Locations', 'digicells-cbm'), 'manage_options', 'edit.php?post_type=dcbm_location');
}

// Extra Services Page
function dcbm_services_page() {
    global $wpdb;
    $services_table = $wpdb->prefix . 'dcbm_extra_services';
    if (isset($_POST['dcbm_add_service']) && wp_verify_nonce($_POST['dcbm_service_nonce'], 'dcbm_add_service')) {
        $wpdb->insert($services_table, array(
            'name' => sanitize_text_field($_POST['service_name']),
            'icon' => sanitize_text_field($_POST['service_icon']),
            'price_type' => sanitize_text_field($_POST['price_type']),
            'price' => floatval($_POST['price']),
            'price_model' => sanitize_text_field($_POST['price_model']),
            'status' => 'enabled'
        ));
        echo '<div class="notice notice-success"><p>Service added!</p></div>';
    }
    if (isset($_GET['delete_service']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_service')) {
        $wpdb->delete($services_table, array('id'=>intval($_GET['delete_service'])));
        echo '<div class="notice notice-success"><p>Service deleted!</p></div>';
    }
    $services = $wpdb->get_results("SELECT * FROM $services_table ORDER BY id ASC");
    ?>
    <div class="wrap"><h1>Extra Services</h1><div class="card" style="max-width:500px;margin-bottom:30px;"><h3>Add New Service</h3><form method="post"><?php wp_nonce_field('dcbm_add_service','dcbm_service_nonce'); ?><table class="form-table"><tr><th>Name:</th><td><input type="text" name="service_name" required></td></tr><tr><th>Icon (Emoji):</th><td><input type="text" name="service_icon" placeholder="🚗"></td></tr><tr><th>Price Type:</th><td><select name="price_type"><option value="paid">Paid</option><option value="free">Free</option></select></td></tr><tr><th>Price (PKR):</th><td><input type="number" name="price" value="0"></td></tr><tr><th>Pricing Model:</th><td><select name="price_model"><option value="per_booking">Per Booking (flat)</option><option value="per_day">Per Day</option></select></td></tr></td><?php submit_button('Add Service','primary','dcbm_add_service'); ?></form></div><h3>Existing Services</h3><table class="wp-list-table widefat fixed striped"><thead><tr><th>Icon</th><th>Name</th><th>Price Type</th><th>Price</th><th>Pricing Model</th><th>Actions</th></tr></thead><tbody><?php foreach($services as $service): ?><tr><td><?php echo esc_html($service->icon); ?></td><td><?php echo esc_html($service->name); ?></td><td><?php echo ucfirst($service->price_type); ?></td><td><?php echo $service->price_type=='paid'?'PKR '.number_format($service->price):'Free'; ?></td><td><?php echo $service->price_model == 'per_day' ? 'Per Day' : 'Per Booking'; ?></td><td><a href="<?php echo wp_nonce_url(add_query_arg('delete_service',$service->id),'delete_service'); ?>" class="button button-small" onclick="return confirm('Delete?')">Delete</a></td></tr><?php endforeach; ?></tbody></table></div>
    <?php
}

// Bookings Page
function dcbm_bookings_page() {
    global $wpdb;
    $bookings = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dcbm_bookings ORDER BY id DESC");
    ?>
    <div class="wrap"><h1>Car Bookings</h1><table class="wp-list-table widefat fixed striped"><thead><tr><th>Booking #</th><th>Customer</th><th>Car</th><th>Pickup Date</th><th>Days</th><th>Total</th><th>Status</th><th>Actions</th><tr></thead><tbody><?php foreach($bookings as $booking): $car_title=get_the_title($booking->car_id); ?><tr><td><?php echo esc_html($booking->booking_number); ?></td><td><?php echo esc_html($booking->customer_name); ?><br><small><?php echo esc_html($booking->customer_email); ?></small></td><td><?php echo esc_html($car_title); ?></td><td><?php echo date('M j, Y',strtotime($booking->pickup_date)); ?></td><td><?php echo $booking->number_of_days; ?></td><td>PKR <?php echo number_format($booking->total_amount); ?></td><td><select class="dcbm-booking-status" data-id="<?php echo $booking->id; ?>"><option value="pending" <?php selected($booking->status,'pending'); ?>>Pending</option><option value="approved" <?php selected($booking->status,'approved'); ?>>Approved</option><option value="rejected" <?php selected($booking->status,'rejected'); ?>>Rejected</option><option value="completed" <?php selected($booking->status,'completed'); ?>>Completed</option></select></td><td><button class="button button-small view-booking" data-id="<?php echo $booking->id; ?>">View</button></td></tr><?php endforeach; ?></tbody></table></div><script>jQuery(document).ready(function($){$('.dcbm-booking-status').on('change',function(){var id=$(this).data('id'),status=$(this).val();$.ajax({url:ajaxurl,type:'POST',data:{action:'dcbm_update_booking_status',booking_id:id,status:status,nonce:'<?php echo wp_create_nonce("dcbm_nonce"); ?>'},success:function(r){if(r.success) location.reload();}});});});</script>
    <?php
}

// Enqueue Scripts
function dcbm_admin_scripts($hook) {
    if (strpos($hook, 'dcbm_location') !== false || strpos($hook, 'dcbm_agent') !== false) {
        wp_enqueue_media();
    }
}

function dcbm_frontend_scripts() {
    wp_enqueue_style('dashicons');
    wp_enqueue_style('dcbm-frontend', DCBM_PLUGIN_URL.'assets/css/frontend-style.css', array(), DCBM_VERSION);
    wp_enqueue_script('dcbm-frontend', DCBM_PLUGIN_URL.'assets/js/frontend-script.js', array('jquery'), DCBM_VERSION, true);
    wp_localize_script('dcbm-frontend', 'dcbm_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dcbm_nonce')
    ));
}

// AJAX: Get Extra Services
function dcbm_ajax_get_extra_services() {
    check_ajax_referer('dcbm_nonce','nonce');
    global $wpdb;
    $services = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dcbm_extra_services WHERE status='enabled'");
    wp_send_json_success($services);
}

// AJAX: Submit Booking (fixed)
function dcbm_ajax_submit_booking() {
    check_ajax_referer('dcbm_nonce','nonce');
    global $wpdb;
    $bookings_table = $wpdb->prefix.'dcbm_bookings';
    $booking_number = 'BK-'.strtoupper(uniqid());
    
    $pickup_date = sanitize_text_field($_POST['pickup_date']);
    $number_of_days = intval($_POST['number_of_days']);
    $driver_needed = isset($_POST['driver_needed']) ? sanitize_text_field($_POST['driver_needed']) : 'no';
    
    $selected_services_raw = stripslashes($_POST['selected_services']);
    $selected_services = json_decode($selected_services_raw, true);
    if (!is_array($selected_services)) $selected_services = array();
    
    $data = array(
        'booking_number' => $booking_number,
        'car_id' => intval($_POST['car_id']),
        'customer_name' => sanitize_text_field($_POST['name']),
        'customer_phone' => sanitize_text_field($_POST['phone']),
        'customer_email' => sanitize_email($_POST['email']),
        'pickup_date' => $pickup_date,
        'number_of_days' => $number_of_days,
        'pickup_location' => sanitize_text_field($_POST['pickup_location']),
        'destination' => sanitize_text_field($_POST['destination']),
        'driver_needed' => $driver_needed,
        'car_rental_price' => floatval($_POST['car_rental_price']),
        'extra_services_total' => floatval($_POST['extra_services_total']),
        'total_amount' => floatval($_POST['total_amount']),
        'selected_services' => json_encode($selected_services),
        'status' => 'pending'
    );
    
    $inserted = $wpdb->insert($bookings_table, $data);
    if ($inserted) {
        $car_id = $data['car_id'];
        $car_title = get_the_title($car_id);
        $car_model = get_post_meta($car_id, '_dcbm_car_model', true);
        $car_color = get_post_meta($car_id, '_dcbm_car_color', true);
        $car_price_per_day = get_post_meta($car_id, '_dcbm_price_per_day', true);
        $agent_id = get_post_meta($car_id, '_dcbm_agent_id', true);
        $agent_email = $agent_id ? get_post_meta($agent_id, '_dcbm_agent_email', true) : '';
        
        $services_list = '';
        $services_table = $wpdb->prefix.'dcbm_extra_services';
        if (!empty($selected_services)) {
            foreach ($selected_services as $sid => $sdata) {
                $sname = $wpdb->get_var($wpdb->prepare("SELECT name FROM $services_table WHERE id=%d", $sid));
                $price = isset($sdata['price']) ? $sdata['price'] : 0;
                if ($sname) $services_list .= "<li>{$sname} - PKR ".number_format($price)."</li>";
            }
        } else {
            $services_list = '<li>No extra services selected</li>';
        }
        
        $admin_email = get_option('admin_email');
        $subject = "New Car Booking Inquiry - {$booking_number}";
        $message = "<html><body><h2>New Booking Request</h2><p><strong>Booking Number:</strong> {$booking_number}</p><h3>Customer</h3><p><strong>Name:</strong> {$data['customer_name']}<br><strong>Phone:</strong> {$data['customer_phone']}<br><strong>Email:</strong> {$data['customer_email']}</p><h3>Car</h3><p><strong>Car:</strong> {$car_title}<br><strong>Model:</strong> {$car_model}<br><strong>Color:</strong> {$car_color}<br><strong>Price per day:</strong> PKR ".number_format($car_price_per_day)."</p><h3>Trip</h3><p><strong>Pickup Date:</strong> {$data['pickup_date']}<br><strong>Days:</strong> {$data['number_of_days']}<br><strong>Pickup Location:</strong> {$data['pickup_location']}<br><strong>Destination:</strong> {$data['destination']}<br><strong>Driver Needed:</strong> ".($driver_needed == 'yes' ? 'Yes' : 'No')."</p><h3>Extra Services</h3><ul>{$services_list}</ul><h3>Price</h3><p><strong>Car Rental:</strong> PKR ".number_format($data['car_rental_price'])."<br><strong>Extra Services:</strong> PKR ".number_format($data['extra_services_total'])."<br><strong>Total:</strong> PKR ".number_format($data['total_amount'])."</p></body></html>";
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($admin_email, $subject, $message, $headers);
        if ($agent_email) wp_mail($agent_email, $subject, $message, $headers);
        
        $customer_subject = "Thank you for your inquiry - Hamari Booking";
        $customer_message = "<html><body><h2>Thank you for showing interest in Hamari Booking!</h2><p>Dear {$data['customer_name']},</p><p>Your booking request has been received. Our team is working on quotes and will revert back shortly with the best available offers.</p><p><strong>Booking Reference:</strong> {$booking_number}</p><p>We will contact you shortly.</p><p>Regards,<br>Hamari Booking Team</p></body></html>";
        wp_mail($data['customer_email'], $customer_subject, $customer_message, $headers);
        
        wp_send_json_success(array('message' => 'Your request submitted successfully. Thank you for showing interest in hamaribooking.com. Our team is working on quotes and will revert back shortly with best available offers.'));
    } else {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    }
}

// ==========================================================
// UPDATED AJAX FUNCTIONS WITH "SHOW PRICE" BUTTON
// ==========================================================
function dcbm_ajax_advanced_search_loadmore() {
    check_ajax_referer('dcbm_nonce','nonce');
    $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
    $per_page = 4;
    
    $args = array(
        'post_type' => 'dcbm_car',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'meta_query' => array(
            array('key' => '_dcbm_availability', 'value' => 'available'),
        )
    );
    if ($location_id) {
        $args['meta_query'][] = array('key' => '_dcbm_location_id', 'value' => $location_id);
    }
    
    $cars = new WP_Query($args);
    ob_start();
    if ($cars->have_posts()):
        while ($cars->have_posts()): $cars->the_post();
            $price = get_post_meta(get_the_ID(), '_dcbm_price_per_day', true);
            $trans = get_post_meta(get_the_ID(), '_dcbm_transmission', true);
            $capacity = get_post_meta(get_the_ID(), '_dcbm_passenger_capacity', true);
            $loc_id = get_post_meta(get_the_ID(), '_dcbm_location_id', true);
            $location_name = $loc_id ? get_the_title($loc_id) : '';
            ?>
            <div class="dcbm-car-card">
                <div class="dcbm-car-image">
                    <?php if(has_post_thumbnail()) the_post_thumbnail('medium'); else echo '<img src="'.DCBM_PLUGIN_URL.'assets/images/placeholder-car.jpg">'; ?>
                </div>
                <div class="dcbm-car-info">
                    <h3><?php the_title(); ?></h3>
                    <div class="dcbm-car-specs"><span><?php echo ucfirst($trans); ?></span><span>👥 <?php echo $capacity; ?> seats</span></div>
                    <div class="dcbm-car-location">📍 <?php echo esc_html($location_name); ?></div>
                    <div class="dcbm-car-price-wrapper">
                        <button class="dcbm-show-price-btn">Show Price</button>
                        <span class="dcbm-price-display" style="display:none;">PKR <?php echo number_format($price); ?> <span class="dcbm-price-per-day">/ day</span></span>
                    </div>
                    <div class="dcbm-car-buttons">
                        <a href="<?php the_permalink(); ?>" class="dcbm-btn dcbm-btn-outline">Details</a>
                        <button class="dcbm-btn dcbm-btn-primary book-now" data-id="<?php echo get_the_ID(); ?>" data-price="<?php echo $price; ?>">Book Now</button>
                    </div>
                </div>
            </div>
        <?php endwhile;
    else:
        echo '<div class="dcbm-no-results">No cars available.</div>';
    endif;
    wp_reset_postdata();
    $html = ob_get_clean();
    $total_pages = $cars->max_num_pages;
    wp_send_json_success(array('html' => $html, 'total_pages' => $total_pages, 'current_page' => $paged));
}

function dcbm_ajax_load_more_listing() {
    check_ajax_referer('dcbm_nonce','nonce');
    $paged = intval($_POST['paged']);
    $per_page = 4;
    $args = array(
        'post_type' => 'dcbm_car',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'meta_query' => array(array('key' => '_dcbm_availability', 'value' => 'available'))
    );
    $cars = new WP_Query($args);
    ob_start();
    if ($cars->have_posts()):
        while ($cars->have_posts()): $cars->the_post();
            $price = get_post_meta(get_the_ID(), '_dcbm_price_per_day', true);
            $trans = get_post_meta(get_the_ID(), '_dcbm_transmission', true);
            $capacity = get_post_meta(get_the_ID(), '_dcbm_passenger_capacity', true);
            $loc_id = get_post_meta(get_the_ID(), '_dcbm_location_id', true);
            $location_name = $loc_id ? get_the_title($loc_id) : '';
            ?>
            <div class="dcbm-car-card">
                <div class="dcbm-car-image"><?php if(has_post_thumbnail()) the_post_thumbnail('medium'); ?></div>
                <div class="dcbm-car-info">
                    <h3><?php the_title(); ?></h3>
                    <div class="dcbm-car-specs"><span><?php echo ucfirst($trans); ?></span><span>👥 <?php echo $capacity; ?> seats</span></div>
                    <div class="dcbm-car-location">📍 <?php echo esc_html($location_name); ?></div>
                    <div class="dcbm-car-price-wrapper">
                        <button class="dcbm-show-price-btn">Show Price</button>
                        <span class="dcbm-price-display" style="display:none;">PKR <?php echo number_format($price); ?> <span class="dcbm-price-per-day">/ day</span></span>
                    </div>
                    <div class="dcbm-car-buttons"><a href="<?php the_permalink(); ?>" class="dcbm-btn dcbm-btn-outline">Details</a><button class="dcbm-btn dcbm-btn-primary book-now" data-id="<?php echo get_the_ID(); ?>" data-price="<?php echo $price; ?>">Book Now</button></div>
                </div>
            </div>
        <?php endwhile;
    endif;
    wp_reset_postdata();
    wp_send_json_success(array('html' => ob_get_clean()));
}

// Rest of AJAX handlers
function dcbm_ajax_update_booking_status() {
    check_ajax_referer('dcbm_nonce','nonce');
    if(!current_user_can('manage_options')) wp_die('Unauthorized');
    global $wpdb;
    $updated = $wpdb->update($wpdb->prefix.'dcbm_bookings', array('status'=>sanitize_text_field($_POST['status'])), array('id'=>intval($_POST['booking_id'])));
    wp_send_json_success($updated ? 'Status updated' : 'Update failed');
}

function dcbm_single_car_template($single) {
    global $post;
    if($post->post_type == 'dcbm_car'){
        $template = DCBM_PLUGIN_DIR.'templates/single-car.php';
        if(file_exists($template)) return $template;
    }
    return $single;
}

// SHORTCODES
function dcbm_advanced_search_shortcode() {
    $locations = get_posts(array('post_type' => 'dcbm_location', 'posts_per_page' => -1, 'orderby' => 'title'));
    ob_start();
    ?>
    <div class="dcbm-advanced-search-container">
        <form id="dcbm-advanced-search-form" class="dcbm-advanced-search-form">
            <div class="dcbm-search-row">
                <div class="dcbm-search-field"><label>Location</label><select name="location_id" id="adv_location_id"><option value="">Any Location</option><?php foreach($locations as $loc): ?><option value="<?php echo $loc->ID; ?>"><?php echo esc_html($loc->post_title); ?></option><?php endforeach; ?></select></div>
                <div class="dcbm-search-field"><label>Booking From</label><input type="date" name="pickup_date" id="adv_pickup_date"></div>
                <div class="dcbm-search-field"><label>Number of Days</label><input type="number" name="days" id="adv_days" min="1" value="1"></div>
                <div class="dcbm-search-field"><button type="submit" class="dcbm-search-submit">Search</button></div>
            </div>
        </form>
        <div id="dcbm-advanced-results" class="dcbm-cars-grid"></div>
        <div id="dcbm-load-more-container" style="text-align:center;margin-top:20px;"><button id="dcbm-load-more-btn" class="dcbm-load-more" style="display:none;">Load More</button></div>
    </div>
    <?php
    return ob_get_clean();
}

function dcbm_car_listing_loadmore_shortcode() {
    ob_start();
    ?>
    <div class="dcbm-car-listing-wrapper">
        <div id="dcbm-listing-grid" class="dcbm-cars-grid"></div>
        <div style="text-align:center;margin-top:20px;"><button id="dcbm-listing-load-more" class="dcbm-load-more">Load More</button></div>
    </div>
    <?php
    return ob_get_clean();
}

function dcbm_featured_cars_shortcode($atts) {
    $atts = shortcode_atts(array('per_page'=>6), $atts);
    $cars = new WP_Query(array('post_type'=>'dcbm_car','posts_per_page'=>$atts['per_page'],'meta_key'=>'_dcbm_availability','meta_value'=>'available'));
    ob_start();
    ?>
    <div class="dcbm-featured-cars"><div class="dcbm-cars-grid"><?php while($cars->have_posts()):$cars->the_post(); $price=get_post_meta(get_the_ID(),'_dcbm_price_per_day',true); ?><div class="dcbm-car-card"><div class="dcbm-car-image"><?php if(has_post_thumbnail()) the_post_thumbnail('medium'); ?></div><div class="dcbm-car-info"><h3><?php the_title(); ?></h3><div class="dcbm-car-price">PKR <?php echo number_format($price); ?>/day</div><button class="dcbm-btn dcbm-btn-primary book-now" data-id="<?php echo get_the_ID(); ?>" data-price="<?php echo $price; ?>">Book Now</button></div></div><?php endwhile; wp_reset_postdata(); ?></div></div>
    <?php
    return ob_get_clean();
}

function dcbm_cars_listing_simple_shortcode($atts) {
    return dcbm_car_listing_loadmore_shortcode();
}

// Booking Modal
function dcbm_booking_modal() { ?>
    <div id="dcbm-booking-modal" class="dcbm-modal" style="display:none;">
        <div class="dcbm-modal-content">
            <div class="dcbm-modal-header"><h2>Book This Car</h2><button class="dcbm-modal-close">&times;</button></div>
            <div class="dcbm-modal-body">
                <form id="dcbm-booking-form">
                    <input type="hidden" name="car_id" id="modal_car_id">
                    <div class="dcbm-form-group"><label>Car Model</label><input type="text" id="car_model_display" readonly style="background:#f5f5f5;"></div>
                    <h3>Customer Information</h3>
                    <div class="dcbm-form-row"><div class="dcbm-form-group"><label>Full Name *</label><input type="text" id="customer_name" required></div><div class="dcbm-form-group"><label>Phone *</label><input type="tel" id="customer_phone" required></div></div>
                    <div class="dcbm-form-group"><label>Email *</label><input type="email" id="customer_email" required></div>
                    <h3>Trip Details</h3>
                    <div class="dcbm-form-row"><div class="dcbm-form-group"><label>Pickup Location *</label><input type="text" id="pickup_location" required></div><div class="dcbm-form-group"><label>Destination *</label><input type="text" id="destination" required></div></div>
                    <div class="dcbm-form-row"><div class="dcbm-form-group"><label>Pickup Date *</label><input type="date" id="pickup_date" required></div><div class="dcbm-form-group"><label>Number of Days *</label><input type="number" id="number_of_days" min="1" value="1" required></div></div>
                    <div class="dcbm-form-group"><label><input type="checkbox" id="driver_needed" value="yes"> Driver needed?</label></div>
                    <h3>Extra Services</h3>
                    <div id="dcbm-extra-services-list" class="dcbm-services-list"></div>
                    <div class="dcbm-price-summary"><div class="dcbm-price-row"><span>Car Rent</span><span id="dcbm-car-rent">PKR 0</span></div><div class="dcbm-price-row"><span>Extra Services</span><span id="dcbm-services-total">PKR 0</span></div><div class="dcbm-price-row dcbm-price-total"><span>Total Amount</span><span id="dcbm-total-amount">PKR 0</span></div></div>
                    <button type="submit" class="dcbm-submit-btn">Submit Booking Request</button>
                </form>
            </div>
        </div>
    </div>
<?php }