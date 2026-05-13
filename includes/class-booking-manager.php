<?php
class DCBM_Booking_Manager {
    
    private $bookings_table;
    
    public function __construct() {
        global $wpdb;
        $this->bookings_table = $wpdb->prefix . 'dcbm_bookings';
        
        add_action('admin_menu', array($this, 'add_bookings_menu'));
        add_action('wp_ajax_dcbm_update_booking_status', array($this, 'update_booking_status'));
    }
    
    public function add_bookings_menu() {
        add_submenu_page(
            'edit.php?post_type=dcbm_car',
            __('All Bookings', 'digicells-cbm'),
            __('All Bookings', 'digicells-cbm'),
            'manage_options',
            'dcbm-bookings',
            array($this, 'render_bookings_page')
        );
    }
    
    public function render_bookings_page() {
        require_once DCBM_PLUGIN_DIR . 'admin/booking-list-table.php';
        $bookings_table = new DCBM_Bookings_List_Table();
        $bookings_table->prepare_items();
        ?>
        <div class="wrap">
            <h1><?php _e('Car Bookings', 'digicells-cbm'); ?></h1>
            <form method="post">
                <?php $bookings_table->search_box(__('Search Bookings', 'digicells-cbm'), 'booking_search'); ?>
                <?php $bookings_table->display(); ?>
            </form>
        </div>
        <style>
            .booking-status {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
            }
            .status-pending { background: #fff3cd; color: #856404; }
            .status-approved { background: #d4edda; color: #155724; }
            .status-rejected { background: #f8d7da; color: #721c24; }
            .status-completed { background: #d1ecf1; color: #0c5460; }
        </style>
        <?php
    }
    
    public function save_booking($data) {
        global $wpdb;
        
        $booking_data = array(
            'booking_number' => 'BK-' . strtoupper(uniqid()),
            'car_id' => intval($data['car_id']),
            'customer_name' => sanitize_text_field($data['name']),
            'customer_phone' => sanitize_text_field($data['phone']),
            'customer_email' => sanitize_email($data['email']),
            'pickup_date' => sanitize_text_field($data['pickup_date']),
            'return_date' => sanitize_text_field($data['return_date']),
            'pickup_location' => sanitize_text_field($data['pickup_location']),
            'number_of_days' => intval($data['number_of_days']),
            'car_rental_price' => floatval($data['car_rental_price']),
            'extra_services_total' => floatval($data['extra_services_total']),
            'total_amount' => floatval($data['total_amount']),
            'selected_services' => $data['selected_services'],
            'status' => 'pending',
            'booking_date' => current_time('mysql'),
        );
        
        $inserted = $wpdb->insert(
            $this->bookings_table,
            $booking_data,
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%f', '%f', '%s', '%s', '%s')
        );
        
        if ($inserted) {
            $booking_id = $wpdb->insert_id;
            $this->send_emails($booking_id, $booking_data);
            return array('success' => true, 'booking_id' => $booking_id, 'booking_number' => $booking_data['booking_number']);
        }
        
        return array('success' => false);
    }
    
    private function send_emails($booking_id, $booking_data) {
        $email_handler = new DCBM_Email_Handler();
        $email_handler->send_admin_notification($booking_id, $booking_data);
        $email_handler->send_customer_confirmation($booking_id, $booking_data);
    }
    
    public function update_booking_status() {
        check_ajax_referer('dcbm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $booking_id = intval($_POST['booking_id']);
        $status = sanitize_text_field($_POST['status']);
        
        global $wpdb;
        $updated = $wpdb->update(
            $this->bookings_table,
            array('status' => $status),
            array('id' => $booking_id),
            array('%s'),
            array('%d')
        );
        
        if ($updated) {
            wp_send_json_success('Status updated');
        } else {
            wp_send_json_error('Update failed');
        }
    }
    
    public function get_bookings($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'search' => '',
            'status' => '',
            'limit' => 20,
            'offset' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        if (!empty($args['search'])) {
            $where[] = $wpdb->prepare("(customer_name LIKE '%%%s%%' OR customer_email LIKE '%%%s%%' OR booking_number LIKE '%%%s%%')", $args['search'], $args['search'], $args['search']);
        }
        
        if (!empty($args['status'])) {
            $where[] = $wpdb->prepare("status = %s", $args['status']);
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->bookings_table} WHERE $where_clause ORDER BY id DESC LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        );
        
        return $wpdb->get_results($query);
    }
}