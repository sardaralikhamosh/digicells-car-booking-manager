<?php
class DCBM_Activator {
    
    public static function activate() {
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
            pickup_date datetime NOT NULL,
            return_date datetime NOT NULL,
            pickup_location varchar(255) NOT NULL,
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
        
        // Extra services table
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
        
        // Insert default extra services
        self::insert_default_services();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private static function insert_default_services() {
        global $wpdb;
        $services_table = $wpdb->prefix . 'dcbm_extra_services';
        
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
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $services_table WHERE name = %s", $service[0]));
            if (!$exists) {
                $wpdb->insert(
                    $services_table,
                    array(
                        'name' => $service[0],
                        'icon' => $service[1],
                        'price_type' => $service[2],
                        'price' => $service[3],
                        'status' => 'enabled',
                    ),
                    array('%s', '%s', '%s', '%f', '%s')
                );
            }
        }
    }
}