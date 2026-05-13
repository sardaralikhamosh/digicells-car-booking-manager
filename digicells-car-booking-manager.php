<?php
/**
 * Plugin Name: Digicells Car Booking Manager
 * Plugin URI: https://hamaribooking.com/
 * Description: Professional car rental and booking management system for Hamari Booking
 * Version: 1.0.0
 * Author: Digicells
 * Text Domain: digicells-cbm
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DCBM_VERSION', '1.0.0');
define('DCBM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DCBM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DCBM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if Elementor is active
function dcbm_check_elementor() {
    // Plugin can work without Elementor, but registers widgets if available
    if (did_action('elementor/loaded')) {
        require_once DCBM_PLUGIN_DIR . 'includes/class-elementor-widgets.php';
    }
}
add_action('plugins_loaded', 'dcbm_check_elementor');

// Autoloader for classes
spl_autoload_register(function ($class) {
    $prefix = 'DCBM_';
    $base_dir = DCBM_PLUGIN_DIR . 'includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Activation hook
register_activation_hook(__FILE__, 'dcbm_activate');
function dcbm_activate() {
    require_once DCBM_PLUGIN_DIR . 'includes/class-activator.php';
    DCBM_Activator::activate();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'dcbm_deactivate');
function dcbm_deactivate() {
    flush_rewrite_rules();
}

// Initialize plugin
add_action('init', 'dcbm_init');
function dcbm_init() {
    // Initialize classes
    new DCBM_Post_Types();
    new DCBM_Taxonomies();
    new DCBM_Metaboxes();
    new DCBM_Shortcodes();
    new DCBM_Ajax_Handler();
    
    if (is_admin()) {
        new DCBM_Extra_Services();
        new DCBM_Booking_Manager();
    }
}

// Enqueue admin assets
add_action('admin_enqueue_scripts', 'dcbm_admin_assets');
function dcbm_admin_assets($hook) {
    wp_enqueue_media();
    wp_enqueue_style('dcbm-admin-style', DCBM_PLUGIN_URL . 'assets/css/admin-style.css', array(), DCBM_VERSION);
    wp_enqueue_script('dcbm-admin-script', DCBM_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), DCBM_VERSION, true);
}

// Enqueue frontend assets
add_action('wp_enqueue_scripts', 'dcbm_frontend_assets');
function dcbm_frontend_assets() {
    // Enqueue Google Fonts (matching Hamari Booking)
    wp_enqueue_style('google-fonts-poppins', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
    
    wp_enqueue_style('dcbm-frontend-style', DCBM_PLUGIN_URL . 'assets/css/frontend-style.css', array(), DCBM_VERSION);
    wp_enqueue_script('dcbm-frontend-script', DCBM_PLUGIN_URL . 'assets/js/frontend-script.js', array('jquery'), DCBM_VERSION, true);
    
    // Localize script
    wp_localize_script('dcbm-frontend-script', 'dcbm_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dcbm_nonce'),
        'loading_text' => __('Processing...', 'digicells-cbm'),
        'error_text' => __('An error occurred. Please try again.', 'digicells-cbm'),
    ));
}