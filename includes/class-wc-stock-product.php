<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
* WC stock status for Products if product stock status are empty they get global stock status ( Setting tab Status )
*/

class Woo_Stock_Product extends Woo_Stock_Base {
	
	public function __construct() {
		
		// add stock status tab to product tab
		add_filter( 'woocommerce_product_data_tabs', array( $this , 'woo_add_simple_product_stock_status' ) );

		// display stock status fields for ( Simple,Grouped,External ) Products
		add_action( 'woocommerce_product_data_panels' , array( $this , 'woo_stock_status_fields' ) );

		// save stock fields value for ( Simple ) Product
		add_action( 'woocommerce_process_product_meta_simple' , array( $this , 'save_stock_status_message' ) );

		// add stock status message in content-product template page
		add_action( 'woocommerce_after_shop_loop_item_title' , array( $this , 'add_stack_status_in_summary' ) , 15 ); // after price woocommerce\templates\content-product.php line:60

		/**
		 * Hide save stock fields value for Grouped,External Products
		 */
		
		// add_action( 'woocommerce_process_product_meta_grouped' , array( $this , 'save_stock_status_message' ) );
		// add_action( 'woocommerce_process_product_meta_external' , array( $this , 'save_stock_status_message' ) );

		// variration stock status field
		add_action( 'woocommerce_variation_options_inventory' , array( $this , 'woo_variation_stock_status_field' ) , 10 , 3 ); 

		//save variation stock status
		add_action( 'woocommerce_save_product_variation' , array( $this , 'save_variation_stock_status' ) , 10 , 2 );

		//backorder woo custom stock status in order confirmation
		add_action('woocommerce_order_item_meta_start',array($this,'add_stock_status_in_order_confirmation'),10,3);
	}

	public function woo_add_simple_product_stock_status( $tabs ) {
		$tabs['stockstatus'] = array(
										'label'  => __( 'Stock Status', 'woo-custom-stock-status' ),
										'target' => 'custom_stock_status_data',
										'class'  => array( 'show_if_simple' ), // depend upon product type to show & hide
									);

		return $tabs;
	}

	public function woo_stock_status_fields() {
		echo '<div id="custom_stock_status_data" class="panel woocommerce_options_panel">';
		foreach ($this->status_array as $key => $value) {
			woocommerce_wp_text_input(
										array( 
												'id' => $key, 
												'label' => __( $value , 'woo-custom-stock-status' ),
												'placeholder' => $value 
											)
									);
		}
		echo '</div>';
	}

	public function save_stock_status_message( $post_id ) {
		foreach ($this->status_array as $meta_key => $val) {
			if(isset( $_POST[$meta_key] ) && !empty( $_POST[$meta_key] ) ) {
				update_post_meta( $post_id , $meta_key , sanitize_text_field( $_POST[$meta_key] ) );
			} else {
				delete_post_meta( $post_id, $meta_key );
			}
		}
	}

	public function woo_variation_stock_status_field( $loop, $variation_data, $variation ) {
		$right_side = array('in_stock','can_be_backordered','available_on_backorder');
		echo '<div style="clear:both"></div><p style="font-size:14px;"><b>'.__( 'Custom Stock Status' , 'woo-custom-stock-status' ).'</b></p>';
		foreach ($this->status_array as $key => $name) { ?>
			<p class="form-row <?php echo in_array( $key,$right_side ) ? 'form-row-first' : 'form-row-last' ?>">
				<label><?php _e( $name , 'woo-custom-stock-status' ); ?></label>
				<input type="text" placeholder="<?php echo $name; ?>" name="variable_<?php echo $key; ?>_status[<?php echo $loop; ?>]" value="<?php echo get_post_meta( $variation->ID , '_'.$key.'_status' , true ); ?>" />
			</p>
		<?php
		}
	}

	public function save_variation_stock_status( $post_id , $variation_key ) {
		foreach ($this->status_array as $meta_key => $val) {
			if(isset( $_POST['variable_'.$meta_key.'_status'][$variation_key] ) && !empty( $_POST['variable_'.$meta_key.'_status'][$variation_key] ) ) {
				update_post_meta( $post_id , '_'.$meta_key.'_status' , sanitize_text_field( $_POST['variable_'.$meta_key.'_status'][$variation_key] ) );
			} else {
				delete_post_meta( $post_id, '_'.$meta_key.'_status' );
			}
		}
	}
	
	/**
	 * Show stock status in product listing page
	 */
	public function add_stack_status_in_summary(){
		$show_status = get_option( 'wc_slr_show_in_shop_page' , 'yes' );
		if( $show_status === 'yes' ) {
			global $product;
			$availability      = $product->get_availability();
			$availability_html = empty( $availability['availability'] ) ? '' : '<p class="stock ' . esc_attr( $availability['class'] ) . '">' . __(esc_html( $availability['availability'] ),'woo-custom-stock-status') . '</p>';
			echo $availability_html;
		}
	}

	/**
	* Woo custom stock status in order confirmation (for backorders) (Improved)
	*/
	public function add_stock_status_in_order_confirmation( $item_id , $item , $order  ) {	
		$product_id 			= 	$item->get_product_id();
		$product 				= 	new WC_Product( $product_id );
		$order_items     		=	$order->get_items();
		$on_backorder 			= 	false;
		$order_id 				=	$order->get_id();
		$product_availabilty 	= 	$product->get_availability();
		foreach(  $order_items as $items_ ) {
			if(	$items_['Backordered']	) {
				$on_backorder = true;
			}
		}		
		if( ( $on_backorder === true ) && ( $product_id > 0 ) ) {
			$woo_custom_stock_status = $product_availabilty['availability'];
			$custom_message		 =  serialize(array(
				'class'   => esc_html($product_availabilty['class']),
				'status'  => $woo_custom_stock_status
			));

			$backorder_message       = get_post_meta($order_id,'woo_custom_stock_status_backorder_status_'.$item_id,true);
			if( ($backorder_message == '') || ( is_null($backorder_message) ) || (empty($backorder_message)) ) {
				update_post_meta($order_id, 'woo_custom_stock_status_backorder_status_'.$item_id, $custom_message);
			}
			$custom_message       = unserialize(get_post_meta($order_id,'woo_custom_stock_status_backorder_status_'.$item_id,true));

			echo wp_kses_post( '<p class="stock '.esc_html( $custom_message['class'] ) .'">'.__($custom_message['status'],'woo-custom-stock-status').'</p>' );
		}
	}


}
