<?php
get_header();

while (have_posts()) : the_post();
    $car_id = get_the_ID();
    $price = get_post_meta($car_id, '_dcbm_price_per_day', true);
    $transmission = get_post_meta($car_id, '_dcbm_transmission', true);
    $fuel_type = get_post_meta($car_id, '_dcbm_fuel_type', true);
    $capacity = get_post_meta($car_id, '_dcbm_passenger_capacity', true);
    $car_model = get_post_meta($car_id, '_dcbm_car_model', true);
    $manufacturer = get_post_meta($car_id, '_dcbm_manufacturer', true);
    $reg_year = get_post_meta($car_id, '_dcbm_registration_year', true);
    $car_number = get_post_meta($car_id, '_dcbm_car_number', true);
    $pickup_location = get_post_meta($car_id, '_dcbm_pickup_location', true);
    $availability = get_post_meta($car_id, '_dcbm_availability', true);
    
    if (!$availability) $availability = 'available';
?>
<style>
.dcbm-single-car {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}
.dcbm-single-car h1 {
    font-size: 2rem;
    margin-bottom: 20px;
    color: #1a1a2e;
}
.dcbm-single-car-image {
    margin-bottom: 30px;
}
.dcbm-single-car-image img {
    width: 100%;
    border-radius: 16px;
}
.dcbm-single-car-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin: 30px 0;
}
.dcbm-car-detail-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.dcbm-car-detail-card h3 {
    color: #f68511;
    margin-bottom: 15px;
}
.dcbm-detail-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e2e8f0;
}
.dcbm-detail-label {
    font-weight: 600;
    color: #64748b;
}
.dcbm-detail-value {
    color: #1a1a2e;
}
.dcbm-book-now-section {
    text-align: center;
    margin: 40px 0;
}
.dcbm-book-now-btn {
    background: #f68511;
    color: white;
    border: none;
    padding: 15px 40px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 40px;
    cursor: pointer;
}
.dcbm-book-now-btn:hover {
    background: #0048a5;
}
</style>

<div class="dcbm-single-car">
    <h1><?php the_title(); ?></h1>
    
    <div class="dcbm-single-car-image">
        <?php if (has_post_thumbnail()): ?>
            <?php the_post_thumbnail('large'); ?>
        <?php endif; ?>
    </div>
    
    <div class="dcbm-single-car-grid">
        <div class="dcbm-car-detail-card">
            <h3>Specifications</h3>
            <div class="dcbm-detail-row">
                <span class="dcbm-detail-label">Model:</span>
                <span class="dcbm-detail-value"><?php echo esc_html($car_model); ?></span>
            </div>
            <div class="dcbm-detail-row">
                <span class="dcbm-detail-label">Manufacturer:</span>
                <span class="dcbm-detail-value"><?php echo esc_html($manufacturer); ?></span>
            </div>
            <div class="dcbm-detail-row">
                <span class="dcbm-detail-label">Year:</span>
                <span class="dcbm-detail-value"><?php echo esc_html($reg_year); ?></span>
            </div>
            <div class="dcbm-detail-row">
                <span class="dcbm-detail-label">Registration #:</span>
                <span class="dcbm-detail-value"><?php echo esc_html($car_number); ?></span>
            </div>
            <div class="dcbm-detail-row">
                <span class="dcbm-detail-label">Transmission:</span>
                <span class="dcbm-detail-value"><?php echo ucfirst($transmission); ?></span>
            </div>
            <div class="dcbm-detail-row">
                <span class="dcbm-detail-label">Fuel Type:</span>
                <span class="dcbm-detail-value"><?php echo ucfirst($fuel_type); ?></span>
            </div>
            <div class="dcbm-detail-row">
                <span class="dcbm-detail-label">Passengers:</span>
                <span class="dcbm-detail-value"><?php echo $capacity; ?> seats</span>
            </div>
        </div>
        
        <div class="dcbm-car-detail-card">
            <h3>Pricing & Location</h3>
            <div class="dcbm-detail-row">
                <span class="dcbm-detail-label">Price Per Day:</span>
                <span class="dcbm-detail-value">PKR <?php echo number_format($price); ?></span>
            </div>
            <div class="dcbm-detail-row">
                <span class="dcbm-detail-label">Pickup Location:</span>
                <span class="dcbm-detail-value"><?php echo esc_html($pickup_location); ?></span>
            </div>
            <div class="dcbm-detail-row">
                <span class="dcbm-detail-label">Availability:</span>
                <span class="dcbm-detail-value">
                    <span style="color: <?php echo $availability == 'available' ? '#10b981' : ($availability == 'maintenance' ? '#f59e0b' : '#ef4444'); ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $availability)); ?>
                    </span>
                </span>
            </div>
        </div>
        
        <div class="dcbm-car-detail-card">
            <h3>Description</h3>
            <?php the_content(); ?>
        </div>
    </div>
    
    <?php if ($availability == 'available'): ?>
    <div class="dcbm-book-now-section">
        <button class="dcbm-book-now-btn book-now" data-id="<?php echo $car_id; ?>" data-price="<?php echo $price; ?>">
            Book This Car Now
        </button>
    </div>
    <?php endif; ?>
</div>

<?php
endwhile;
get_footer();
?>