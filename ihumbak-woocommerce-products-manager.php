<?php
/**
 * Plugin Name: iHumbak WooCommerce Products Manager
 * Plugin URI: https://github.com/michalstaniecko/ihumbak-woocommerce-products-manager
 * Description: Advanced WooCommerce products management with filtering and bulk price editing
 * Version: 1.0.0
 * Author: Michał Stanięcko
 * Author URI: https://github.com/michalstaniecko
 * Text Domain: ihumbak-wpm
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'IHUMBAK_WPM_VERSION', '1.0.0' );
define( 'IHUMBAK_WPM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IHUMBAK_WPM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'IHUMBAK_WPM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 */
class IHumbak_WooCommerce_Products_Manager {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if ( ! $this->is_woocommerce_active() ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
            return;
        }

        // Load plugin files
        $this->load_files();

        // Initialize admin
        if ( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        }

        // AJAX actions
        add_action( 'wp_ajax_ihumbak_get_products', array( $this, 'ajax_get_products' ) );
        add_action( 'wp_ajax_ihumbak_update_prices', array( $this, 'ajax_update_prices' ) );
        add_action( 'wp_ajax_ihumbak_bulk_update_prices', array( $this, 'ajax_bulk_update_prices' ) );
        add_action( 'wp_ajax_ihumbak_get_filters', array( $this, 'ajax_get_filters' ) );
    }

    /**
     * Check if WooCommerce is active
     */
    private function is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'iHumbak WooCommerce Products Manager requires WooCommerce to be installed and active.', 'ihumbak-wpm' ); ?></p>
        </div>
        <?php
    }

    /**
     * Load plugin files
     */
    private function load_files() {
        require_once IHUMBAK_WPM_PLUGIN_DIR . 'includes/class-products-handler.php';
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Products Manager', 'ihumbak-wpm' ),
            __( 'Products Manager', 'ihumbak-wpm' ),
            'manage_woocommerce',
            'ihumbak-products-manager',
            array( $this, 'render_admin_page' ),
            'dashicons-products',
            56
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        require_once IHUMBAK_WPM_PLUGIN_DIR . 'includes/admin-page.php';
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_ihumbak-products-manager' !== $hook ) {
            return;
        }

        // Enqueue Vue.js
        wp_enqueue_script(
            'vue',
            'https://cdn.jsdelivr.net/npm/vue@3.3.4/dist/vue.global.prod.js',
            array(),
            '3.3.4',
            true
        );

        // Enqueue custom CSS
        wp_enqueue_style(
            'ihumbak-wpm-admin',
            IHUMBAK_WPM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            IHUMBAK_WPM_VERSION
        );

        // Enqueue custom JS
        wp_enqueue_script(
            'ihumbak-wpm-admin',
            IHUMBAK_WPM_PLUGIN_URL . 'assets/js/admin.js',
            array( 'vue' ),
            IHUMBAK_WPM_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'ihumbak-wpm-admin',
            'ihumbakWpm',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'ihumbak_wpm_nonce' ),
                'i18n' => array(
                    'loading' => __( 'Loading...', 'ihumbak-wpm' ),
                    'noProducts' => __( 'No products found', 'ihumbak-wpm' ),
                    'error' => __( 'An error occurred', 'ihumbak-wpm' ),
                    'success' => __( 'Changes saved successfully', 'ihumbak-wpm' ),
                    'confirmBulk' => __( 'Are you sure you want to update prices for all filtered products?', 'ihumbak-wpm' ),
                ),
            )
        );
    }

    /**
     * AJAX: Get products
     */
    public function ajax_get_products() {
        check_ajax_referer( 'ihumbak_wpm_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'ihumbak-wpm' ) ) );
        }

        $handler = new IHumbak_Products_Handler();
        $products = $handler->get_products( $_POST );

        wp_send_json_success( $products );
    }

    /**
     * AJAX: Update single product prices
     */
    public function ajax_update_prices() {
        check_ajax_referer( 'ihumbak_wpm_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'ihumbak-wpm' ) ) );
        }

        $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        $regular_price = isset( $_POST['regular_price'] ) ? sanitize_text_field( $_POST['regular_price'] ) : '';
        $sale_price = isset( $_POST['sale_price'] ) ? sanitize_text_field( $_POST['sale_price'] ) : '';

        $handler = new IHumbak_Products_Handler();
        $result = $handler->update_product_prices( $product_id, $regular_price, $sale_price );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Prices updated successfully', 'ihumbak-wpm' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to update prices', 'ihumbak-wpm' ) ) );
        }
    }

    /**
     * AJAX: Bulk update prices
     */
    public function ajax_bulk_update_prices() {
        check_ajax_referer( 'ihumbak_wpm_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'ihumbak-wpm' ) ) );
        }

        $filters = isset( $_POST['filters'] ) ? $_POST['filters'] : array();
        $change_type = isset( $_POST['change_type'] ) ? sanitize_text_field( $_POST['change_type'] ) : 'percentage';
        $change_value = isset( $_POST['change_value'] ) ? floatval( $_POST['change_value'] ) : 0;
        $price_type = isset( $_POST['price_type'] ) ? sanitize_text_field( $_POST['price_type'] ) : 'regular';

        $handler = new IHumbak_Products_Handler();
        $result = $handler->bulk_update_prices( $filters, $change_type, $change_value, $price_type );

        if ( $result ) {
            wp_send_json_success( array(
                'message' => sprintf( __( 'Updated %d products successfully', 'ihumbak-wpm' ), $result ),
                'count' => $result
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to update products', 'ihumbak-wpm' ) ) );
        }
    }

    /**
     * AJAX: Get filter options
     */
    public function ajax_get_filters() {
        check_ajax_referer( 'ihumbak_wpm_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'ihumbak-wpm' ) ) );
        }

        $handler = new IHumbak_Products_Handler();
        $filters = $handler->get_filter_options();

        wp_send_json_success( $filters );
    }
}

// Initialize plugin
function ihumbak_wpm_init() {
    return IHumbak_WooCommerce_Products_Manager::get_instance();
}

add_action( 'plugins_loaded', 'ihumbak_wpm_init' );
