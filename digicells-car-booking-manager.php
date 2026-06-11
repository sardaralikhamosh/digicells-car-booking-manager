<?php
/**
 * Plugin Name: Digicells Car Booking Manager
 * Version: 1.5.0
 * Author: Sardar Ali Khamosh (Digicells)
 * Text Domain: digicells-cbm
 */

if (!defined('ABSPATH')) exit;

define('DCBM_VERSION', '1.5.0');
define('DCBM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DCBM_PLUGIN_URL', plugin_dir_url(__FILE__));

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
        pickup_date datetime NOT NULL,
        return_date datetime NOT NULL,
        pickup_location varchar(255) NOT NULL,
        destination varchar(255) DEFAULT '',
        driver_needed varchar(10) DEFAULT 'no',
        number_of_days int NOT NULL,
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
        status varchar(20) DEFAULT 'enabled',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($bookings_sql);
    dbDelta($services_sql);
    
    $service_count = $wpdb->get_var("SELECT COUNT(*) FROM $services_table");
    if ($service_count == 0) {
        $default_services = array(
            array('Driver', '👨‍✈️', 'paid', 1000),
            array('Fuel', '⛽', 'paid', 2000),
            array('Meal', '🍽️', 'free', 0),
            array('Accommodation', '🏨', 'paid', 5000),
            array('Airport Pickup', '✈️', 'paid', 1500),
            array('Child Seat', '👶', 'paid', 500),
            array('Tour Guide', '🗺️', 'paid', 3000),
        );
        foreach ($default_services as $service) {
            $wpdb->insert($services_table, array(
                'name' => $service[0], 'icon' => $service[1],
                'price_type' => $service[2], 'price' => $service[3], 'status' => 'enabled'
            ), array('%s','%s','%s','%f','%s'));
        }
    }
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'dcbm_deactivate');
function dcbm_deactivate() { flush_rewrite_rules(); }

add_action('plugins_loaded', 'dcbm_init');
function dcbm_init() {
    load_plugin_textdomain('digicells-cbm', false, dirname(plugin_basename(__FILE__)) . '/languages');
    add_action('init', 'dcbm_register_post_types');
    add_action('init', 'dcbm_register_taxonomies');
    add_action('add_meta_boxes', 'dcbm_add_meta_boxes');
    add_action('save_post_dcbm_car', 'dcbm_save_car_meta');
    add_action('admin_enqueue_scripts', 'dcbm_admin_scripts');
    add_action('wp_enqueue_scripts', 'dcbm_frontend_scripts');
    add_action('admin_menu', 'dcbm_admin_menus');
    
    add_action('wp_ajax_dcbm_get_extra_services', 'dcbm_ajax_get_extra_services');
    add_action('wp_ajax_nopriv_dcbm_get_extra_services', 'dcbm_ajax_get_extra_services');
    add_action('wp_ajax_dcbm_submit_booking', 'dcbm_ajax_submit_booking');
    add_action('wp_ajax_nopriv_dcbm_submit_booking', 'dcbm_ajax_submit_booking');
    add_action('wp_ajax_dcbm_advanced_search', 'dcbm_ajax_advanced_search');
    add_action('wp_ajax_nopriv_dcbm_advanced_search', 'dcbm_ajax_advanced_search');
    add_action('wp_ajax_dcbm_update_booking_status', 'dcbm_ajax_update_booking_status');
    
    add_shortcode('digicells_advanced_search', 'dcbm_advanced_search_shortcode');
    add_shortcode('digicells_car_listing', 'dcbm_car_listing_shortcode');
    add_shortcode('digicells_featured_cars', 'dcbm_featured_cars_shortcode');
    add_shortcode('digicells_car_search', 'dcbm_search_form_shortcode');
    add_shortcode('digicells_cars_listing_simple', 'dcbm_cars_listing_simple_shortcode');
    
    add_filter('single_template', 'dcbm_single_car_template');
    add_action('wp_footer', 'dcbm_booking_modal');
}

function dcbm_register_post_types() {
    $labels = array(
        'name' => __('Cars', 'digicells-cbm'), 'singular_name' => __('Car', 'digicells-cbm'),
        'menu_name' => __('Car Booking', 'digicells-cbm'), 'add_new' => __('Add New Car', 'digicells-cbm'),
        'add_new_item' => __('Add New Car', 'digicells-cbm'), 'edit_item' => __('Edit Car', 'digicells-cbm'),
        'new_item' => __('New Car', 'digicells-cbm'), 'view_item' => __('View Car', 'digicells-cbm'),
        'search_items' => __('Search Cars', 'digicells-cbm'), 'not_found' => __('No cars found', 'digicells-cbm'),
        'not_found_in_trash' => __('No cars found in trash', 'digicells-cbm'),
    );
    $args = array(
        'labels' => $labels, 'public' => true, 'publicly_queryable' => true, 'show_ui' => true,
        'show_in_menu' => true, 'query_var' => true, 'rewrite' => array('slug' => 'car'),
        'capability_type' => 'post', 'has_archive' => true, 'hierarchical' => false,
        'menu_position' => 20, 'menu_icon' => 'dashicons-car', 'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        'show_in_rest' => true,
    );
    register_post_type('dcbm_car', $args);
}

function dcbm_register_taxonomies() {
    $labels = array(
        'name' => __('Car Categories', 'digicells-cbm'), 'singular_name' => __('Car Category', 'digicells-cbm'),
        'search_items' => __('Search Categories', 'digicells-cbm'), 'all_items' => __('All Categories', 'digicells-cbm'),
        'parent_item' => __('Parent Category', 'digicells-cbm'), 'parent_item_colon' => __('Parent Category:', 'digicells-cbm'),
        'edit_item' => __('Edit Category', 'digicells-cbm'), 'update_item' => __('Update Category', 'digicells-cbm'),
        'add_new_item' => __('Add New Category', 'digicells-cbm'), 'new_item_name' => __('New Category Name', 'digicells-cbm'),
        'menu_name' => __('Categories', 'digicells-cbm'),
    );
    $args = array(
        'hierarchical' => true, 'labels' => $labels, 'show_ui' => true, 'show_admin_column' => true,
        'query_var' => true, 'rewrite' => array('slug' => 'car-category'), 'show_in_rest' => true,
    );
    register_taxonomy('dcbm_car_category', array('dcbm_car'), $args);
    
    $existing_cats = get_terms(array('taxonomy' => 'dcbm_car_category', 'hide_empty' => false));
    if (empty($existing_cats)) {
        $default_categories = array('SUV', 'Sedan', 'Luxury', 'Economy', 'Van', '4x4');
        foreach ($default_categories as $category) wp_insert_term($category, 'dcbm_car_category');
    }
}

function dcbm_add_meta_boxes() {
    add_meta_box('dcbm_car_details', __('Car Details', 'digicells-cbm'), 'dcbm_render_car_details_metabox', 'dcbm_car', 'normal', 'high');
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
    $pickup_location = get_post_meta($post->ID, '_dcbm_pickup_location', true);
    $availability = get_post_meta($post->ID, '_dcbm_availability', true);
    $car_color = get_post_meta($post->ID, '_dcbm_car_color', true);
    $unlimited_miles = get_post_meta($post->ID, '_dcbm_unlimited_miles', true);
    if (!$availability) $availability = 'available';
    ?>
    <style>.dcbm-meta-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;padding:15px}.dcbm-meta-field{margin-bottom:15px}.dcbm-meta-field label{font-weight:600;display:block;margin-bottom:8px}.dcbm-meta-field input,.dcbm-meta-field select{width:100%;padding:8px 12px;border:1px solid #ccd0d4;border-radius:4px}</style>
    <div class="dcbm-meta-grid">
        <div class="dcbm-meta-field"><label>Car Model:</label><input type="text" name="dcbm_car_model" value="<?php echo esc_attr($car_model); ?>"></div>
        <div class="dcbm-meta-field"><label>Manufacturer:</label><input type="text" name="dcbm_manufacturer" value="<?php echo esc_attr($manufacturer); ?>"></div>
        <div class="dcbm-meta-field"><label>Registration Year:</label><select name="dcbm_registration_year"><option value="">Select Year</option><?php for($y=date('Y');$y>=2000;$y--): ?><option value="<?php echo $y; ?>" <?php selected($reg_year,$y); ?>><?php echo $y; ?></option><?php endfor; ?></select></div>
        <div class="dcbm-meta-field"><label>Car Number:</label><input type="text" name="dcbm_car_number" value="<?php echo esc_attr($car_number); ?>"></div>
        <div class="dcbm-meta-field"><label>Transmission:</label><select name="dcbm_transmission"><option value="automatic" <?php selected($transmission,'automatic'); ?>>Automatic</option><option value="manual" <?php selected($transmission,'manual'); ?>>Manual</option></select></div>
        <div class="dcbm-meta-field"><label>Fuel Type:</label><select name="dcbm_fuel_type"><option value="petrol" <?php selected($fuel_type,'petrol'); ?>>Petrol</option><option value="diesel" <?php selected($fuel_type,'diesel'); ?>>Diesel</option><option value="hybrid" <?php selected($fuel_type,'hybrid'); ?>>Hybrid</option><option value="electric" <?php selected($fuel_type,'electric'); ?>>Electric</option></select></div>
        <div class="dcbm-meta-field"><label>Passenger Capacity (Seats):</label><input type="number" name="dcbm_passenger_capacity" value="<?php echo esc_attr($passenger_capacity); ?>" min="1" max="20"></div>
        <div class="dcbm-meta-field"><label>Price Per Day (PKR):</label><input type="number" name="dcbm_price_per_day" value="<?php echo esc_attr($price); ?>"></div>
        <div class="dcbm-meta-field"><label>Pickup Location:</label><input type="text" name="dcbm_pickup_location" value="<?php echo esc_attr($pickup_location); ?>"></div>
        <div class="dcbm-meta-field"><label>Availability Status:</label><select name="dcbm_availability"><option value="available" <?php selected($availability,'available'); ?>>Available</option><option value="not_available" <?php selected($availability,'not_available'); ?>>Not Available</option><option value="maintenance" <?php selected($availability,'maintenance'); ?>>Maintenance</option></select></div>
        <div class="dcbm-meta-field"><label>Car Color:</label><input type="text" name="dcbm_car_color" value="<?php echo esc_attr($car_color); ?>" placeholder="e.g., Red, Black, White"></div>
        <div class="dcbm-meta-field"><label>Unlimited Miles:</label><select name="dcbm_unlimited_miles"><option value="yes" <?php selected($unlimited_miles,'yes'); ?>>Yes</option><option value="no" <?php selected($unlimited_miles,'no'); ?>>No</option></select></div>
    </div>
    <?php
}

function dcbm_save_car_meta($post_id) {
    if (!isset($_POST['dcbm_car_meta_nonce']) || !wp_verify_nonce($_POST['dcbm_car_meta_nonce'], 'dcbm_save_car_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    $fields = array('dcbm_car_model','dcbm_manufacturer','dcbm_registration_year','dcbm_car_number','dcbm_transmission','dcbm_fuel_type','dcbm_passenger_capacity','dcbm_price_per_day','dcbm_pickup_location','dcbm_availability','dcbm_car_color','dcbm_unlimited_miles');
    foreach ($fields as $field) if (isset($_POST[$field])) update_post_meta($post_id, '_'.$field, sanitize_text_field($_POST[$field]));
}

function dcbm_admin_menus() {
    add_submenu_page('edit.php?post_type=dcbm_car', __('Extra Services', 'digicells-cbm'), __('Extra Services', 'digicells-cbm'), 'manage_options', 'dcbm-services', 'dcbm_services_page');
    add_submenu_page('edit.php?post_type=dcbm_car', __('Bookings', 'digicells-cbm'), __('Bookings', 'digicells-cbm'), 'manage_options', 'dcbm-bookings', 'dcbm_bookings_page');
}

function dcbm_services_page() {
    global $wpdb;
    $services_table = $wpdb->prefix . 'dcbm_extra_services';
    if (isset($_POST['dcbm_add_service']) && wp_verify_nonce($_POST['dcbm_service_nonce'], 'dcbm_add_service')) {
        $wpdb->insert($services_table, array('name'=>sanitize_text_field($_POST['service_name']),'icon'=>sanitize_text_field($_POST['service_icon']),'price_type'=>sanitize_text_field($_POST['price_type']),'price'=>floatval($_POST['price']),'status'=>'enabled'));
        echo '<div class="notice notice-success"><p>Service added!</p></div>';
    }
    if (isset($_GET['delete_service']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_service')) {
        $wpdb->delete($services_table, array('id'=>intval($_GET['delete_service'])));
        echo '<div class="notice notice-success"><p>Service deleted!</p></div>';
    }
    $services = $wpdb->get_results("SELECT * FROM $services_table ORDER BY id ASC");
    ?>
    <div class="wrap"><h1>Extra Services</h1><div class="card" style="max-width:500px;margin-bottom:30px;"><h3>Add New Service</h3><form method="post"><?php wp_nonce_field('dcbm_add_service','dcbm_service_nonce'); ?><table class="form-table"><tr><th><label>Service Name:</label></th><td><input type="text" name="service_name" class="regular-text" required></td></tr><tr><th><label>Icon (Emoji):</label></th><td><input type="text" name="service_icon" class="regular-text" placeholder="🚗"></td></tr><tr><th><label>Price Type:</label></th><td><select name="price_type"><option value="free">Free</option><option value="paid">Paid</option></select></td></tr><tr><th><label>Price (PKR):</label></th><td><input type="number" name="price" value="0"></td></tr></table><?php submit_button('Add Service','primary','dcbm_add_service'); ?></form></div><h3>Existing Services</h3><table class="wp-list-table widefat fixed striped"><thead><tr><th>Icon</th><th>Name</th><th>Price Type</th><th>Price</th><th>Actions</th></tr></thead><tbody><?php foreach($services as $service): ?><tr><td><?php echo esc_html($service->icon); ?></td><td><?php echo esc_html($service->name); ?></td><td><?php echo ucfirst($service->price_type); ?></td><td><?php echo $service->price_type=='paid'?'PKR '.number_format($service->price):'Free'; ?></td><td><a href="<?php echo wp_nonce_url(add_query_arg('delete_service',$service->id),'delete_service'); ?>" class="button button-small" onclick="return confirm('Delete?')">Delete</a></td></tr><?php endforeach; ?></tbody></table></div>
    <?php
}

function dcbm_bookings_page() {
    global $wpdb;
    $bookings = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dcbm_bookings ORDER BY id DESC");
    ?>
    <div class="wrap"><h1>Car Bookings</h1><table class="wp-list-table widefat fixed striped"><thead><tr><th>Booking #</th><th>Customer</th><th>Car</th><th>Pickup Date</th><th>Return Date</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach($bookings as $booking): $car_title=get_the_title($booking->car_id); ?><tr><td><?php echo esc_html($booking->booking_number); ?></td><td><?php echo esc_html($booking->customer_name); ?><br><small><?php echo esc_html($booking->customer_email); ?></small></td><td><?php echo esc_html($car_title); ?></td><td><?php echo date('M j, Y H:i',strtotime($booking->pickup_date)); ?></td><td><?php echo date('M j, Y H:i',strtotime($booking->return_date)); ?></td><td>PKR <?php echo number_format($booking->total_amount); ?></td><td><select class="dcbm-booking-status" data-id="<?php echo $booking->id; ?>"><option value="pending" <?php selected($booking->status,'pending'); ?>>Pending</option><option value="approved" <?php selected($booking->status,'approved'); ?>>Approved</option><option value="rejected" <?php selected($booking->status,'rejected'); ?>>Rejected</option><option value="completed" <?php selected($booking->status,'completed'); ?>>Completed</option></select></td><td><button class="button button-small view-booking" data-id="<?php echo $booking->id; ?>">View</button></td></tr><?php endforeach; ?></tbody></table></div><script>jQuery(document).ready(function($){$('.dcbm-booking-status').on('change',function(){var id=$(this).data('id'),status=$(this).val();$.ajax({url:ajaxurl,type:'POST',data:{action:'dcbm_update_booking_status',booking_id:id,status:status,nonce:'<?php echo wp_create_nonce("dcbm_nonce"); ?>'},success:function(r){if(r.success) location.reload();}});});});</script>
    <?php
}

function dcbm_admin_scripts($hook) { if(strpos($hook,'dcbm_car')!==false) wp_enqueue_media(); }

function dcbm_frontend_scripts() {
    wp_enqueue_style('dashicons');
    wp_enqueue_style('dcbm-frontend', DCBM_PLUGIN_URL.'assets/css/frontend-style.css', array(), DCBM_VERSION);
    wp_enqueue_script('dcbm-frontend', DCBM_PLUGIN_URL.'assets/js/frontend-script.js', array('jquery'), DCBM_VERSION, true);
    wp_localize_script('dcbm-frontend', 'dcbm_ajax', array('ajax_url'=>admin_url('admin-ajax.php'), 'nonce'=>wp_create_nonce('dcbm_nonce')));
}

function dcbm_ajax_get_extra_services() { check_ajax_referer('dcbm_nonce','nonce'); global $wpdb; $services=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}dcbm_extra_services WHERE status='enabled'"); wp_send_json_success($services); }

function dcbm_ajax_submit_booking() {
    check_ajax_referer('dcbm_nonce','nonce'); 
    global $wpdb; 
    $bookings_table = $wpdb->prefix.'dcbm_bookings'; 
    $booking_number = 'BK-'.strtoupper(uniqid());
    
    $pickup_datetime = sanitize_text_field($_POST['pickup_date']).' '.sanitize_text_field($_POST['pickup_time']);
    $return_datetime = sanitize_text_field($_POST['return_date']).' '.sanitize_text_field($_POST['return_time']);
    $driver_needed = isset($_POST['driver_needed']) ? sanitize_text_field($_POST['driver_needed']) : 'no';
    
    $data = array(
        'booking_number' => $booking_number,
        'car_id' => intval($_POST['car_id']),
        'customer_name' => sanitize_text_field($_POST['name']),
        'customer_phone' => sanitize_text_field($_POST['phone']),
        'customer_email' => sanitize_email($_POST['email']),
        'pickup_date' => $pickup_datetime,
        'return_date' => $return_datetime,
        'pickup_location' => sanitize_text_field($_POST['pickup_location']),
        'destination' => sanitize_text_field($_POST['destination']),
        'driver_needed' => $driver_needed,
        'number_of_days' => intval($_POST['number_of_days']),
        'car_rental_price' => floatval($_POST['car_rental_price']),
        'extra_services_total' => floatval($_POST['extra_services_total']),
        'total_amount' => floatval($_POST['total_amount']),
        'selected_services' => sanitize_text_field($_POST['selected_services']),
        'status' => 'pending'
    );
    
    if($wpdb->insert($bookings_table, $data)) {
        $car_id = $data['car_id'];
        $car_title = get_the_title($car_id);
        $car_model = get_post_meta($car_id, '_dcbm_car_model', true);
        $car_color = get_post_meta($car_id, '_dcbm_car_color', true);
        $car_price_per_day = get_post_meta($car_id, '_dcbm_price_per_day', true);
        
        $selected = json_decode($data['selected_services'], true);
        $services_list = '';
        $services_table = $wpdb->prefix.'dcbm_extra_services';
        if(!empty($selected)) {
            foreach($selected as $sid=>$price) {
                $sname = $wpdb->get_var($wpdb->prepare("SELECT name FROM $services_table WHERE id=%d", $sid));
                if($sname) $services_list .= "<li>{$sname} - PKR ".number_format($price)."</li>";
            }
        } else {
            $services_list = '<li>No extra services selected</li>';
        }
        
        // Admin email
        $admin_email = get_option('admin_email');
        $subject = "New Car Booking Inquiry - {$booking_number}";
        $message = "<html><body>
            <h2>New Booking Request Received</h2>
            <p><strong>Booking Number:</strong> {$booking_number}</p>
            <h3>Customer Details</h3>
            <p><strong>Name:</strong> {$data['customer_name']}<br>
            <strong>Phone:</strong> {$data['customer_phone']}<br>
            <strong>Email:</strong> {$data['customer_email']}</p>
            <h3>Car Details</h3>
            <p><strong>Car:</strong> {$car_title}<br>
            <strong>Model:</strong> {$car_model}<br>
            <strong>Color:</strong> {$car_color}<br>
            <strong>Price per day:</strong> PKR ".number_format($car_price_per_day)."</p>
            <h3>Trip Details</h3>
            <p><strong>Pickup Location:</strong> {$data['pickup_location']}<br>
            <strong>Destination:</strong> {$data['destination']}<br>
            <strong>Pickup Date/Time:</strong> {$data['pickup_date']}<br>
            <strong>Return Date/Time:</strong> {$data['return_date']}<br>
            <strong>Number of Days:</strong> {$data['number_of_days']}<br>
            <strong>Driver Needed:</strong> ".($driver_needed == 'yes' ? 'Yes' : 'No')."</p>
            <h3>Extra Services</h3>
            <ul>{$services_list}</ul>
            <h3>Price Breakdown</h3>
            <p><strong>Car Rental:</strong> PKR ".number_format($data['car_rental_price'])."<br>
            <strong>Extra Services:</strong> PKR ".number_format($data['extra_services_total'])."<br>
            <strong>Total Amount:</strong> PKR ".number_format($data['total_amount'])."</p>
            <p><em>Login to admin panel to manage this booking.</em></p>
        </body></html>";
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($admin_email, $subject, $message, $headers);
        
        // Customer email
        $customer_subject = "Thank you for your inquiry - Hamari Booking";
        $customer_message = "<html><body>
            <h2>Thank you for showing interest in Hamari Booking!</h2>
            <p>Dear {$data['customer_name']},</p>
            <p>Your booking request has been received. Our team is working on quotes and will revert back shortly with the best available offers.</p>
            <p><strong>Booking Reference:</strong> {$booking_number}</p>
            <h3>Your Request Summary</h3>
            <p><strong>Car:</strong> {$car_title} ({$car_model})<br>
            <strong>Pickup:</strong> {$data['pickup_location']} on {$data['pickup_date']}<br>
            <strong>Destination:</strong> {$data['destination']}<br>
            <strong>Return:</strong> {$data['return_date']}<br>
            <strong>Driver Needed:</strong> ".($driver_needed == 'yes' ? 'Yes' : 'No')."</p>
            <p>We will contact you shortly via email or phone.</p>
            <p>Regards,<br>Hamari Booking Team</p>
        </body></html>";
        wp_mail($data['customer_email'], $customer_subject, $customer_message, $headers);
        
        wp_send_json_success(array('message' => 'Thank you! Our team will contact you shortly with the best offers.'));
    } else {
        wp_send_json_error('Failed to submit booking. Please try again.');
    }
}

function dcbm_ajax_advanced_search() {
    check_ajax_referer('dcbm_nonce','nonce');
    $pickup_location = sanitize_text_field($_POST['pickup_location']);
    $pickup_datetime = sanitize_text_field($_POST['pickup_date']).' '.sanitize_text_field($_POST['pickup_time']);
    $return_datetime = sanitize_text_field($_POST['return_date']).' '.sanitize_text_field($_POST['return_time']);
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
    $per_page = 8; // 2 rows × 4 columns = 8 cars per page
    
    $args = array(
        'post_type' => 'dcbm_car',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array()
    );
    
    if(!empty($pickup_location)) {
        $args['meta_query'][] = array(
            'relation' => 'OR',
            array('key' => '_dcbm_pickup_location', 'value' => $pickup_location, 'compare' => 'LIKE'),
            array('post_title' => $pickup_location, 'compare' => 'LIKE')
        );
    }
    
    $all_cars = new WP_Query($args);
    $available_ids = array();
    global $wpdb;
    $bookings_table = $wpdb->prefix.'dcbm_bookings';
    
    while($all_cars->have_posts()) {
        $all_cars->the_post();
        $cid = get_the_ID();
        $avail = get_post_meta($cid, '_dcbm_availability', true);
        if($avail != 'available') continue;
        
        $overlap = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table 
             WHERE car_id=%d AND status NOT IN ('rejected','completed')
             AND ((pickup_date<=%s AND return_date>=%s) 
               OR (pickup_date<=%s AND return_date>=%s)
               OR (pickup_date>=%s AND return_date<=%s))",
            $cid, $return_datetime, $pickup_datetime, 
            $pickup_datetime, $return_datetime,
            $pickup_datetime, $return_datetime
        ));
        if($overlap == 0) $available_ids[] = $cid;
    }
    wp_reset_postdata();
    
    $total = count($available_ids);
    $total_pages = ceil($total / $per_page);
    $offset = ($paged - 1) * $per_page;
    $paged_ids = array_slice($available_ids, $offset, $per_page);
    
    if(empty($paged_ids)) {
        echo '<div class="dcbm-no-results">No cars available for the selected criteria.</div>';
        wp_die();
    }
    
    $args2 = array(
        'post_type' => 'dcbm_car',
        'post__in' => $paged_ids,
        'orderby' => 'post__in',
        'posts_per_page' => $per_page
    );
    $cars = new WP_Query($args2);
    ob_start();
    if($cars->have_posts()):
        echo '<div class="dcbm-cars-grid">';
        while($cars->have_posts()): $cars->the_post();
            $price = get_post_meta(get_the_ID(), '_dcbm_price_per_day', true);
            $trans = get_post_meta(get_the_ID(), '_dcbm_transmission', true);
            $capacity = get_post_meta(get_the_ID(), '_dcbm_passenger_capacity', true);
            $location = get_post_meta(get_the_ID(), '_dcbm_pickup_location', true);
            $availability = get_post_meta(get_the_ID(), '_dcbm_availability', true);
            ?>
            <div class="dcbm-car-card">
                <div class="dcbm-car-image">
                    <?php if(has_post_thumbnail()) the_post_thumbnail('medium'); 
                    else echo '<img src="'.DCBM_PLUGIN_URL.'assets/images/placeholder-car.jpg">'; ?>
                    <span class="dcbm-availability dcbm-availability-<?php echo $availability; ?>">
                        <?php echo ucfirst(str_replace('_',' ',$availability)); ?>
                    </span>
                </div>
                <div class="dcbm-car-info">
                    <h3><?php the_title(); ?></h3>
                    <div class="dcbm-car-specs">
                        <span><?php echo ucfirst($trans); ?></span>
                        <span>👥 <?php echo $capacity; ?> seats</span>
                    </div>
                    <div class="dcbm-car-location">📍 <?php echo esc_html($location); ?></div>
                    <div class="dcbm-car-price">PKR <?php echo number_format($price); ?> <span>/ day</span></div>
                    <div class="dcbm-car-buttons">
                        <a href="<?php the_permalink(); ?>" class="dcbm-btn dcbm-btn-outline">Details</a>
                        <?php if($availability == 'available'): ?>
                            <button class="dcbm-btn dcbm-btn-primary book-now" data-id="<?php echo get_the_ID(); ?>" data-price="<?php echo $price; ?>">Book Now</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile;
        echo '</div>';
        if($total_pages > 1):
            echo '<div class="dcbm-pagination">';
            if($paged > 1) echo '<button class="dcbm-page-btn dcbm-prev" data-page="'.($paged-1).'">‹ Prev</button>';
            for($i=1; $i<=$total_pages; $i++):
                $active = ($i == $paged) ? 'active' : '';
                echo '<button class="dcbm-page-btn '.$active.'" data-page="'.$i.'">'.$i.'</button>';
            endfor;
            if($paged < $total_pages) echo '<button class="dcbm-page-btn dcbm-next" data-page="'.($paged+1).'">Next ›</button>';
            echo '</div>';
        endif;
    else:
        echo '<div class="dcbm-no-results">No cars match your filters.</div>';
    endif;
    wp_reset_postdata();
    wp_die(ob_get_clean());
}

function dcbm_ajax_update_booking_status() { 
    check_ajax_referer('dcbm_nonce','nonce'); 
    if(!current_user_can('manage_options')) wp_die('Unauthorized'); 
    global $wpdb; 
    $updated = $wpdb->update($wpdb->prefix.'dcbm_bookings', array('status'=>sanitize_text_field($_POST['status'])), array('id'=>intval($_POST['booking_id'])), array('%s'), array('%d')); 
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

// Shortcodes (unchanged but kept for compatibility)
function dcbm_car_listing_shortcode($atts){ $atts=shortcode_atts(array('per_page'=>12,'show_filter'=>'yes'),$atts); ob_start(); include DCBM_PLUGIN_DIR.'templates/car-listing.php'; return ob_get_clean(); }
function dcbm_featured_cars_shortcode($atts){ $atts=shortcode_atts(array('per_page'=>6),$atts); $cars=new WP_Query(array('post_type'=>'dcbm_car','posts_per_page'=>$atts['per_page'],'meta_key'=>'_dcbm_availability','meta_value'=>'available')); ob_start(); ?><div class="dcbm-featured-cars"><div class="dcbm-cars-grid"><?php while($cars->have_posts()):$cars->the_post(); $price=get_post_meta(get_the_ID(),'_dcbm_price_per_day',true); $trans=get_post_meta(get_the_ID(),'_dcbm_transmission',true); $capacity=get_post_meta(get_the_ID(),'_dcbm_passenger_capacity',true); $location=get_post_meta(get_the_ID(),'_dcbm_pickup_location',true); ?><div class="dcbm-car-card"><div class="dcbm-car-image"><?php if(has_post_thumbnail()) the_post_thumbnail('medium'); ?></div><div class="dcbm-car-info"><h3><?php the_title(); ?></h3><div class="dcbm-car-specs"><span><?php echo ucfirst($trans); ?></span><span>👥 <?php echo $capacity; ?> seats</span></div><div class="dcbm-car-location">📍 <?php echo esc_html($location); ?></div><div class="dcbm-car-price">PKR <?php echo number_format($price); ?> <span>/ day</span></div><div class="dcbm-car-buttons"><a href="<?php the_permalink(); ?>" class="dcbm-btn dcbm-btn-outline">Details</a><button class="dcbm-btn dcbm-btn-primary book-now" data-id="<?php echo get_the_ID(); ?>" data-price="<?php echo $price; ?>">Book Now</button></div></div></div><?php endwhile; wp_reset_postdata(); ?></div></div><?php return ob_get_clean(); }
function dcbm_search_form_shortcode(){ ob_start(); ?><div class="dcbm-search-form"><div class="dcbm-filters"><input type="text" id="dcbm-search-input" placeholder="Search cars..."><select id="dcbm-category-filter"><option value="">All Categories</option><?php $cats=get_terms('dcbm_car_category'); foreach($cats as $c) echo '<option value="'.esc_attr($c->slug).'">'.esc_html($c->name).'</option>'; ?></select><select id="dcbm-transmission-filter"><option value="">Transmission</option><option value="automatic">Automatic</option><option value="manual">Manual</option></select><button id="dcbm-search-btn">Search</button></div></div><?php return ob_get_clean(); }
function dcbm_cars_listing_simple_shortcode($atts){ $atts=shortcode_atts(array('per_page'=>12),$atts); ob_start(); ?><div class="dcbm-car-listing-wrapper"><div class="dcbm-cars-grid"><?php $cars=new WP_Query(array('post_type'=>'dcbm_car','posts_per_page'=>$atts['per_page'],'post_status'=>'publish')); if($cars->have_posts()): while($cars->have_posts()):$cars->the_post(); $price=get_post_meta(get_the_ID(),'_dcbm_price_per_day',true); $trans=get_post_meta(get_the_ID(),'_dcbm_transmission',true); $capacity=get_post_meta(get_the_ID(),'_dcbm_passenger_capacity',true); $availability=get_post_meta(get_the_ID(),'_dcbm_availability',true); if(!$availability) $availability='available'; $location=get_post_meta(get_the_ID(),'_dcbm_pickup_location',true); ?><div class="dcbm-car-card"><div class="dcbm-car-image"><?php if(has_post_thumbnail()) the_post_thumbnail('medium'); else echo '<img src="'.DCBM_PLUGIN_URL.'assets/images/placeholder-car.jpg">'; ?><span class="dcbm-availability dcbm-availability-<?php echo $availability; ?>"><?php echo ucfirst(str_replace('_',' ',$availability)); ?></span></div><div class="dcbm-car-info"><h3><?php the_title(); ?></h3><div class="dcbm-car-specs"><span><?php echo ucfirst($trans); ?></span><span>👥 <?php echo $capacity; ?> seats</span></div><div class="dcbm-car-location">📍 <?php echo esc_html($location); ?></div><div class="dcbm-car-price">PKR <?php echo number_format($price); ?> <span>/ day</span></div><div class="dcbm-car-buttons"><a href="<?php the_permalink(); ?>" class="dcbm-btn dcbm-btn-outline">Details</a><?php if($availability=='available'): ?><button class="dcbm-btn dcbm-btn-primary book-now" data-id="<?php echo get_the_ID(); ?>" data-price="<?php echo $price; ?>">Book Now</button><?php else: ?><button class="dcbm-btn" disabled>Not Available</button><?php endif; ?></div></div></div><?php endwhile; wp_reset_postdata(); else: ?><div>No cars found.</div><?php endif; ?></div></div><?php return ob_get_clean(); }

function dcbm_advanced_search_shortcode() {
    ob_start();
    ?>
    <style>
        /* Force the grid layout */
        .dcbm-cars-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 25px !important;
            margin-top: 30px !important;
        }
        @media (max-width: 992px) {
            .dcbm-cars-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
        @media (max-width: 576px) {
            .dcbm-cars-grid {
                grid-template-columns: 1fr !important;
            }
        }
        .dcbm-car-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .dcbm-car-image img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .dcbm-car-info {
            padding: 15px;
        }
        .dcbm-car-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .dcbm-btn-primary {
            background: #f68511;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
        }
        .dcbm-btn-outline {
            background: transparent;
            border: 1px solid #f68511;
            color: #f68511;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
        }
        .dcbm-pagination {
            text-align: center;
            margin-top: 30px;
        }
        .dcbm-page-btn {
            background: #fff;
            border: 1px solid #ddd;
            padding: 8px 14px;
            margin: 0 4px;
            border-radius: 6px;
            cursor: pointer;
        }
        .dcbm-page-btn.active {
            background: #f68511;
            color: white;
            border-color: #f68511;
        }
    </style>
    <div class="dcbm-advanced-search-container">
        <form id="dcbm-advanced-search-form" class="dcbm-advanced-search-form">
            <div class="dcbm-search-row">
                <div class="dcbm-search-field"><label>Pick-up location</label><input type="text" name="pickup_location" id="adv_pickup_location" placeholder="Airport, city or station"></div>
                <div class="dcbm-search-field"><label>Pick-up date</label><input type="date" name="pickup_date" id="adv_pickup_date" required></div>
                <div class="dcbm-search-field"><label>Time</label><select name="pickup_time" id="adv_pickup_time"><?php for($h=0;$h<24;$h++): $hour=str_pad($h,2,'0'); ?><option value="<?php echo $hour;?>:00"><?php echo $hour;?>:00</option><option value="<?php echo $hour;?>:30"><?php echo $hour;?>:30</option><?php endfor; ?></select></div>
                <div class="dcbm-search-field"><label>Drop-off date</label><input type="date" name="return_date" id="adv_return_date" required></div>
                <div class="dcbm-search-field"><label>Time</label><select name="return_time" id="adv_return_time"><?php for($h=0;$h<24;$h++): $hour=str_pad($h,2,'0'); ?><option value="<?php echo $hour;?>:00"><?php echo $hour;?>:00</option><option value="<?php echo $hour;?>:30"><?php echo $hour;?>:30</option><?php endfor; ?></select></div>
                <div class="dcbm-search-field"><button type="submit" class="dcbm-search-submit">Search</button></div>
            </div>
            <div class="dcbm-search-extra">
                <label><input type="checkbox" name="different_dropoff" id="different_dropoff"> Drop car off at different location</label>
                <label><input type="checkbox" name="driver_age" id="driver_age" checked> Driver aged between 30 - 65?</label>
            </div>
        </form>
        <div id="dcbm-advanced-results" class="dcbm-cars-grid"></div>
    </div>
    <?php
    return ob_get_clean();
}

function dcbm_booking_modal() { ?>
    <div id="dcbm-booking-modal" class="dcbm-modal" style="display:none;">
        <div class="dcbm-modal-content" style="max-width:600px;">
            <div class="dcbm-modal-header">
                <h2>Book This Car</h2>
                <button class="dcbm-modal-close">&times;</button>
            </div>
            <div class="dcbm-modal-body">
                <form id="dcbm-booking-form">
                    <input type="hidden" name="car_id" id="modal_car_id" value="">
                    <input type="hidden" id="number_of_days" value="">
                    <input type="hidden" id="total_amount" value="">
                    
                    <div class="dcbm-form-group">
                        <label>Car Model</label>
                        <input type="text" id="car_model_display" readonly style="background:#f5f5f5; font-weight:bold;">
                    </div>
                    
                    <h3>Customer Information</h3>
                    <div class="dcbm-form-row">
                        <div class="dcbm-form-group"><label>Full Name *</label><input type="text" id="customer_name" required></div>
                        <div class="dcbm-form-group"><label>Phone Number *</label><input type="tel" id="customer_phone" required></div>
                    </div>
                    <div class="dcbm-form-group"><label>Email Address *</label><input type="email" id="customer_email" required></div>
                    
                    <h3>Trip Details</h3>
                    <div class="dcbm-form-row">
                        <div class="dcbm-form-group"><label>Pickup Location *</label><input type="text" id="pickup_location" required></div>
                        <div class="dcbm-form-group"><label>Destination *</label><input type="text" id="destination" required placeholder="Where are you going?"></div>
                    </div>
                    <div class="dcbm-form-row">
                        <div class="dcbm-form-group"><label>Pickup Date *</label><input type="date" id="pickup_date" required></div>
                        <div class="dcbm-form-group"><label>Pickup Time *</label><select id="pickup_time" required><?php for($h=0;$h<24;$h++): $hour=str_pad($h,2,'0'); ?><option value="<?php echo $hour;?>:00"><?php echo $hour;?>:00</option><option value="<?php echo $hour;?>:30"><?php echo $hour;?>:30</option><?php endfor; ?></select></div>
                    </div>
                    <div class="dcbm-form-row">
                        <div class="dcbm-form-group"><label>Return Date *</label><input type="date" id="return_date" required></div>
                        <div class="dcbm-form-group"><label>Return Time *</label><select id="return_time" required><?php for($h=0;$h<24;$h++): $hour=str_pad($h,2,'0'); ?><option value="<?php echo $hour;?>:00"><?php echo $hour;?>:00</option><option value="<?php echo $hour;?>:30"><?php echo $hour;?>:30</option><?php endfor; ?></select></div>
                    </div>
                    
                    <div class="dcbm-form-group">
                        <label><input type="checkbox" id="driver_needed" value="yes"> Driver needed?</label>
                    </div>
                    
                    <h3>Extra Services</h3>
                    <div id="dcbm-extra-services-list" class="dcbm-services-list"></div>
                    
                    <div class="dcbm-price-summary">
                        <div class="dcbm-price-row"><span>Car Rent</span><span id="dcbm-car-rent">PKR 0</span></div>
                        <div class="dcbm-price-row"><span>Extra Services</span><span id="dcbm-services-total">PKR 0</span></div>
                        <div class="dcbm-price-row dcbm-price-total"><span>Total Amount</span><span id="dcbm-total-amount">PKR 0</span></div>
                    </div>
                    
                    <button type="submit" class="dcbm-submit-btn">Submit Booking Request</button>
                </form>
            </div>
        </div>
    </div>
<?php }