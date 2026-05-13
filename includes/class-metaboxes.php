<?php
class DCBM_Metaboxes {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_car_metaboxes'));
        add_action('save_post_dcbm_car', array($this, 'save_car_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_metabox_assets'));
    }
    
    public function add_car_metaboxes() {
        add_meta_box(
            'dcbm_car_basic_info',
            __('Basic Information', 'digicells-cbm'),
            array($this, 'render_basic_info_metabox'),
            'dcbm_car',
            'normal',
            'high'
        );
        
        add_meta_box(
            'dcbm_car_specifications',
            __('Car Specifications', 'digicells-cbm'),
            array($this, 'render_specifications_metabox'),
            'dcbm_car',
            'normal',
            'high'
        );
        
        add_meta_box(
            'dcbm_car_booking_options',
            __('Booking Options', 'digicells-cbm'),
            array($this, 'render_booking_options_metabox'),
            'dcbm_car',
            'normal',
            'high'
        );
        
        add_meta_box(
            'dcbm_car_pricing',
            __('Pricing Section', 'digicells-cbm'),
            array($this, 'render_pricing_metabox'),
            'dcbm_car',
            'normal',
            'high'
        );
        
        add_meta_box(
            'dcbm_car_location',
            __('Location Section', 'digicells-cbm'),
            array($this, 'render_location_metabox'),
            'dcbm_car',
            'normal',
            'high'
        );
        
        add_meta_box(
            'dcbm_car_availability',
            __('Availability Settings', 'digicells-cbm'),
            array($this, 'render_availability_metabox'),
            'dcbm_car',
            'side',
            'high'
        );
        
        add_meta_box(
            'dcbm_car_gallery',
            __('Car Gallery', 'digicells-cbm'),
            array($this, 'render_gallery_metabox'),
            'dcbm_car',
            'normal',
            'high'
        );
    }
    
    public function render_basic_info_metabox($post) {
        wp_nonce_field('dcbm_save_car_meta', 'dcbm_car_meta_nonce');
        
        $car_model = get_post_meta($post->ID, '_dcbm_car_model', true);
        $manufacturer = get_post_meta($post->ID, '_dcbm_manufacturer', true);
        $reg_year = get_post_meta($post->ID, '_dcbm_registration_year', true);
        $car_number = get_post_meta($post->ID, '_dcbm_car_number', true);
        ?>
        <div class="dcbm-metabox-grid">
            <div class="dcbm-field-group">
                <label><?php _e('Car Name:', 'digicells-cbm'); ?></label>
                <input type="text" name="dcbm_car_model" value="<?php echo esc_attr($car_model); ?>" class="widefat">
                <p class="description"><?php _e('e.g., Toyota Corolla', 'digicells-cbm'); ?></p>
            </div>
            
            <div class="dcbm-field-group">
                <label><?php _e('Manufacturer:', 'digicells-cbm'); ?></label>
                <input type="text" name="dcbm_manufacturer" value="<?php echo esc_attr($manufacturer); ?>" class="widefat">
                <p class="description"><?php _e('e.g., Toyota, Honda, Suzuki', 'digicells-cbm'); ?></p>
            </div>
            
            <div class="dcbm-field-group">
                <label><?php _e('Registration Year:', 'digicells-cbm'); ?></label>
                <select name="dcbm_registration_year" class="widefat">
                    <option value=""><?php _e('Select Year', 'digicells-cbm'); ?></option>
                    <?php for ($year = date('Y'); $year >= 2000; $year--): ?>
                        <option value="<?php echo $year; ?>" <?php selected($reg_year, $year); ?>><?php echo $year; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="dcbm-field-group">
                <label><?php _e('Car Number / Registration Number:', 'digicells-cbm'); ?></label>
                <input type="text" name="dcbm_car_number" value="<?php echo esc_attr($car_number); ?>" class="widefat">
                <p class="description"><?php _e('e.g., ABC-123', 'digicells-cbm'); ?></p>
            </div>
        </div>
        <?php
    }
    
    public function render_specifications_metabox($post) {
        $color = get_post_meta($post->ID, '_dcbm_color', true);
        $transmission = get_post_meta($post->ID, '_dcbm_transmission', true);
        $fuel_type = get_post_meta($post->ID, '_dcbm_fuel_type', true);
        $passenger_capacity = get_post_meta($post->ID, '_dcbm_passenger_capacity', true);
        $luggage_capacity = get_post_meta($post->ID, '_dcbm_luggage_capacity', true);
        $air_conditioning = get_post_meta($post->ID, '_dcbm_air_conditioning', true);
        $gps = get_post_meta($post->ID, '_dcbm_gps', true);
        $bluetooth = get_post_meta($post->ID, '_dcbm_bluetooth', true);
        $usb_charging = get_post_meta($post->ID, '_dcbm_usb_charging', true);
        $music_system = get_post_meta($post->ID, '_dcbm_music_system', true);
        ?>
        <div class="dcbm-metabox-grid">
            <div class="dcbm-field-group">
                <label><?php _e('Color:', 'digicells-cbm'); ?></label>
                <input type="text" name="dcbm_color" value="<?php echo esc_attr($color); ?>" class="widefat">
            </div>
            
            <div class="dcbm-field-group">
                <label><?php _e('Transmission Type:', 'digicells-cbm'); ?></label>
                <select name="dcbm_transmission" class="widefat">
                    <option value="automatic" <?php selected($transmission, 'automatic'); ?>><?php _e('Automatic', 'digicells-cbm'); ?></option>
                    <option value="manual" <?php selected($transmission, 'manual'); ?>><?php _e('Manual', 'digicells-cbm'); ?></option>
                </select>
            </div>
            
            <div class="dcbm-field-group">
                <label><?php _e('Fuel Type:', 'digicells-cbm'); ?></label>
                <select name="dcbm_fuel_type" class="widefat">
                    <option value="petrol" <?php selected($fuel_type, 'petrol'); ?>><?php _e('Petrol', 'digicells-cbm'); ?></option>
                    <option value="diesel" <?php selected($fuel_type, 'diesel'); ?>><?php _e('Diesel', 'digicells-cbm'); ?></option>
                    <option value="hybrid" <?php selected($fuel_type, 'hybrid'); ?>><?php _e('Hybrid', 'digicells-cbm'); ?></option>
                    <option value="electric" <?php selected($fuel_type, 'electric'); ?>><?php _e('Electric', 'digicells-cbm'); ?></option>
                </select>
            </div>
            
            <div class="dcbm-field-group">
                <label><?php _e('Passenger Capacity:', 'digicells-cbm'); ?></label>
                <input type="number" name="dcbm_passenger_capacity" value="<?php echo esc_attr($passenger_capacity); ?>" class="widefat">
            </div>
            
            <div class="dcbm-field-group">
                <label><?php _e('Luggage Capacity:', 'digicells-cbm'); ?></label>
                <input type="text" name="dcbm_luggage_capacity" value="<?php echo esc_attr($luggage_capacity); ?>" class="widefat" placeholder="e.g., 2 Bags">
            </div>
            
            <div class="dcbm-field-group-checkbox">
                <label>
                    <input type="checkbox" name="dcbm_air_conditioning" value="yes" <?php checked($air_conditioning, 'yes'); ?>>
                    <?php _e('Air Conditioning', 'digicells-cbm'); ?>
                </label>
            </div>
            
            <div class="dcbm-field-group-checkbox">
                <label>
                    <input type="checkbox" name="dcbm_gps" value="yes" <?php checked($gps, 'yes'); ?>>
                    <?php _e('GPS Navigation', 'digicells-cbm'); ?>
                </label>
            </div>
            
            <div class="dcbm-field-group-checkbox">
                <label>
                    <input type="checkbox" name="dcbm_bluetooth" value="yes" <?php checked($bluetooth, 'yes'); ?>>
                    <?php _e('Bluetooth', 'digicells-cbm'); ?>
                </label>
            </div>
            
            <div class="dcbm-field-group-checkbox">
                <label>
                    <input type="checkbox" name="dcbm_usb_charging" value="yes" <?php checked($usb_charging, 'yes'); ?>>
                    <?php _e('USB Charging', 'digicells-cbm'); ?>
                </label>
            </div>
            
            <div class="dcbm-field-group-checkbox">
                <label>
                    <input type="checkbox" name="dcbm_music_system" value="yes" <?php checked($music_system, 'yes'); ?>>
                    <?php _e('Music System', 'digicells-cbm'); ?>
                </label>
            </div>
        </div>
        <?php
    }
    
    public function render_booking_options_metabox($post) {
        $with_driver = get_post_meta($post->ID, '_dcbm_with_driver', true);
        $without_driver = get_post_meta($post->ID, '_dcbm_without_driver', true);
        ?>
        <div class="dcbm-metabox-grid">
            <div class="dcbm-field-group-checkbox">
                <label>
                    <input type="checkbox" name="dcbm_with_driver" value="yes" <?php checked($with_driver, 'yes'); ?>>
                    <?php _e('With Driver Available', 'digicells-cbm'); ?>
                </label>
            </div>
            
            <div class="dcbm-field-group-checkbox">
                <label>
                    <input type="checkbox" name="dcbm_without_driver" value="yes" <?php checked($without_driver, 'yes'); ?>>
                    <?php _e('Without Driver Available', 'digicells-cbm'); ?>
                </label>
            </div>
        </div>
        <?php
    }
    
    public function render_pricing_metabox($post) {
        $rent_without_fuel = get_post_meta($post->ID, '_dcbm_rent_per_day_without_fuel', true);
        $rent_with_fuel = get_post_meta($post->ID, '_dcbm_rent_per_day_with_fuel', true);
        $rent_with_driver = get_post_meta($post->ID, '_dcbm_rent_per_day_with_driver', true);
        $rent_without_driver = get_post_meta($post->ID, '_dcbm_rent_per_day_without_driver', true);
        $security_deposit = get_post_meta($post->ID, '_dcbm_security_deposit', true);
        $extra_km_charges = get_post_meta($post->ID, '_dcbm_extra_km_charges', true);
        ?>
        <div class="dcbm-metabox-grid">
            <div class="dcbm-field-group">
                <label><?php _e('Rent Per Day (Without Fuel):', 'digicells-cbm'); ?></label>
                <input type="number" name="dcbm_rent_per_day_without_fuel" value="<?php echo esc_attr($rent_without_fuel); ?>" class="widefat">
                <p class="description"><?php _e('Price in PKR', 'digicells-cbm'); ?></p>
            </div>
            
            <div class="dcbm-field-group">
                <label><?php _e('Rent Per Day (With Fuel):', 'digicells-cbm'); ?></label>
                <input type="number" name="dcbm_rent_per_day_with_fuel" value="<?php echo esc_attr($rent_with_fuel); ?>" class="widefat">
            </div>
            
            <div class="dcbm-field-group">
                <label><?php _e('Rent Per Day (With Driver):', 'digicells-cbm'); ?></label>
                <input type="number" name="dcbm_rent_per_day_with_driver" value="<?php echo esc_attr($rent_with_driver); ?>" class="widefat">
            </div>
            
            <div class="dcbm-field-group">
                <label><?php _e('Rent Per Day (Without Driver):', 'digicells-cbm'); ?></label>
                <input type="number" name="dcbm_rent_per_day_without_driver" value="<?php echo esc_attr($rent_without_driver); ?>" class="widefat">
            </div>
            
            <div class="dcbm-field-group">
                <label><?php _e('Security Deposit:', 'digicells-cbm'); ?></label>
                <input type="number" name="dcbm_security_deposit" value="<?php echo esc_attr($security_deposit); ?>" class="widefat">
            </div>
            
            <div class="dcbm-field-group">
                <label><?php _e('Extra Kilometer Charges:', 'digicells-cbm'); ?></label>
                <input type="number" name="dcbm_extra_km_charges" value="<?php echo esc_attr($extra_km_charges); ?>" class="widefat">
                <p class="description"><?php _e('per km', 'digicells-cbm'); ?></p>
            </div>
        </div>
        <?php
    }
    
    public function render_location_metabox($post) {
        $pickup_location = get_post_meta($post->ID, '_dcbm_pickup_location', true);
        $dropoff_location = get_post_meta($post->ID, '_dcbm_dropoff_location', true);
        $google_map_link = get_post_meta($post->ID, '_dcbm_google_map_link', true);
        $city = get_post_meta($post->ID, '_dcbm_city', true);
        ?>
        <div class="dcbm-metabox-grid">
            <div class="dcbm-field-group">
                <label><?php _e('Pickup Location:', 'digicells-cbm'); ?></label>
                <input type="text" name="dcbm_pickup_location" value="<?php echo esc_attr($pickup_location); ?>" class="widefat">
            </div>
            
            <div class="dcbm-field-group">
                <label><?php _e('Drop-off Location:', 'digicells-cbm'); ?></label>
                <input type="text" name="dcbm_dropoff_location" value="<?php echo esc_attr($dropoff_location); ?>" class="widefat">
            </div>
            
            <div class="dcbm-field-group">
                <label><?php _e('Google Map Link:', 'digicells-cbm'); ?></label>
                <input type="url" name="dcbm_google_map_link" value="<?php echo esc_attr($google_map_link); ?>" class="widefat">
            </div>
            
            <div class="dcbm-field-group">
                <label><?php _e('City:', 'digicells-cbm'); ?></label>
                <input type="text" name="dcbm_city" value="<?php echo esc_attr($city); ?>" class="widefat">
            </div>
        </div>
        <?php
    }
    
    public function render_availability_metabox($post) {
        $availability = get_post_meta($post->ID, '_dcbm_availability', true);
        if (!$availability) {
            $availability = 'available';
        }
        ?>
        <div class="dcbm-metabox-grid">
            <div class="dcbm-field-group">
                <select name="dcbm_availability" class="widefat">
                    <option value="available" <?php selected($availability, 'available'); ?>><?php _e('Available', 'digicells-cbm'); ?></option>
                    <option value="not_available" <?php selected($availability, 'not_available'); ?>><?php _e('Not Available', 'digicells-cbm'); ?></option>
                    <option value="maintenance" <?php selected($availability, 'maintenance'); ?>><?php _e('Maintenance Mode', 'digicells-cbm'); ?></option>
                </select>
            </div>
        </div>
        <?php
    }
    
    public function render_gallery_metabox($post) {
        $gallery_images = get_post_meta($post->ID, '_dcbm_gallery_images', true);
        $gallery_images = $gallery_images ? explode(',', $gallery_images) : array();
        ?>
        <div class="dcbm-gallery-wrapper">
            <div class="dcbm-gallery-images">
                <?php foreach ($gallery_images as $image_id): ?>
                    <div class="dcbm-gallery-image">
                        <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                        <input type="hidden" name="dcbm_gallery_images[]" value="<?php echo esc_attr($image_id); ?>">
                        <button type="button" class="button dcbm-remove-gallery-image"><?php _e('Remove', 'digicells-cbm'); ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button button-primary" id="dcbm_add_gallery_images"><?php _e('Add Images to Gallery', 'digicells-cbm'); ?></button>
        </div>
        <?php
    }
    
    public function save_car_meta($post_id) {
        if (!isset($_POST['dcbm_car_meta_nonce']) || !wp_verify_nonce($_POST['dcbm_car_meta_nonce'], 'dcbm_save_car_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save all meta fields
        $fields = array(
            'dcbm_car_model', 'dcbm_manufacturer', 'dcbm_registration_year', 'dcbm_car_number',
            'dcbm_color', 'dcbm_transmission', 'dcbm_fuel_type', 'dcbm_passenger_capacity',
            'dcbm_luggage_capacity', 'dcbm_rent_per_day_without_fuel', 'dcbm_rent_per_day_with_fuel',
            'dcbm_rent_per_day_with_driver', 'dcbm_rent_per_day_without_driver', 'dcbm_security_deposit',
            'dcbm_extra_km_charges', 'dcbm_pickup_location', 'dcbm_dropoff_location',
            'dcbm_google_map_link', 'dcbm_city', 'dcbm_availability'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Save checkbox fields
        $checkboxes = array(
            'dcbm_air_conditioning', 'dcbm_gps', 'dcbm_bluetooth', 
            'dcbm_usb_charging', 'dcbm_music_system', 'dcbm_with_driver', 'dcbm_without_driver'
        );
        
        foreach ($checkboxes as $checkbox) {
            if (isset($_POST[$checkbox])) {
                update_post_meta($post_id, '_' . $checkbox, 'yes');
            } else {
                delete_post_meta($post_id, '_' . $checkbox);
            }
        }
        
        // Save gallery
        if (isset($_POST['dcbm_gallery_images']) && is_array($_POST['dcbm_gallery_images'])) {
            $gallery_ids = array_map('intval', $_POST['dcbm_gallery_images']);
            update_post_meta($post_id, '_dcbm_gallery_images', implode(',', $gallery_ids));
        } else {
            delete_post_meta($post_id, '_dcbm_gallery_images');
        }
    }
    
    public function enqueue_metabox_assets() {
        global $post;
        if ($post && $post->post_type == 'dcbm_car') {
            wp_enqueue_media();
        }
    }
}