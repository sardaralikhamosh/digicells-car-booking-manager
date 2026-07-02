<?php get_header(); while (have_posts()) : the_post();
    $car_id = get_the_ID();
    $price = get_post_meta($car_id, '_dcbm_price_per_day', true);
    $transmission = get_post_meta($car_id, '_dcbm_transmission', true);
    $capacity = get_post_meta($car_id, '_dcbm_passenger_capacity', true);
    $car_model = get_post_meta($car_id, '_dcbm_car_model', true);
    $manufacturer = get_post_meta($car_id, '_dcbm_manufacturer', true);
    $location_id = get_post_meta($car_id, '_dcbm_location_id', true);
    $location_name = $location_id ? get_the_title($location_id) : '';
    $agent_id = get_post_meta($car_id, '_dcbm_agent_id', true);
    $agent_name = $agent_id ? get_the_title($agent_id) : '';
    $availability = get_post_meta($car_id, '_dcbm_availability', true);
    if (!$availability) $availability = 'available';
?>
<div class="dcbm-single-car" style="max-width:1200px;margin:40px auto;padding:0 20px;">
    <h1><?php the_title(); ?></h1>
    <div class="dcbm-single-car-image"><?php if(has_post_thumbnail()) the_post_thumbnail('large'); ?></div>
    <div class="dcbm-single-car-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:30px;">
        <div class="dcbm-car-detail-card"><h3>Specifications</h3><div class="dcbm-detail-row"><span>Model:</span><span><?php echo esc_html($car_model); ?></span></div><div class="dcbm-detail-row"><span>Manufacturer:</span><span><?php echo esc_html($manufacturer); ?></span></div><div class="dcbm-detail-row"><span>Transmission:</span><span><?php echo ucfirst($transmission); ?></span></div><div class="dcbm-detail-row"><span>Passengers:</span><span><?php echo $capacity; ?> seats</span></div></div>
        <div class="dcbm-car-detail-card"><h3>Pricing & Location</h3><div class="dcbm-detail-row"><span>Price Per Day:</span><span>PKR <?php echo number_format($price); ?></span></div><div class="dcbm-detail-row"><span>Location:</span><span><?php echo esc_html($location_name); ?></span></div><div class="dcbm-detail-row"><span>Agent:</span><span><?php echo esc_html($agent_name); ?></span></div><div class="dcbm-detail-row"><span>Availability:</span><span style="color:<?php echo $availability=='available'?'#10b981':'#ef4444';?>"><?php echo ucfirst($availability); ?></span></div></div>
    </div>
    <?php if($availability=='available'): ?><div class="dcbm-book-now-section" style="text-align:center;margin:40px 0;"><button class="dcbm-book-now-btn book-now" data-id="<?php echo $car_id; ?>" data-price="<?php echo $price; ?>">Book This Car Now</button></div><?php endif; ?>
</div>
<?php endwhile; get_footer(); ?>