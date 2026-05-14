<?php
/**
 * Plugin Name: Digicells Car Booking Manager
 * Plugin URI: https://hamaribooking.com/
 * Description: Professional car rental and booking management system
 * Version: 1.0.0
 * Author: Digicells
 * Text Domain: digicells-cbm
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('DCBM_VERSION', '1.0.0');
define('DCBM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DCBM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Activation hook
register_activation_hook(__FILE__, 'dcbm_activate');
function dcbm_activate() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Bookings table
    $bookings_table = $wpdb->prefix . 'dcbm_bookings';
    $bookings_sql = "CREATE TABLE IF NOT EXISTS $bookings_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        booking_number varchar(50) NOT NULL,
        car_id mediumint(9) NOT NULL,
        customer_name varchar(255) NOT NULL,
        customer_phone varchar(50) NOT NULL,
        customer_email varchar(255) NOT NULL,
        pickup_date date NOT NULL,
        return_date date NOT NULL,
        pickup_location varchar(255) NOT NULL,
        number_of_days int NOT NULL,
        car_rental_price decimal(10,2) NOT NULL,
        extra_services_total decimal(10,2) DEFAULT 0,
        total_amount decimal(10,2) NOT NULL,
        selected_services text,
        status varchar(50) DEFAULT 'pending',
        booking_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    // Services table
    $services_table = $wpdb->prefix . 'dcbm_extra_services';
    $services_sql = "CREATE TABLE IF NOT EXISTS $services_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        icon varchar(50) DEFAULT '🚗',
        price_type varchar(20) DEFAULT 'paid',
        price decimal(10,2) DEFAULT 0,
        status varchar(20) DEFAULT 'enabled',
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($bookings_sql);
    dbDelta($services_sql);
    
    // Insert default services
    $existing = $wpdb->get_var("SELECT COUNT(*) FROM $services_table");
    if ($existing == 0) {
        $defaults = array(
            array('Driver', '👨‍✈️', 'paid', 1000),
            array('Fuel', '⛽', 'paid', 2000),
            array('GPS Navigation', '🗺️', 'paid', 500),
            array('Child Seat', '👶', 'paid', 500),
            array('Airport Pickup', '✈️', 'paid', 1500),
        );
        foreach ($defaults as $service) {
            $wpdb->insert($services_table, array(
                'name' => $service[0],
                'icon' => $service[1],
                'price_type' => $service[2],
                'price' => $service[3],
                'status' => 'enabled'
            ));
        }
    }
    
    flush_rewrite_rules();
}

// Initialize plugin
add_action('init', 'dcbm_init');
function dcbm_init() {
    // Register Post Type
    $labels = array(
        'name' => 'Cars',
        'singular_name' => 'Car',
        'menu_name' => 'Car Booking',
        'add_new' => 'Add New Car',
        'add_new_item' => 'Add New Car',
        'edit_item' => 'Edit Car',
        'new_item' => 'New Car',
        'view_item' => 'View Car',
        'search_items' => 'Search Cars',
        'not_found' => 'No cars found',
    );
    
    register_post_type('dcbm_car', array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'car'),
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_icon' => 'dashicons-car',
        'show_in_rest' => true,
    ));
    
    // Register Taxonomy
    register_taxonomy('dcbm_category', 'dcbm_car', array(
        'labels' => array('name' => 'Categories'),
        'hierarchical' => true,
        'show_admin_column' => true,
        'rewrite' => array('slug' => 'car-category'),
    ));
    
    // Add default categories
    $default_cats = array('SUV', 'Sedan', 'Luxury', 'Economy', 'Van', '4x4');
    foreach ($default_cats as $cat) {
        if (!term_exists($cat, 'dcbm_category')) {
            wp_insert_term($cat, 'dcbm_category');
        }
    }
}

// Add Meta Boxes
add_action('add_meta_boxes', 'dcbm_add_metaboxes');
function dcbm_add_metaboxes() {
    add_meta_box('dcbm_car_details', 'Car Details', 'dcbm_render_metabox', 'dcbm_car', 'normal', 'high');
}

function dcbm_render_metabox($post) {
    wp_nonce_field('dcbm_save', 'dcbm_nonce');
    $fields = array(
        'model' => get_post_meta($post->ID, '_dcbm_model', true),
        'manufacturer' => get_post_meta($post->ID, '_dcbm_manufacturer', true),
        'year' => get_post_meta($post->ID, '_dcbm_year', true),
        'transmission' => get_post_meta($post->ID, '_dcbm_transmission', true),
        'fuel' => get_post_meta($post->ID, '_dcbm_fuel', true),
        'seats' => get_post_meta($post->ID, '_dcbm_seats', true),
        'price' => get_post_meta($post->ID, '_dcbm_price', true),
        'location' => get_post_meta($post->ID, '_dcbm_location', true),
        'availability' => get_post_meta($post->ID, '_dcbm_availability', true),
    );
    ?>
    <style>
        .dcbm-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            padding: 10px;
        }
        .dcbm-meta-field {
            margin-bottom: 10px;
        }
        .dcbm-meta-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .dcbm-meta-field input, .dcbm-meta-field select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
    <div class="dcbm-meta-grid">
        <div class="dcbm-meta-field">
            <label>Car Model:</label>
            <input type="text" name="dcbm_model" value="<?php echo esc_attr($fields['model']); ?>">
        </div>
        <div class="dcbm-meta-field">
            <label>Manufacturer:</label>
            <input type="text" name="dcbm_manufacturer" value="<?php echo esc_attr($fields['manufacturer']); ?>">
        </div>
        <div class="dcbm-meta-field">
            <label>Year:</label>
            <select name="dcbm_year">
                <option value="">Select Year</option>
                <?php for ($y = date('Y'); $y >= 2010; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php selected($fields['year'], $y); ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="dcbm-meta-field">
            <label>Transmission:</label>
            <select name="dcbm_transmission">
                <option value="automatic" <?php selected($fields['transmission'], 'automatic'); ?>>Automatic</option>
                <option value="manual" <?php selected($fields['transmission'], 'manual'); ?>>Manual</option>
            </select>
        </div>
        <div class="dcbm-meta-field">
            <label>Fuel Type:</label>
            <select name="dcbm_fuel">
                <option value="petrol" <?php selected($fields['fuel'], 'petrol'); ?>>Petrol</option>
                <option value="diesel" <?php selected($fields['fuel'], 'diesel'); ?>>Diesel</option>
                <option value="hybrid" <?php selected($fields['fuel'], 'hybrid'); ?>>Hybrid</option>
            </select>
        </div>
        <div class="dcbm-meta-field">
            <label>Seats:</label>
            <input type="number" name="dcbm_seats" value="<?php echo esc_attr($fields['seats']); ?>">
        </div>
        <div class="dcbm-meta-field">
            <label>Price Per Day (PKR):</label>
            <input type="number" name="dcbm_price" value="<?php echo esc_attr($fields['price']); ?>">
        </div>
        <div class="dcbm-meta-field">
            <label>Pickup Location:</label>
            <input type="text" name="dcbm_location" value="<?php echo esc_attr($fields['location']); ?>">
        </div>
        <div class="dcbm-meta-field">
            <label>Availability:</label>
            <select name="dcbm_availability">
                <option value="available" <?php selected($fields['availability'], 'available'); ?>>Available</option>
                <option value="unavailable" <?php selected($fields['availability'], 'unavailable'); ?>>Not Available</option>
                <option value="maintenance" <?php selected($fields['availability'], 'maintenance'); ?>>Maintenance</option>
            </select>
        </div>
    </div>
    <?php
}

add_action('save_post_dcbm_car', 'dcbm_save_metabox');
function dcbm_save_metabox($post_id) {
    if (!isset($_POST['dcbm_nonce']) || !wp_verify_nonce($_POST['dcbm_nonce'], 'dcbm_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    $fields = array('model', 'manufacturer', 'year', 'transmission', 'fuel', 'seats', 'price', 'location', 'availability');
    foreach ($fields as $field) {
        if (isset($_POST['dcbm_' . $field])) {
            update_post_meta($post_id, '_dcbm_' . $field, sanitize_text_field($_POST['dcbm_' . $field]));
        }
    }
}

// Enqueue Scripts
add_action('wp_enqueue_scripts', 'dcbm_enqueue_scripts');
function dcbm_enqueue_scripts() {
    wp_enqueue_style('dashicons');
    wp_enqueue_style('dcbm-style', DCBM_PLUGIN_URL . 'assets/css/style.css', array(), DCBM_VERSION);
    wp_enqueue_script('jquery');
    wp_enqueue_script('dcbm-script', DCBM_PLUGIN_URL . 'assets/js/script.js', array('jquery'), DCBM_VERSION, true);
    wp_localize_script('dcbm-script', 'dcbm_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dcbm_ajax_nonce')
    ));
}

// Admin Menu
add_action('admin_menu', 'dcbm_admin_menu');
function dcbm_admin_menu() {
    add_submenu_page('edit.php?post_type=dcbm_car', 'Bookings', 'Bookings', 'manage_options', 'dcbm-bookings', 'dcbm_bookings_page');
    add_submenu_page('edit.php?post_type=dcbm_car', 'Extra Services', 'Extra Services', 'manage_options', 'dcbm-services', 'dcbm_services_page');
}

function dcbm_bookings_page() {
    global $wpdb;
    $bookings = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dcbm_bookings ORDER BY id DESC");
    ?>
    <div class="wrap">
        <h1>Bookings</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>ID</th><th>Customer</th><th>Car</th><th>Dates</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($bookings as $b): 
                $car = get_post($b->car_id);
            ?>
                <tr>
                    <td><?php echo $b->booking_number; ?></td>
                    <td><?php echo $b->customer_name; ?><br><small><?php echo $b->customer_email; ?></small></td>
                    <td><?php echo $car ? $car->post_title : 'N/A'; ?></td>
                    <td><?php echo date('M d', strtotime($b->pickup_date)); ?> - <?php echo date('M d', strtotime($b->return_date)); ?></td>
                    <td>PKR <?php echo number_format($b->total_amount); ?></td>
                    <td>
                        <select class="dcbm-status-change" data-id="<?php echo $b->id; ?>">
                            <option value="pending" <?php selected($b->status, 'pending'); ?>>Pending</option>
                            <option value="approved" <?php selected($b->status, 'approved'); ?>>Approved</option>
                            <option value="completed" <?php selected($b->status, 'completed'); ?>>Completed</option>
                            <option value="rejected" <?php selected($b->status, 'rejected'); ?>>Rejected</option>
                        </select>
                    </td>
                    <td><button class="button view-booking" data-details='<?php echo json_encode($b); ?>'>View</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('.dcbm-status-change').on('change', function() {
            var id = $(this).data('id');
            var status = $(this).val();
            $.post(ajaxurl, {action: 'dcbm_update_status', id: id, status: status, nonce: dcbm_ajax.nonce}, function(r) {
                if(r.success) location.reload();
                else alert('Error');
            });
        });
    });
    </script>
    <?php
}

function dcbm_services_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'dcbm_extra_services';
    
    if (isset($_POST['add_service']) && wp_verify_nonce($_POST['service_nonce'], 'add_service')) {
        $wpdb->insert($table, array(
            'name' => sanitize_text_field($_POST['name']),
            'icon' => sanitize_text_field($_POST['icon']),
            'price_type' => sanitize_text_field($_POST['price_type']),
            'price' => floatval($_POST['price']),
            'status' => 'enabled'
        ));
        echo '<div class="notice notice-success"><p>Service added!</p></div>';
    }
    
    if (isset($_GET['delete'])) {
        $wpdb->delete($table, array('id' => intval($_GET['delete'])));
        echo '<div class="notice notice-success"><p>Service deleted!</p></div>';
    }
    
    $services = $wpdb->get_results("SELECT * FROM $table");
    ?>
    <div class="wrap">
        <h1>Extra Services</h1>
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
            <div class="card">
                <h3>Add New Service</h3>
                <form method="post">
                    <?php wp_nonce_field('add_service', 'service_nonce'); ?>
                    <p><input type="text" name="name" placeholder="Service Name" required style="width:100%"></p>
                    <p><input type="text" name="icon" placeholder="Icon (Emoji)" value="🚗" style="width:100%"></p>
                    <p>
                        <select name="price_type" style="width:100%">
                            <option value="free">Free</option>
                            <option value="paid">Paid</option>
                        </select>
                    </p>
                    <p><input type="number" name="price" placeholder="Price (PKR)" value="0" style="width:100%"></p>
                    <p><input type="submit" name="add_service" class="button button-primary" value="Add Service"></p>
                </form>
            </div>
            <div>
                <table class="wp-list-table widefat fixed striped">
                    <thead><tr><th>Icon</th><th>Name</th><th>Price</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($services as $s): ?>
                        <tr>
                            <td><?php echo $s->icon; ?></td>
                            <td><?php echo $s->name; ?></td>
                            <td><?php echo $s->price_type == 'paid' ? 'PKR ' . number_format($s->price) : 'Free'; ?></td>
                            <td><a href="?page=dcbm-services&delete=<?php echo $s->id; ?>" class="button button-small" onclick="return confirm('Delete?')">Delete</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

// AJAX Handlers
add_action('wp_ajax_dcbm_get_services', 'dcbm_get_services');
add_action('wp_ajax_nopriv_dcbm_get_services', 'dcbm_get_services');
function dcbm_get_services() {
    check_ajax_referer('dcbm_ajax_nonce', 'nonce');
    global $wpdb;
    $services = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dcbm_extra_services WHERE status='enabled'");
    wp_send_json_success($services);
}

add_action('wp_ajax_dcbm_submit_booking', 'dcbm_submit_booking');
add_action('wp_ajax_nopriv_dcbm_submit_booking', 'dcbm_submit_booking');
function dcbm_submit_booking() {
    check_ajax_referer('dcbm_ajax_nonce', 'nonce');
    
    global $wpdb;
    $booking_number = 'BK-' . strtoupper(uniqid());
    
    $data = array(
        'booking_number' => $booking_number,
        'car_id' => intval($_POST['car_id']),
        'customer_name' => sanitize_text_field($_POST['name']),
        'customer_phone' => sanitize_text_field($_POST['phone']),
        'customer_email' => sanitize_email($_POST['email']),
        'pickup_date' => sanitize_text_field($_POST['pickup_date']),
        'return_date' => sanitize_text_field($_POST['return_date']),
        'pickup_location' => sanitize_text_field($_POST['pickup_location']),
        'number_of_days' => intval($_POST['days']),
        'car_rental_price' => floatval($_POST['car_price']),
        'extra_services_total' => floatval($_POST['services_total']),
        'total_amount' => floatval($_POST['total']),
        'selected_services' => sanitize_text_field($_POST['services']),
        'status' => 'pending'
    );
    
    $inserted = $wpdb->insert($wpdb->prefix . 'dcbm_bookings', $data);
    
    if ($inserted) {
        // Send email
        $car = get_post($data['car_id']);
        $admin_email = get_option('admin_email');
        wp_mail($admin_email, 'New Booking: ' . $booking_number, "New booking received from {$data['customer_name']} for {$car->post_title}. Total: PKR " . number_format($data['total_amount']));
        wp_mail($data['customer_email'], 'Booking Confirmation - ' . $booking_number, "Dear {$data['customer_name']},\n\nYour booking for {$car->post_title} has been received. We will contact you shortly.\n\nBooking #: $booking_number\nTotal: PKR " . number_format($data['total_amount']) . "\n\nThank you!");
        
        wp_send_json_success(array('message' => 'Booking submitted successfully! Check your email for confirmation.'));
    } else {
        wp_send_json_error('Failed to submit booking.');
    }
}

add_action('wp_ajax_dcbm_update_status', 'dcbm_update_status');
function dcbm_update_status() {
    check_ajax_referer('dcbm_ajax_nonce', 'nonce');
    global $wpdb;
    $wpdb->update($wpdb->prefix . 'dcbm_bookings', array('status' => sanitize_text_field($_POST['status'])), array('id' => intval($_POST['id'])));
    wp_send_json_success();
}

// Shortcodes
add_shortcode('digicells_car_listing', 'dcbm_car_listing');
function dcbm_car_listing($atts) {
    $atts = shortcode_atts(array('per_page' => 12), $atts);
    ob_start();
    ?>
    <div class="dcbm-container">
        <!-- Search Filters -->
        <div class="dcbm-filters">
            <input type="text" id="dcbm-search" placeholder="Search cars..." class="dcbm-filter-input">
            <select id="dcbm-category" class="dcbm-filter-select">
                <option value="">All Categories</option>
                <?php
                $cats = get_terms(array('taxonomy' => 'dcbm_category', 'hide_empty' => false));
                foreach ($cats as $cat) {
                    echo '<option value="' . esc_attr($cat->slug) . '">' . esc_html($cat->name) . '</option>';
                }
                ?>
            </select>
            <select id="dcbm-transmission" class="dcbm-filter-select">
                <option value="">Transmission</option>
                <option value="automatic">Automatic</option>
                <option value="manual">Manual</option>
            </select>
            <button id="dcbm-filter-btn" class="dcbm-filter-btn">Search</button>
        </div>
        
        <!-- Cars Grid -->
        <div id="dcbm-cars-grid" class="dcbm-cars-grid">
            <?php echo dcbm_get_cars_html($atts['per_page']); ?>
        </div>
    </div>
    
    <!-- Booking Modal -->
    <div id="dcbm-modal" class="dcbm-modal">
        <div class="dcbm-modal-content">
            <span class="dcbm-modal-close">&times;</span>
            <h2 id="dcbm-modal-title">Book Your Car</h2>
            <form id="dcbm-booking-form">
                <input type="hidden" id="booking_car_id" name="car_id">
                <input type="hidden" id="booking_car_price" name="car_price">
                
                <div class="dcbm-form-row">
                    <div class="dcbm-form-group">
                        <label>Full Name *</label>
                        <input type="text" id="cust_name" required>
                    </div>
                    <div class="dcbm-form-group">
                        <label>Phone Number *</label>
                        <input type="tel" id="cust_phone" required>
                    </div>
                </div>
                
                <div class="dcbm-form-group">
                    <label>Email Address *</label>
                    <input type="email" id="cust_email" required>
                </div>
                
                <div class="dcbm-form-row">
                    <div class="dcbm-form-group">
                        <label>Pickup Date *</label>
                        <input type="date" id="pickup_date" required>
                    </div>
                    <div class="dcbm-form-group">
                        <label>Return Date *</label>
                        <input type="date" id="return_date" required>
                    </div>
                </div>
                
                <div class="dcbm-form-group">
                    <label>Pickup Location *</label>
                    <input type="text" id="pickup_location" required placeholder="Enter pickup location">
                </div>
                
                <div class="dcbm-form-group">
                    <label>Extra Services</label>
                    <div id="dcbm-services-list" class="dcbm-services-list"></div>
                </div>
                
                <div class="dcbm-price-summary">
                    <div class="dcbm-price-row">Car Rent: <span id="display-car-rent">PKR 0</span></div>
                    <div class="dcbm-price-row">Services: <span id="display-services-total">PKR 0</span></div>
                    <div class="dcbm-price-row dcbm-price-total">Total: <span id="display-total">PKR 0</span></div>
                </div>
                
                <input type="hidden" id="total_days" value="0">
                <input type="hidden" id="services_total" value="0">
                <input type="hidden" id="total_amount" value="0">
                <input type="hidden" id="selected_services" value="{}">
                
                <button type="submit" class="dcbm-submit-btn">Submit Booking</button>
                <div id="dcbm-form-message"></div>
            </form>
        </div>
    </div>
    
    <style>
        .dcbm-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .dcbm-filters { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 30px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .dcbm-filter-input, .dcbm-filter-select { flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; min-width: 150px; }
        .dcbm-filter-btn { padding: 12px 24px; background: #f68511; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .dcbm-filter-btn:hover { background: #0048a5; }
        .dcbm-cars-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
        .dcbm-car-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .dcbm-car-card:hover { transform: translateY(-5px); }
        .dcbm-car-image { position: relative; height: 220px; overflow: hidden; }
        .dcbm-car-image img { width: 100%; height: 100%; object-fit: cover; }
        .dcbm-availability { position: absolute; top: 12px; right: 12px; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; color: white; }
        .dcbm-availability-available { background: #10b981; }
        .dcbm-availability-unavailable { background: #ef4444; }
        .dcbm-availability-maintenance { background: #f59e0b; }
        .dcbm-car-info { padding: 20px; }
        .dcbm-car-info h3 { margin: 0 0 10px; font-size: 1.25rem; color: #1a1a2e; }
        .dcbm-car-specs { display: flex; gap: 15px; margin-bottom: 10px; color: #666; font-size: 14px; }
        .dcbm-car-location { color: #666; font-size: 14px; margin-bottom: 10px; }
        .dcbm-car-price { font-size: 24px; font-weight: 700; color: #f68511; margin: 15px 0; }
        .dcbm-car-price span { font-size: 14px; color: #666; font-weight: normal; }
        .dcbm-car-buttons { display: flex; gap: 10px; }
        .dcbm-btn { flex: 1; padding: 10px; text-align: center; border-radius: 30px; text-decoration: none; font-weight: 600; cursor: pointer; border: none; transition: all 0.3s; }
        .dcbm-btn-primary { background: #f68511; color: white; }
        .dcbm-btn-primary:hover { background: #0048a5; }
        .dcbm-btn-outline { background: transparent; border: 2px solid #f68511; color: #f68511; }
        .dcbm-btn-outline:hover { background: #f68511; color: white; }
        .dcbm-modal { display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); overflow-y: auto; }
        .dcbm-modal-content { background: white; margin: 50px auto; max-width: 600px; width: 90%; border-radius: 20px; animation: slideIn 0.3s; }
        @keyframes slideIn { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .dcbm-modal-close { float: right; font-size: 28px; cursor: pointer; padding: 10px 20px; }
        .dcbm-modal-content h2 { padding: 20px 20px 0; margin: 0; color: #0048a5; }
        #dcbm-booking-form { padding: 20px; }
        .dcbm-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .dcbm-form-group { margin-bottom: 15px; }
        .dcbm-form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        .dcbm-form-group input, .dcbm-form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .dcbm-services-list { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .dcbm-service-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee; }
        .dcbm-service-item:last-child { border-bottom: none; }
        .dcbm-price-summary { background: linear-gradient(135deg, #f68511, #0048a5); color: white; padding: 20px; border-radius: 12px; margin: 20px 0; }
        .dcbm-price-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .dcbm-price-total { font-size: 1.2rem; font-weight: 700; border-top: 1px solid rgba(255,255,255,0.3); margin-top: 8px; padding-top: 12px; }
        .dcbm-submit-btn { width: 100%; padding: 14px; background: #f68511; color: white; border: none; border-radius: 40px; font-size: 16px; font-weight: 700; cursor: pointer; margin-top: 10px; }
        .dcbm-submit-btn:hover { background: #0048a5; }
        @media (max-width: 768px) {
            .dcbm-cars-grid { grid-template-columns: 1fr; }
            .dcbm-form-row { grid-template-columns: 1fr; }
            .dcbm-filters { flex-direction: column; }
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        var selectedServices = {};
        var currentCarPrice = 0;
        
        // Load services
        function loadServices() {
            $.post(dcbm_ajax.ajax_url, {action: 'dcbm_get_services', nonce: dcbm_ajax.nonce}, function(r) {
                if(r.success) {
                    var html = '';
                    $.each(r.data, function(i, s) {
                        html += '<div class="dcbm-service-item"><label><input type="checkbox" class="service-check" data-id="'+s.id+'" data-price="'+s.price+'" data-type="'+s.price_type+'"> '+s.icon+' '+s.name+'</label><span>'+(s.price_type=='paid'?'PKR '+s.price:'Free')+'</span></div>';
                    });
                    $('#dcbm-services-list').html(html);
                    
                    $('.service-check').on('change', function() {
                        var id = $(this).data('id');
                        var price = $(this).data('price');
                        var type = $(this).data('type');
                        if($(this).is(':checked')) {
                            selectedServices[id] = type == 'paid' ? parseFloat(price) : 0;
                        } else {
                            delete selectedServices[id];
                        }
                        calculateTotal();
                    });
                }
            });
        }
        
        // Calculate total
        function calculateTotal() {
            var pickup = $('#pickup_date').val();
            var ret = $('#return_date').val();
            if(pickup && ret) {
                var days = Math.ceil((new Date(ret) - new Date(pickup)) / (1000*60*60*24));
                var carRent = currentCarPrice * days;
                var servicesTotal = 0;
                for(var id in selectedServices) servicesTotal += selectedServices[id];
                var total = carRent + servicesTotal;
                
                $('#display-car-rent').text('PKR ' + carRent.toLocaleString());
                $('#display-services-total').text('PKR ' + servicesTotal.toLocaleString());
                $('#display-total').text('PKR ' + total.toLocaleString());
                $('#total_days').val(days);
                $('#services_total').val(servicesTotal);
                $('#total_amount').val(total);
                $('#selected_services').val(JSON.stringify(selectedServices));
            }
        }
        
        $('#pickup_date, #return_date').on('change', calculateTotal);
        
        // Open modal
        $(document).on('click', '.book-now-btn', function() {
            currentCarPrice = parseFloat($(this).data('price'));
            $('#booking_car_id').val($(this).data('id'));
            $('#booking_car_price').val(currentCarPrice);
            $('#dcbm-modal').fadeIn(300);
            $('body').css('overflow', 'hidden');
            loadServices();
        });
        
        // Close modal
        $('.dcbm-modal-close').on('click', function() {
            $('#dcbm-modal').fadeOut(300);
            $('body').css('overflow', 'auto');
            $('#dcbm-booking-form')[0].reset();
            selectedServices = {};
        });
        
        $(window).on('click', function(e) {
            if($(e.target).is('#dcbm-modal')) {
                $('#dcbm-modal').fadeOut(300);
                $('body').css('overflow', 'auto');
            }
        });
        
        // Submit booking
        $('#dcbm-booking-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'dcbm_submit_booking',
                nonce: dcbm_ajax.nonce,
                car_id: $('#booking_car_id').val(),
                name: $('#cust_name').val(),
                phone: $('#cust_phone').val(),
                email: $('#cust_email').val(),
                pickup_date: $('#pickup_date').val(),
                return_date: $('#return_date').val(),
                pickup_location: $('#pickup_location').val(),
                days: $('#total_days').val(),
                car_price: currentCarPrice,
                services_total: $('#services_total').val(),
                total: $('#total_amount').val(),
                services: $('#selected_services').val()
            };
            
            if(!formData.name || !formData.phone || !formData.email || !formData.pickup_date || !formData.return_date) {
                alert('Please fill all required fields.');
                return;
            }
            
            $('.dcbm-submit-btn').prop('disabled', true).text('Submitting...');
            
            $.post(dcbm_ajax.ajax_url, formData, function(r) {
                if(r.success) {
                    alert(r.data.message);
                    $('#dcbm-modal').fadeOut(300);
                    $('#dcbm-booking-form')[0].reset();
                    selectedServices = {};
                } else {
                    alert(r.data);
                }
            }).fail(function() {
                alert('Error submitting booking. Please try again.');
            }).always(function() {
                $('.dcbm-submit-btn').prop('disabled', false).text('Submit Booking');
            });
        });
        
        // Filter cars
        $('#dcbm-filter-btn').on('click', function() {
            var search = $('#dcbm-search').val();
            var category = $('#dcbm-category').val();
            var transmission = $('#dcbm-transmission').val();
            
            $.post(dcbm_ajax.ajax_url, {
                action: 'dcbm_filter_cars',
                nonce: dcbm_ajax.nonce,
                search: search,
                category: category,
                transmission: transmission
            }, function(r) {
                $('#dcbm-cars-grid').html(r);
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

function dcbm_get_cars_html($per_page) {
    $args = array(
        'post_type' => 'dcbm_car',
        'posts_per_page' => $per_page,
        'post_status' => 'publish'
    );
    $cars = new WP_Query($args);
    ob_start();
    
    if ($cars->have_posts()) {
        while ($cars->have_posts()) {
            $cars->the_post();
            $id = get_the_ID();
            $price = get_post_meta($id, '_dcbm_price', true);
            $trans = get_post_meta($id, '_dcbm_transmission', true);
            $seats = get_post_meta($id, '_dcbm_seats', true);
            $location = get_post_meta($id, '_dcbm_location', true);
            $availability = get_post_meta($id, '_dcbm_availability', true);
            if(!$availability) $availability = 'available';
            ?>
            <div class="dcbm-car-card">
                <div class="dcbm-car-image">
                    <?php if (has_post_thumbnail()): ?>
                        <?php the_post_thumbnail('medium'); ?>
                    <?php else: ?>
                        <img src="https://via.placeholder.com/400x300?text=Car+Image" alt="<?php the_title(); ?>">
                    <?php endif; ?>
                    <span class="dcbm-availability dcbm-availability-<?php echo $availability; ?>">
                        <?php echo ucfirst($availability); ?>
                    </span>
                </div>
                <div class="dcbm-car-info">
                    <h3><?php the_title(); ?></h3>
                    <div class="dcbm-car-specs">
                        <span><?php echo ucfirst($trans); ?></span>
                        <span><?php echo $seats; ?> seats</span>
                    </div>
                    <div class="dcbm-car-location">📍 <?php echo esc_html($location); ?></div>
                    <div class="dcbm-car-price">PKR <?php echo number_format($price); ?> <span>/ day</span></div>
                    <div class="dcbm-car-buttons">
                        <a href="<?php echo get_permalink(); ?>" class="dcbm-btn dcbm-btn-outline">Details</a>
                        <?php if ($availability == 'available'): ?>
                            <button class="dcbm-btn dcbm-btn-primary book-now-btn" data-id="<?php echo $id; ?>" data-price="<?php echo $price; ?>">Book Now</button>
                        <?php else: ?>
                            <button class="dcbm-btn dcbm-btn-primary" disabled style="opacity:0.5">Not Available</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
        }
        wp_reset_postdata();
    } else {
        echo '<p style="text-align:center;padding:50px;">No cars found.</p>';
    }
    
    return ob_get_clean();
}

add_action('wp_ajax_dcbm_filter_cars', 'dcbm_filter_cars');
add_action('wp_ajax_nopriv_dcbm_filter_cars', 'dcbm_filter_cars');
function dcbm_filter_cars() {
    check_ajax_referer('dcbm_ajax_nonce', 'nonce');
    
    $args = array(
        'post_type' => 'dcbm_car',
        'posts_per_page' => 12,
        'post_status' => 'publish'
    );
    
    if (!empty($_POST['search'])) {
        $args['s'] = sanitize_text_field($_POST['search']);
    }
    
    if (!empty($_POST['transmission'])) {
        $args['meta_query'][] = array(
            'key' => '_dcbm_transmission',
            'value' => sanitize_text_field($_POST['transmission'])
        );
    }
    
    if (!empty($_POST['category'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'dcbm_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($_POST['category'])
            )
        );
    }
    
    $cars = new WP_Query($args);
    
    if ($cars->have_posts()) {
        while ($cars->have_posts()) {
            $cars->the_post();
            $id = get_the_ID();
            $price = get_post_meta($id, '_dcbm_price', true);
            $trans = get_post_meta($id, '_dcbm_transmission', true);
            $seats = get_post_meta($id, '_dcbm_seats', true);
            $location = get_post_meta($id, '_dcbm_location', true);
            $availability = get_post_meta($id, '_dcbm_availability', true);
            ?>
            <div class="dcbm-car-card">
                <div class="dcbm-car-image">
                    <?php the_post_thumbnail('medium'); ?>
                    <span class="dcbm-availability dcbm-availability-<?php echo $availability; ?>"><?php echo ucfirst($availability); ?></span>
                </div>
                <div class="dcbm-car-info">
                    <h3><?php the_title(); ?></h3>
                    <div class="dcbm-car-specs"><span><?php echo ucfirst($trans); ?></span><span><?php echo $seats; ?> seats</span></div>
                    <div class="dcbm-car-location">📍 <?php echo esc_html($location); ?></div>
                    <div class="dcbm-car-price">PKR <?php echo number_format($price); ?> <span>/ day</span></div>
                    <div class="dcbm-car-buttons">
                        <a href="<?php echo get_permalink(); ?>" class="dcbm-btn dcbm-btn-outline">Details</a>
                        <button class="dcbm-btn dcbm-btn-primary book-now-btn" data-id="<?php echo $id; ?>" data-price="<?php echo $price; ?>">Book Now</button>
                    </div>
                </div>
            </div>
            <?php
        }
        wp_reset_postdata();
    } else {
        echo '<p style="text-align:center;padding:50px;">No cars found.</p>';
    }
    wp_die();
}

// Single car template
add_filter('single_template', 'dcbm_single_template');
function dcbm_single_template($single) {
    global $post;
    if ($post->post_type == 'dcbm_car') {
        $template = DCBM_PLUGIN_DIR . 'single-car.php';
        if (file_exists($template)) return $template;
        
        // Fallback inline template
        echo dcbm_get_single_car_html($post);
        exit;
    }
    return $single;
}

function dcbm_get_single_car_html($post) {
    $id = $post->ID;
    ob_start();
    get_header();
    ?>
    <div class="dcbm-container" style="max-width:1200px;margin:0 auto;padding:40px 20px;">
        <h1 style="font-size:2rem;margin-bottom:20px;"><?php echo $post->post_title; ?></h1>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;">
            <div>
                <?php if (has_post_thumbnail($id)): ?>
                    <?php echo get_the_post_thumbnail($id, 'large', array('style'=>'width:100%;border-radius:16px;')); ?>
                <?php endif; ?>
            </div>
            <div>
                <?php
                $price = get_post_meta($id, '_dcbm_price', true);
                $trans = get_post_meta($id, '_dcbm_transmission', true);
                $seats = get_post_meta($id, '_dcbm_seats', true);
                $location = get_post_meta($id, '_dcbm_location', true);
                $availability = get_post_meta($id, '_dcbm_availability', true);
                ?>
                <div style="background:white;padding:30px;border-radius:16px;box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                    <div style="font-size:32px;font-weight:700;color:#f68511;margin-bottom:20px;">PKR <?php echo number_format($price); ?> <span style="font-size:16px;color:#666;">/ day</span></div>
                    <div style="margin-bottom:20px;"><?php echo apply_filters('the_content', $post->post_content); ?></div>
                    <hr>
                    <div style="margin:20px 0;"><strong>Transmission:</strong> <?php echo ucfirst($trans); ?></div>
                    <div style="margin:20px 0;"><strong>Seats:</strong> <?php echo $seats; ?></div>
                    <div style="margin:20px 0;"><strong>Pickup Location:</strong> <?php echo esc_html($location); ?></div>
                    <div style="margin:20px 0;"><strong>Status:</strong> <span style="color:<?php echo $availability=='available'?'#10b981':'#ef4444'; ?>"><?php echo ucfirst($availability); ?></span></div>
                    <?php if ($availability == 'available'): ?>
                        <button class="book-now-btn" data-id="<?php echo $id; ?>" data-price="<?php echo $price; ?>" style="width:100%;padding:15px;background:#f68511;color:white;border:none;border-radius:40px;font-size:18px;font-weight:700;cursor:pointer;">Book This Car</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    get_footer();
    return ob_get_clean();
}