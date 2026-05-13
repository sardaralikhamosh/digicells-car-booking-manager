<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class DCBM_Bookings_List_Table extends WP_List_Table {
    
    private $bookings_table;
    
    public function __construct() {
        global $wpdb;
        $this->bookings_table = $wpdb->prefix . 'dcbm_bookings';
        
        parent::__construct(array(
            'singular' => 'booking',
            'plural' => 'bookings',
            'ajax' => true,
        ));
    }
    
    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'booking_number' => __('Booking #', 'digicells-cbm'),
            'customer_name' => __('Customer', 'digicells-cbm'),
            'car' => __('Car', 'digicells-cbm'),
            'pickup_date' => __('Pickup Date', 'digicells-cbm'),
            'return_date' => __('Return Date', 'digicells-cbm'),
            'total_amount' => __('Total', 'digicells-cbm'),
            'status' => __('Status', 'digicells-cbm'),
            'booking_date' => __('Booked On', 'digicells-cbm'),
        );
    }
    
    public function get_sortable_columns() {
        return array(
            'booking_number' => array('booking_number', false),
            'customer_name' => array('customer_name', false),
            'pickup_date' => array('pickup_date', false),
            'total_amount' => array('total_amount', false),
            'booking_date' => array('booking_date', true),
        );
    }
    
    public function prepare_items() {
        global $wpdb;
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        $where = '1=1';
        if (!empty($search)) {
            $where .= $wpdb->prepare(" AND (booking_number LIKE '%%%s%%' OR customer_name LIKE '%%%s%%' OR customer_email LIKE '%%%s%%')", $search, $search, $search);
        }
        
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$this->bookings_table} WHERE $where");
        
        $orderby = isset($_GET['orderby']) ? sanitize_sql_orderby($_GET['orderby']) : 'id';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';
        
        $offset = ($current_page - 1) * $per_page;
        
        $this->items = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->bookings_table} WHERE $where ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $offset)
        );
        
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ));
    }
    
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'booking_number':
                return '<strong>' . esc_html($item->booking_number) . '</strong>';
            case 'customer_name':
                return esc_html($item->customer_name) . '<br><small>' . esc_html($item->customer_email) . '</small>';
            case 'car':
                $car_title = get_the_title($item->car_id);
                return '<a href="' . get_edit_post_link($item->car_id) . '">' . esc_html($car_title) . '</a>';
            case 'pickup_date':
            case 'return_date':
                return date('M j, Y', strtotime($item->$column_name));
            case 'total_amount':
                return 'PKR ' . number_format($item->total_amount);
            case 'status':
                return $this->get_status_badge($item->status);
            case 'booking_date':
                return date('M j, Y g:i A', strtotime($item->booking_date));
            default:
                return esc_html($item->$column_name);
        }
    }
    
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="booking_ids[]" value="%s" />', $item->id);
    }
    
    public function column_actions($item) {
        $actions = array(
            'view' => sprintf('<a href="#" class="view-booking" data-id="%d">View</a>', $item->id),
            'approve' => sprintf('<a href="#" class="update-status" data-id="%d" data-status="approved">Approve</a>', $item->id),
            'reject' => sprintf('<a href="#" class="update-status" data-id="%d" data-status="rejected">Reject</a>', $item->id),
            'complete' => sprintf('<a href="#" class="update-status" data-id="%d" data-status="completed">Complete</a>', $item->id),
            'delete' => sprintf('<a href="#" class="delete-booking" data-id="%d" onclick="return confirm(\'Delete this booking?\')">Delete</a>', $item->id),
        );
        return $this->row_actions($actions);
    }
    
    private function get_status_badge($status) {
        $classes = array(
            'pending' => 'status-pending',
            'approved' => 'status-approved',
            'rejected' => 'status-rejected',
            'completed' => 'status-completed',
        );
        $labels = array(
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'completed' => 'Completed',
        );
        $class = isset($classes[$status]) ? $classes[$status] : 'status-pending';
        $label = isset($labels[$status]) ? $labels[$status] : ucfirst($status);
        
        return '<span class="booking-status ' . $class . '">' . $label . '</span>';
    }
    
    public function get_bulk_actions() {
        return array(
            'approve' => 'Approve Selected',
            'reject' => 'Reject Selected',
            'complete' => 'Mark as Completed',
            'delete' => 'Delete Selected',
        );
    }
}