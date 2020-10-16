<?php
/*
Plugin Name: Woo Custom Stock Status
Plugin URI:  https://www.softound.com/
Description: Write the custom stock status with different colors for each woocommerce product, to show in product details and listing pages.
Version:     1.2.4
Author:      Softound Solutions
Author URI:  https://www.softound.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: woo-custom-stock-status
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
* Main Woocommerce Stock status class
*/
class WC_Custom_Stock_status {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Instance of base class
	 *
	 * @since 1.1.0
	 */
	public $stock_base = null;

	/**
	 * Instance of Setting class
	 *
	 * @since 1.1.0
	 */
	public $stock_setting = null;

	/**
	 * Instance of Product class
	 *
	 * @since 1.1.0
	 */
	public $stock_product = null;

	/**
	 * Main WC_Custom_Stock_status Instance.
	 *
	 * Ensures only one instance of WC_Custom_Stock_status is loaded or can be loaded.
	 *
	 * @since 1.1.0
	 * @static
	 * @return WC_Custom_Stock_status - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * WC_Custom_Stock_status Constructor.
	 */
	public function __construct() {
		$this->includes();
		// init other classes
		$this->stock_base = new Woo_Stock_Base();
		$this->stock_setting = new Woo_Stock_Setting();
		$this->stock_product = new Woo_Stock_Product();
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		require_once( 'includes/class-wc-stock-base.php' ); 
		require_once( 'includes/class-wc-stock-setting.php' ); 
		require_once( 'includes/class-wc-stock-product.php' ); 
	}

	public static function deactive_error_notice() {
	  printf('<div class="error notice is-dismissible"><p>%1$s</p></div>',__( 'Please install WooCommerce, it is required for this plugin to work properly!', 'wc-stock-status' ));
	}
}

/**
 * Check if WooCommerce is active
 **/
if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}
if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	deactivate_plugins( plugin_dir_path( __FILE__ ) . 'woo-custom-stock-status.php', false );
	add_action( 'admin_notices' , array( 'WC_Custom_Stock_status' , 'deactive_error_notice' ) );
} else {

	/**
	 * Main instance of Main Woocommerce Stock status.
	 * @since  1.1.0
	 */
	WC_Custom_Stock_status::instance();
}



?>
