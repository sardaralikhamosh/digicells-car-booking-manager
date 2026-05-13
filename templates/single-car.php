<?php
get_header();

while (have_posts()) : the_post();
    $car_id = get_the_ID();
    $gallery = get_post_meta($car_id, '_dcbm_gallery_images', true);
    $gallery_images = $gallery ? explode(',', $gallery) : array();
    ?>
    
    <div class="dcbm-single-car">
        <div class="dcbm-container">
            <div class="dcbm-car-header">
                <h1><?php the_title(); ?></h1>
            </div>
            
            <div class="dcbm-car-gallery">
                <?php if (has_post_thumbnail()): ?>
                    <div class="dcbm-main-image">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($gallery_images)): ?>
                    <div class="dcbm-thumbnails">
                        <?php foreach ($gallery_images as $image_id): ?>
                            <div class="dcbm-thumbnail" onclick="changeMainImage(this)">
                                <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="dcbm-car-info-grid">
                <!-- Specifications -->
                <div class="dcbm-info-card">
                    <h3>Specifications</h3>
                    <div class="dcbm-specs-grid">
                        <?php
                        $specs = array(
                            'Model' => get_post_meta($car_id, '_dcbm_car_model', true),
                            'Manufacturer' => get_post_meta($car_id, '_dcbm_manufacturer', true),
                            'Year' => get_post_meta($car_id, '_dcbm_registration_year', true),
                            'Color' => get_post_meta($car_id, '_dcbm_color', true),
                            'Transmission' => ucfirst(get_post_meta($car_id, '_dcbm_transmission', true)),
                            'Fuel Type' => ucfirst(get_post_meta($car_id, '_dcbm_fuel_type', true)),
                            'Passengers' => get_post_meta($car_id, '_dcbm_passenger_capacity', true) . ' seats',
                            'Luggage' => get_post_meta($car_id, '_dcbm_luggage_capacity', true),
                        );
                        foreach ($specs as $label => $value):
                            if ($value):
                        ?>
                            <div class="dcbm-spec-item">
                                <span class="dcbm-spec-label"><?php echo $label; ?>:</span>
                                <span class="dcbm-spec-value"><?php echo esc_html($value); ?></span>
                            </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>
                
                <!-- Pricing -->
                <div class="dcbm-info-card">
                    <h3>Pricing</h3>
                    <div class="dcbm-pricing-grid">
                        <?php
                        $pricing = array(
                            'Without Fuel' => get_post_meta($car_id, '_dcbm_rent_per_day_without_fuel', true),
                            'With Fuel' => get_post_meta($car_id, '_dcbm_rent_per_day_with_fuel', true),
                            'With Driver' => get_post_meta($car_id, '_dcbm_rent_per_day_with_driver', true),
                            'Security Deposit' => get_post_meta($car_id, '_dcbm_security_deposit', true),
                        );
                        foreach ($pricing as $label => $price):
                            if ($price):
                        ?>
                            <div class="dcbm-pricing-item">
                                <span class="dcbm-pricing-label"><?php echo $label; ?>:</span>
                                <span class="dcbm-pricing-value">PKR <?php echo number_format($price); ?> / day</span>
                            </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>
                
                <!-- Location -->
                <div class="dcbm-info-card">
                    <h3>Location</h3>
                    <div class="dcbm-specs-grid">
                        <div class="dcbm-spec-item">
                            <span class="dcbm-spec-label">Pickup:</span>
                            <span class="dcbm-spec-value"><?php echo esc_html(get_post_meta($car_id, '_dcbm_pickup_location', true)); ?></span>
                        </div>
                        <div class="dcbm-spec-item">
                            <span class="dcbm-spec-label">Drop-off:</span>
                            <span class="dcbm-spec-value"><?php echo esc_html(get_post_meta($car_id, '_dcbm_dropoff_location', true)); ?></span>
                        </div>
                        <div class="dcbm-spec-item">
                            <span class="dcbm-spec-label">City:</span>
                            <span class="dcbm-spec-value"><?php echo esc_html(get_post_meta($car_id, '_dcbm_city', true)); ?></span>
                        </div>
                    </div>
                    <?php $map_link = get_post_meta($car_id, '_dcbm_google_map_link', true); ?>
                    <?php if ($map_link): ?>
                        <a href="<?php echo esc_url($map_link); ?>" target="_blank" class="dcbm-btn dcbm-btn-outline" style="margin-top: 15px; display: inline-block;">View on Map</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Description -->
            <div class="dcbm-info-card" style="margin-bottom: 30px;">
                <h3>Description</h3>
                <?php the_content(); ?>
            </div>
            
            <!-- Features -->
            <div class="dcbm-info-card" style="margin-bottom: 30px;">
                <h3>Features</h3>
                <div class="dcbm-specs-grid">
                    <?php
                    $features = array(
                        'Air Conditioning' => get_post_meta($car_id, '_dcbm_air_conditioning', true),
                        'GPS Navigation' => get_post_meta($car_id, '_dcbm_gps', true),
                        'Bluetooth' => get_post_meta($car_id, '_dcbm_bluetooth', true),
                        'USB Charging' => get_post_meta($car_id, '_dcbm_usb_charging', true),
                        'Music System' => get_post_meta($car_id, '_dcbm_music_system', true),
                    );
                    foreach ($features as $label => $feature):
                        if ($feature == 'yes'):
                    ?>
                        <div class="dcbm-spec-item">
                            <span class="dcbm-spec-value">✓ <?php echo $label; ?></span>
                        </div>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
            </div>
            
            <!-- Book Now Button -->
            <div style="text-align: center; margin: 40px 0;">
                <button class="dcbm-btn dcbm-btn-primary book-now-btn" 
                        data-car-id="<?php echo $car_id; ?>" 
                        data-car-price="<?php echo get_post_meta($car_id, '_dcbm_rent_per_day_without_fuel', true); ?>"
                        style="padding: 15px 40px; font-size: 1.1rem;">
                    Book This Car Now
                </button>
            </div>
            
            <!-- Related Cars -->
            <?php
            $related_args = array(
                'post_type' => 'dcbm_car',
                'posts_per_page' => 3,
                'post__not_in' => array($car_id),
                'meta_key' => '_dcbm_availability',
                'meta_value' => 'available',
            );
            $related = new WP_Query($related_args);
            if ($related->have_posts()):
            ?>
                <div class="dcbm-related-cars">
                    <h3>Related Cars You Might Like</h3>
                    <div class="dcbm-cars-grid">
                        <?php while ($related->have_posts()) : $related->the_post(); ?>
                            <?php echo do_shortcode('[digicells_car_listing]'); ?>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php
            endif;
            wp_reset_postdata();
            ?>
        </div>
    </div>
    
    <?php include DCBM_PLUGIN_DIR . 'templates/booking-form.php'; ?>
    
    <script>
    function changeMainImage(element) {
        const imgSrc = $(element).find('img').attr('src');
        $('.dcbm-main-image img').attr('src', imgSrc);
        $('.dcbm-thumbnail').removeClass('active');
        $(element).addClass('active');
    }
    </script>
    
<?php endwhile;

get_footer();
?>