<?php
class DCBM_Ajax_Handler {
    
    public function __construct() {
        add_action('wp_ajax_dcbm_get_extra_services', array($this, 'get_extra_services'));
        add_action('wp_ajax_nopriv_dcbm_get_extra_services', array($this, 'get_extra_services'));
        add_action('wp_ajax_dcbm_submit_booking', array($this, 'submit_booking'));
        add_action('wp_ajax_nopriv_dcbm_submit_booking', array($this, 'submit_booking'));
        add_action('wp_ajax_dcbm_filter_cars', array($this, 'filter_cars'));
        add_action('wp_ajax_nopriv_dcbm_filter_cars', array($this, 'filter_cars'));
    }
    
    public function get_extra_services() {
        check_ajax_referer('dcbm_nonce', 'nonce');
        
        $extra_services = new DCBM_Extra_Services();
        $services = $extra_services->get_services(true);
        
        wp_send_json_success($services);
    }
    
    public function submit_booking() {
        check_ajax_referer('dcbm_nonce', 'nonce');
        
        $booking_manager = new DCBM_Booking_Manager();
        $result = $booking_manager->save_booking($_POST);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'Your booking request has been submitted successfully! You will receive a confirmation email within 24 hours.',
                'booking_id' => $result['booking_id'],
                'booking_number' => $result['booking_number']
            ));
        } else {
            wp_send_json_error('Failed to submit booking. Please try again.');
        }
    }
    
    public function filter_cars() {
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $transmission = isset($_POST['transmission']) ? sanitize_text_field($_POST['transmission']) : '';
        
        $args = array(
            'post_type' => 'dcbm_car',
            'posts_per_page' => 12,
            'meta_query' => array(),
        );
        
        if ($search) {
            $args['s'] = $search;
        }
        
        if ($transmission) {
            $args['meta_query'][] = array(
                'key' => '_dcbm_transmission',
                'value' => $transmission,
            );
        }
        
        if ($category) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'dcbm_car_category',
                    'field' => 'slug',
                    'terms' => $category,
                ),
            );
        }
        
        $cars = new WP_Query($args);
        
        ob_start();
        if ($cars->have_posts()) {
            while ($cars->have_posts()) {
                $cars->the_post();
                $car_id = get_the_ID();
                $price = get_post_meta($car_id, '_dcbm_rent_per_day_without_fuel', true);
                $trans = get_post_meta($car_id, '_dcbm_transmission', true);
                $capacity = get_post_meta($car_id, '_dcbm_passenger_capacity', true);
                $availability = get_post_meta($car_id, '_dcbm_availability', true);
                $location = get_post_meta($car_id, '_dcbm_pickup_location', true);
                ?>
                <div class="dcbm-car-card">
                    <div class="dcbm-car-image">
                        <?php the_post_thumbnail('medium'); ?>
                        <div class="dcbm-availability-badge dcbm-availability-<?php echo $availability; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $availability)); ?>
                        </div>
                    </div>
                    <div class="dcbm-car-details">
                        <h3><?php the_title(); ?></h3>
                        <div class="dcbm-car-specs">
                            <span class="dcbm-spec"><?php echo ucfirst($trans); ?></span>
                            <span class="dcbm-spec">👥 <?php echo $capacity; ?> seats</span>
                        </div>
                        <div class="dcbm-car-location">📍 <?php echo esc_html($location); ?></div>
                        <div class="dcbm-car-price">
                            <span class="dcbm-price">PKR <?php echo number_format($price); ?></span>
                            <span class="dcbm-price-period">/ day</span>
                        </div>
                        <div class="dcbm-car-buttons">
                            <a href="<?php echo get_permalink(); ?>" class="dcbm-btn dcbm-btn-outline">More Info</a>
                            <button class="dcbm-btn dcbm-btn-primary book-now-btn" data-car-id="<?php echo $car_id; ?>" data-car-price="<?php echo $price; ?>">Book Now</button>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<div class="dcbm-no-results"><p>No cars found matching your criteria.</p></div>';
        }
        wp_reset_postdata();
        
        echo ob_get_clean();
        wp_die();
    }
}