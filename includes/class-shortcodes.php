<?php
class DCBM_Shortcodes {
    
    public function __construct() {
        add_shortcode('digicells_car_listing', array($this, 'render_car_listing'));
        add_shortcode('digicells_featured_cars', array($this, 'render_featured_cars'));
        add_shortcode('digicells_car_search', array($this, 'render_search_form'));
    }
    
    public function render_car_listing($atts) {
        $atts = shortcode_atts(array(
            'per_page' => 12,
            'category' => '',
            'show_filter' => 'yes',
        ), $atts);
        
        ob_start();
        include DCBM_PLUGIN_DIR . 'templates/car-listing.php';
        return ob_get_clean();
    }
    
    public function render_featured_cars($atts) {
        $atts = shortcode_atts(array(
            'per_page' => 6,
        ), $atts);
        
        $args = array(
            'post_type' => 'dcbm_car',
            'posts_per_page' => $atts['per_page'],
            'meta_query' => array(
                array(
                    'key' => '_dcbm_availability',
                    'value' => 'available',
                ),
            ),
        );
        
        $cars = new WP_Query($args);
        
        ob_start();
        ?>
        <div class="dcbm-featured-cars">
            <div class="dcbm-cars-grid">
                <?php while ($cars->have_posts()) : $cars->the_post(); ?>
                    <?php $this->render_car_card(get_the_ID()); ?>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    public function render_search_form() {
        ob_start();
        ?>
        <div class="dcbm-search-form">
            <form id="dcbm-search-form" class="dcbm-search-form-fields">
                <div class="dcbm-search-row">
                    <div class="dcbm-search-group">
                        <input type="text" name="search" placeholder="<?php _e('Search by car name...', 'digicells-cbm'); ?>">
                    </div>
                    <div class="dcbm-search-group">
                        <select name="category">
                            <option value=""><?php _e('All Categories', 'digicells-cbm'); ?></option>
                            <?php
                            $categories = get_terms(array('taxonomy' => 'dcbm_car_category', 'hide_empty' => false));
                            foreach ($categories as $category) {
                                echo '<option value="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="dcbm-search-group">
                        <select name="transmission">
                            <option value=""><?php _e('Transmission', 'digicells-cbm'); ?></option>
                            <option value="automatic"><?php _e('Automatic', 'digicells-cbm'); ?></option>
                            <option value="manual"><?php _e('Manual', 'digicells-cbm'); ?></option>
                        </select>
                    </div>
                    <div class="dcbm-search-group">
                        <button type="submit"><?php _e('Search', 'digicells-cbm'); ?></button>
                    </div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_car_card($car_id) {
        $price = get_post_meta($car_id, '_dcbm_rent_per_day_without_fuel', true);
        $transmission = get_post_meta($car_id, '_dcbm_transmission', true);
        $passenger_capacity = get_post_meta($car_id, '_dcbm_passenger_capacity', true);
        $availability = get_post_meta($car_id, '_dcbm_availability', true);
        $pickup_location = get_post_meta($car_id, '_dcbm_pickup_location', true);
        $model = get_post_meta($car_id, '_dcbm_car_model', true);
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
                    <span class="dcbm-spec"><?php echo ucfirst($transmission); ?></span>
                    <span class="dcbm-spec">👥 <?php echo $passenger_capacity; ?> seats</span>
                </div>
                <div class="dcbm-car-location">
                    📍 <?php echo esc_html($pickup_location); ?>
                </div>
                <div class="dcbm-car-price">
                    <span class="dcbm-price">PKR <?php echo number_format($price); ?></span>
                    <span class="dcbm-price-period">/ day</span>
                </div>
                <div class="dcbm-car-buttons">
                    <a href="<?php echo get_permalink(); ?>" class="dcbm-btn dcbm-btn-outline"><?php _e('More Info', 'digicells-cbm'); ?></a>
                    <button class="dcbm-btn dcbm-btn-primary book-now-btn" data-car-id="<?php echo $car_id; ?>" data-car-price="<?php echo $price; ?>">
                        <?php _e('Book Now', 'digicells-cbm'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}