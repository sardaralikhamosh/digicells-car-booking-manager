<div class="dcbm-car-listing-wrapper">
    <?php if ($atts['show_filter'] == 'yes'): ?>
    <div class="dcbm-filters">
        <input type="text" id="dcbm-search-input" placeholder="Search cars...">
        <select id="dcbm-category-filter">
            <option value="">All Categories</option>
            <?php
            $categories = get_terms(array('taxonomy' => 'dcbm_car_category', 'hide_empty' => false));
            foreach ($categories as $cat) {
                echo '<option value="' . esc_attr($cat->slug) . '">' . esc_html($cat->name) . '</option>';
            }
            ?>
        </select>
        <select id="dcbm-transmission-filter">
            <option value="">Transmission</option>
            <option value="automatic">Automatic</option>
            <option value="manual">Manual</option>
        </select>
        <button id="dcbm-search-btn">Search</button>
    </div>
    <?php endif; ?>
    
    <div class="dcbm-cars-grid">
        <?php
        $args = array(
            'post_type' => 'dcbm_car',
            'posts_per_page' => $atts['per_page'],
            'post_status' => 'publish',
        );
        $cars = new WP_Query($args);
        
        if ($cars->have_posts()):
            while ($cars->have_posts()): $cars->the_post();
                $price = get_post_meta(get_the_ID(), '_dcbm_price_per_day', true);
                $trans = get_post_meta(get_the_ID(), '_dcbm_transmission', true);
                $capacity = get_post_meta(get_the_ID(), '_dcbm_passenger_capacity', true);
                $availability = get_post_meta(get_the_ID(), '_dcbm_availability', true);
                $location = get_post_meta(get_the_ID(), '_dcbm_pickup_location', true);
                
                if (!$availability) $availability = 'available';
        ?>
            <div class="dcbm-car-card">
                <div class="dcbm-car-image">
                    <?php if (has_post_thumbnail()): ?>
                        <?php the_post_thumbnail('medium'); ?>
                    <?php else: ?>
                        <img src="<?php echo DCBM_PLUGIN_URL . 'assets/images/placeholder-car.jpg'; ?>" alt="Car">
                    <?php endif; ?>
                    <span class="dcbm-availability dcbm-availability-<?php echo $availability; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $availability)); ?>
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
                        <a href="<?php echo get_permalink(); ?>" class="dcbm-btn dcbm-btn-outline">Details</a>
                        <?php if ($availability == 'available'): ?>
                            <button class="dcbm-btn dcbm-btn-primary book-now" data-id="<?php echo get_the_ID(); ?>" data-price="<?php echo $price; ?>">Book Now</button>
                        <?php else: ?>
                            <button class="dcbm-btn dcbm-btn-primary" disabled style="opacity:0.5;cursor:not-allowed;">Not Available</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php
            endwhile;
            wp_reset_postdata();
        else:
        ?>
            <div style="text-align:center;padding:50px;">No cars found.</div>
        <?php endif; ?>
    </div>
</div>