<?php
/**
 * Plugin Name: Woo Custom Stock Status
 * Description: This plugin is used to write the custom stock status for woocommerce products, let users to know exact stock status names in product details page.
 * Author: Softound Solutions
 * Version: 1.0.0
 * Author URI: http://softound.com/
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class WooCustomStockStatus {

	/**
	 * Default default stock status and its names
	 */
	private $status_array = array(
							'in_stock' 				=> 'In stock',
							'only_s_left_in_stock' 	=> 'Only %s left in stock',
							'can_be_backordered' 	=> '(can be backordered)',
							's_in_stock'			=> '%s in stock',
							'available_on_backorder'=> 'Available on backorder',
							'out_of_stock' 			=> 'Out of stock',
						);
	
	public function __construct() {
		add_filter( 'woocommerce_settings_tabs_array', array( $this , 'add_settings_tab' ) , 50 );
		add_action( 'woocommerce_settings_tabs_wc_stock_list_rename',array( $this ,'settings_tab' ));
		add_action( 'woocommerce_update_options_wc_stock_list_rename',array( $this ,'update_settings' ));
		add_filter( 'woocommerce_get_availability', array( $this,'woo_rename_stock_status' ) , 10 , 2);
	}
	
	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
	 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs['wc_stock_list_rename'] = __( 'Custom Stock', 'woo-custom-stock-status' );
		return $settings_tabs;
	}
	
	/**
	 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses $this->get_settings()
	 */
	public function settings_tab() {
		woocommerce_admin_fields( $this->get_settings() );
	}
	
	
	/**
	 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
	 *
	 * @uses woocommerce_update_options()
	 * @uses $this->get_settings()
	 */
	public function update_settings() {
		woocommerce_update_options( $this->get_settings() );
	}
	
	/**
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields() function.
	 */
	public function get_settings() {
		
		$settings['section_title'] = array(
				'name'     => __( 'Custom Stock Status', 'woo-custom-stock-status' ),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'wc_wc_stock_list_rename_section_title'
			);
		
		foreach($this->status_array as $status=>$label){
			$settings[$status] =  array(
				'name' => __( $label, 'woo-custom-stock-status' ),
				'type' => 'text',
				'desc'     => '',
				'id'   => 'wc_slr_'.$status,
				'class' => 'large-text'
			);
		}
		
		$settings['section_end'] = array(
				 'type' => 'sectionend',
				 'id' => 'wc_wc_stock_list_rename_section_end'
			);
		return apply_filters( 'wc_wc_stock_list_rename_settings', $settings );
	}

	/*
	 * Rename the default stock list names based on admin settings
	 */
	public function woo_rename_stock_status( $message_array ,  $this_obj ) {
	
		$availability = $class = '';
		
		foreach($this->status_array as $status=>$label){
			$$status = $label;
		}
				
		foreach($this->status_array as $status=>$label){
			$$status = (get_option('wc_slr_'.$status,$$status)=='') ? $$status : get_option('wc_slr_'.$status,$$status);
		}
		
		if ( $this_obj->managing_stock() ) {

			if ( $this_obj->is_in_stock() && $this_obj->get_total_stock() > get_option( 'woocommerce_notify_no_stock_amount' ) ) {

				switch ( get_option( 'woocommerce_stock_format' ) ) {

					case 'no_amount' :
						$availability = __( $in_stock, 'woocommerce' );
					break;

					case 'low_amount' :
						if ( $this_obj->get_total_stock() <= get_option( 'woocommerce_notify_low_stock_amount' ) ) {
							$availability = sprintf( __( $only_s_left_in_stock, 'woocommerce' ), $this_obj->get_total_stock() );

							if ( $this_obj->backorders_allowed() && $this_obj->backorders_require_notification() ) {
								$availability .= ' ' . __( $can_be_backordered, 'woocommerce' );
							}
						} else {
							$availability = __( $in_stock, 'woocommerce' );
						}
					break;

					default :
						$availability = sprintf( __( $s_in_stock, 'woocommerce' ), $this_obj->get_total_stock() );

						if ( $this_obj->backorders_allowed() && $this_obj->backorders_require_notification() ) {
							$availability .= ' ' . __( $can_be_backordered, 'woocommerce' );
						}
					break;
				}

				$class        = 'in-stock';

			} elseif ( $this_obj->backorders_allowed() && $this_obj->backorders_require_notification() ) {

				$availability = __( $available_on_backorder, 'woocommerce' );
				$class        = 'available-on-backorder';

			} elseif ( $this_obj->backorders_allowed() ) {

				$availability = __( $in_stock, 'woocommerce' );
				$class        = 'in-stock';

			} else {

				$availability = __( $out_of_stock, 'woocommerce' );
				$class        = 'out-of-stock';
			}

		} elseif ( ! $this_obj->is_in_stock() ) {

			$availability = __( $out_of_stock, 'woocommerce' );
			$class        = 'out-of-stock';
		}
		
		$message_array['availability'] = $availability;
		$message_array['class'] = $class;
		return $message_array;
	}
}
$woo_custom_stock_status_obj = new WooCustomStockStatus();
?>