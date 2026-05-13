<?php
class DCBM_Extra_Services {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'dcbm_extra_services';
        add_action('admin_menu', array($this, 'add_services_menu'));
        add_action('admin_post_dcbm_save_service', array($this, 'save_service'));
        add_action('admin_post_dcbm_delete_service', array($this, 'delete_service'));
    }
    
    public function add_services_menu() {
        add_submenu_page(
            'edit.php?post_type=dcbm_car',
            __('Extra Services', 'digicells-cbm'),
            __('Extra Services', 'digicells-cbm'),
            'manage_options',
            'dcbm-extra-services',
            array($this, 'render_services_page')
        );
    }
    
    public function render_services_page() {
        $services = $this->get_services();
        ?>
        <div class="wrap">
            <h1><?php _e('Extra Services', 'digicells-cbm'); ?></h1>
            
            <div class="dcbm-services-container">
                <div class="dcbm-add-service-form">
                    <h2><?php _e('Add New Service', 'digicells-cbm'); ?></h2>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="dcbm_save_service">
                        <?php wp_nonce_field('dcbm_save_service', 'dcbm_service_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th><label><?php _e('Service Name:', 'digicells-cbm'); ?></label></th>
                                <td><input type="text" name="service_name" class="regular-text" required></td>
                            </tr>
                            <tr>
                                <th><label><?php _e('Service Icon (FontAwesome/Emoji):', 'digicells-cbm'); ?></label></th>
                                <td><input type="text" name="service_icon" class="regular-text" placeholder="🚗"></td>
                            </tr>
                            <tr>
                                <th><label><?php _e('Price Type:', 'digicells-cbm'); ?></label></th>
                                <td>
                                    <select name="price_type">
                                        <option value="free"><?php _e('Free', 'digicells-cbm'); ?></option>
                                        <option value="paid"><?php _e('Paid', 'digicells-cbm'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label><?php _e('Price (PKR):', 'digicells-cbm'); ?></label></th>
                                <td><input type="number" name="price" value="0" step="100"></td>
                            </tr>
                            <tr>
                                <th><label><?php _e('Status:', 'digicells-cbm'); ?></label></th>
                                <td>
                                    <select name="status">
                                        <option value="enabled"><?php _e('Enabled', 'digicells-cbm'); ?></option>
                                        <option value="disabled"><?php _e('Disabled', 'digicells-cbm'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(__('Add Service', 'digicells-cbm')); ?>
                    </form>
                </div>
                
                <div class="dcbm-services-list">
                    <h2><?php _e('Existing Services', 'digicells-cbm'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Icon', 'digicells-cbm'); ?></th>
                                <th><?php _e('Service Name', 'digicells-cbm'); ?></th>
                                <th><?php _e('Price Type', 'digicells-cbm'); ?></th>
                                <th><?php _e('Price', 'digicells-cbm'); ?></th>
                                <th><?php _e('Status', 'digicells-cbm'); ?></th>
                                <th><?php _e('Actions', 'digicells-cbm'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($services): ?>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><?php echo esc_html($service->icon); ?></td>
                                        <td><?php echo esc_html($service->name); ?></td>
                                        <td><?php echo ucfirst($service->price_type); ?></td>
                                        <td><?php echo $service->price_type == 'paid' ? 'PKR ' . number_format($service->price) : 'Free'; ?></td>
                                        <td>
                                            <span class="dcbm-status dcbm-status-<?php echo $service->status; ?>">
                                                <?php echo ucfirst($service->status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
                                                <input type="hidden" name="action" value="dcbm_delete_service">
                                                <input type="hidden" name="service_id" value="<?php echo $service->id; ?>">
                                                <?php wp_nonce_field('dcbm_delete_service', 'dcbm_delete_nonce'); ?>
                                                <button type="submit" class="button button-small" onclick="return confirm('<?php _e('Delete this service?', 'digicells-cbm'); ?>')">
                                                    <?php _e('Delete', 'digicells-cbm'); ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6"><?php _e('No services found.', 'digicells-cbm'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function save_service() {
        if (!wp_verify_nonce($_POST['dcbm_service_nonce'], 'dcbm_save_service')) {
            wp_die(__('Security check failed', 'digicells-cbm'));
        }
        
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'name' => sanitize_text_field($_POST['service_name']),
                'icon' => sanitize_text_field($_POST['service_icon']),
                'price_type' => sanitize_text_field($_POST['price_type']),
                'price' => floatval($_POST['price']),
                'status' => sanitize_text_field($_POST['status']),
            ),
            array('%s', '%s', '%s', '%f', '%s')
        );
        
        wp_redirect(add_query_arg('message', 'saved', wp_get_referer()));
        exit;
    }
    
    public function delete_service() {
        if (!wp_verify_nonce($_POST['dcbm_delete_nonce'], 'dcbm_delete_service')) {
            wp_die(__('Security check failed', 'digicells-cbm'));
        }
        
        global $wpdb;
        $wpdb->delete($this->table_name, array('id' => intval($_POST['service_id'])), array('%d'));
        
        wp_redirect(wp_get_referer());
        exit;
    }
    
    public function get_services($enabled_only = true) {
        global $wpdb;
        $where = $enabled_only ? "WHERE status = 'enabled'" : "";
        return $wpdb->get_results("SELECT * FROM {$this->table_name} $where ORDER BY id ASC");
    }
}