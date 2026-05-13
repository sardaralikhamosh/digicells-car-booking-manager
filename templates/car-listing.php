<?php
// Enqueue frontend assets
wp_enqueue_style('dcbm-frontend-style');
wp_enqueue_script('dcbm-frontend-script');

// Get filters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$transmission = isset($_GET['transmission']) ? sanitize_text_field($_GET['transmission']) : '';

// Build query
$args = array(
    'post_type' => 'dcbm_car',
    'posts_per_page' => $atts['per_page'],
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
    'meta_query' => array(
        'relation' => 'AND',
    ),
);

// Search filter
if ($search) {
    $args['s'] = $search;
}

// Transmission filter
if ($transmission) {
    $args['meta_query'][] = array(
        'key' => '_dcbm_transmission',
        'value' => $transmission,
    );
}

// Category filter
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
?>

<div class="dcbm-car-listing-wrapper">
    <?php if ($atts['show_filter'] == 'yes'): ?>
    <div class="dcbm-filters">
        <form id="dcbm-search-form" class="dcbm-filters-grid">
            <div class="dcbm-filter-group">
                <input type="text" name="search" placeholder="Search cars..." value="<?php echo esc_attr($search); ?>">
            </div>
            <div class="dcbm-filter-group">
                <select name="category">
                    <option value="">All Categories</option>
                    <?php
                    $categories = get_terms(array('taxonomy' => 'dcbm_car_category', 'hide_empty' => false));
                    foreach ($categories as $cat) {
                        echo '<option value="' . esc_attr($cat->slug) . '" ' . selected($category, $cat->slug, false) . '>' . esc_html($cat->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="dcbm-filter-group">
                <select name="transmission">
                    <option value="">Transmission</option>
                    <option value="automatic" <?php selected($transmission, 'automatic'); ?>>Automatic</option>
                    <option value="manual" <?php selected($transmission, 'manual'); ?>>Manual</option>
                </select>
            </div>
            <div class="dcbm-filter-group">
                <button type="submit" class="dcbm-btn dcbm-btn-primary">Search</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="dcbm-cars-grid">
        <?php if ($cars->have_posts()): ?>
            <?php while ($cars->have_posts()) : $cars->the_post();
                $car_id = get_the_ID();
                $price = get_post_meta($car_id, '_dcbm_rent_per_day_without_fuel', true);
                $transmission = get_post_meta($car_id, '_dcbm_transmission', true);
                $passenger_capacity = get_post_meta($car_id, '_dcbm_passenger_capacity', true);
                $availability = get_post_meta($car_id, '_dcbm_availability', true);
                $pickup_location = get_post_meta($car_id, '_dcbm_pickup_location', true);
                ?>
                <div class="dcbm-car-card">
                    <div class="dcbm-car-image">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium'); ?>
                        <?php else: ?>
                            <img src="<?php echo DCBM_PLUGIN_URL . 'assets/images/placeholder-car.jpg'; ?>" alt="Car Image">
                        <?php endif; ?>
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
                            <a href="<?php echo get_permalink(); ?>" class="dcbm-btn dcbm-btn-outline">More Info</a>
                            <?php if ($availability == 'available'): ?>
                                <button class="dcbm-btn dcbm-btn-primary book-now-btn" data-car-id="<?php echo $car_id; ?>" data-car-price="<?php echo $price; ?>">
                                    Book Now
                                </button>
                            <?php else: ?>
                                <button class="dcbm-btn dcbm-btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">
                                    Not Available
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="dcbm-no-results">
                <p>No cars found matching your criteria.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($cars->max_num_pages > 1): ?>
        <div class="dcbm-pagination">
            <?php
            echo paginate_links(array(
                'total' => $cars->max_num_pages,
                'current' => max(1, get_query_var('paged')),
                'prev_text' => '« Previous',
                'next_text' => 'Next »',
            ));
            ?>
        </div>
    <?php endif; ?>
</div>

<?php wp_reset_postdata(); ?>