<?php
/**
 * Plugin Name: Uptimex Custom attributes for WooCommerce
 * Description: Add custom fields to WooCommerce products
 * Version: 1.0.0
 * Author: Aram Khachikyan
 * Author URI: https://aramkhachikyan.net
*/
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

class wsrc_custom_attributes_woocommerce {
	
	public function __construct() {
		$this->init();
	}
	
	public function init() {
		add_action( 'woocommerce_product_options_general_product_data', array($this, 'wsrc_create_custom_field') );		
		add_action( 'woocommerce_process_product_meta', array($this, 'wsrc_save_custom_field') );
		add_action( 'woocommerce_after_add_to_cart_button', array($this, 'wsrc_display_custom_field') );		
		// add_filter( 'woocommerce_add_to_cart_validation', array($this, 'wsrc_validate_custom_field', 10, 3 );
		add_filter( 'woocommerce_add_cart_item_data', array($this, 'wsrc_add_custom_field_item_data'), 10, 4 );
		add_action( 'woocommerce_before_calculate_totals', array($this, 'wsrc_before_calculate_totals'), 10, 1 );
		add_filter( 'woocommerce_cart_item_name',array($this,  'wsrc_cart_item_name'), 10, 3 );		
		add_action( 'woocommerce_checkout_create_order_line_item', array($this, 'wsrc_add_custom_data_to_order'), 10, 4 );
		add_action( 'wp_enqueue_scripts', array($this, 'init_plugin_wsrc_custom'));	
		add_action( 'wp_ajax_wsrc_custom_attributes_ajax_retrieve', array($this, 'wsrc_custom_attributes_ajax_retrieve'));
		add_action( 'wp_ajax_nopriv_wsrc_custom_attributes_ajax_retrieve', array($this, 'wsrc_custom_attributes_ajax_retrieve'));
	}
	
	/**
	 * Display the custom text field
	 * @since 1.0.0
	 */
	public function wsrc_create_custom_field() {
		$args = array(
			'id' => 'custom_atts_field_content',
			'label' => __( 'Custom attributes', 'wsrc' ),
			'class' => 'wsrc-custom-field',
			'desc_tip' => false,
			'placeholder' => '50g Heinz ketchup-ketchup-500',
			'description' => __( 'Format: [Label-id-price] - 50g Heinz ketchup-ketchup-500', 'ctwc' ),
		);
		
		woocommerce_wp_textarea_input( $args );
	}

	/**
	* Save the custom field
	* @since 1.0.0
	*/
	public function wsrc_save_custom_field( $post_id ) {
		$product = wc_get_product( $post_id );
		$title = isset( $_POST['custom_atts_field_content'] ) ? $_POST['custom_atts_field_content'] : '';
		$product->update_meta_data( 'custom_atts_field_content', sanitize_text_field( $title ) );
		$product->save();
	}

	/**
	 * Display custom field on the front end
	 * @since 1.0.0
	 */
	public function wsrc_display_custom_field() {
		global $post;
		
		$product = wc_get_product( $post->ID );
		$title = $product->get_meta( 'custom_atts_field_content' );

		$boxes = explode('%',$title);
	 
		if( $title ) {

			foreach( $boxes as $box) {
				$box_name_price = explode('-',$box);
				
				echo '<div class="wsrc-custom-field-wrapper"><input class="wsrcCustomAttsCheck" data-product-id="'.$post->ID.'" data-price="'.$box_name_price[2].'" type="checkbox" id="wsrc-custom-atts-field-'.$box_name_price[1].'" name="wsrc-custom-atts-field[]" value="'.$box_name_price[1].'"><label for="wsrc-custom-atts-field-'.$box_name_price[1].'">'.$box_name_price[0].'</label></div>';
			}
		}
	}

	/**
	 * Validate the text field
	 * @since 1.0.0
	 * @param Array 		$passed					Validation status.
	 * @param Integer   $product_id     Product ID.
	 * @param Boolean  	$quantity   		Quantity
	 */
	public function wsrc_validate_custom_field( $passed, $product_id, $quantity ) {
		if( empty( $_POST['wsrc-custom-atts-field'] ) ) {
			// Fails validation
			$passed = false;
			wc_add_notice( __( 'Please enter a value into the text field', 'wsrc' ), 'error' );
		}
		return $passed;
	}
	
	/**
	 * Add the text field as item data to the cart object
	 * @since 1.0.0
	 * @param Array 		$cart_item_data Cart item meta data.
	 * @param Integer   $product_id     Product ID.
	 * @param Integer   $variation_id   Variation ID.
	 * @param Boolean  	$quantity   		Quantity
	 */
	public function wsrc_add_custom_field_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {
	$product = wc_get_product( $product_id );
	$price = $product->get_price(); // Expanded function


	$title = $product->get_meta( 'custom_atts_field_content' );

	$boxes = explode('%',$title);

	 if( ! empty( $_POST['wsrc-custom-atts-field'] ) ) {
		 
		foreach($boxes as $box) {
			
			$single_box = explode('-',$box);
			
			foreach($_POST['wsrc-custom-atts-field'] as $single_additional) {			
				if($single_box[1] == $single_additional) {
					$cart_item_title_arr[] = $single_box[0];
					$cart_item_data['custom_atts_field'] .= $single_box[0];
					$price += $single_box[2];
				}
			}
		}	
		$cart_item_data['custom_atts_field'] = implode(', ', $cart_item_title_arr);
		$cart_item_data['total_price'] = $price; 

	 }
	 return $cart_item_data;
	}	
	
	
	/**
	 * Update the price in the cart
	 * @since 1.0.0
	 */
	public function wsrc_before_calculate_totals( $cart_obj ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		// Iterate through each cart item
		foreach( $cart_obj->get_cart() as $key=>$value ) {
			if( isset( $value['total_price'] ) ) {
				$price = $value['total_price'];
				$value['data']->set_price( ( $price ) );
			}
		}
	}

	/**
	 * Display the custom field value in the cart
	 * @since 1.0.0
	 */
	public function wsrc_cart_item_name( $name, $cart_item, $cart_item_key ) {

		if( isset( $cart_item['custom_atts_field'] ) ) {
			$name .= sprintf(
			'<p>%s</p>',
			esc_html( $cart_item['custom_atts_field'] )
			);
		}
		return $name;
	}
	
	/**
	* Add custom field to order object
	*/
	public function wsrc_add_custom_data_to_order( $item, $cart_item_key, $values, $order ) {

		foreach( $item as $cart_item_key=>$values ) {
			if( isset( $values['custom_atts_field'] ) ) {
				$item->add_meta_data( __( 'Custom Field', 'wsrc' ), $values['custom_atts_field'], true );
			}
		}
	}
	
	// AJAX
	public function init_plugin_wsrc_custom()
	{
		// wp_enqueue_style( 'wsrc-custom-attributes-style', plugins_url( '/style.css',__FILE__ ) );
		wp_enqueue_script( 
			'wsrc-custom-attributes-script', 
			plugins_url( '/scripts.js',__FILE__ ), 
			array('jquery'), 
			TRUE 
		);
		
		wp_localize_script( 
			'wsrc-custom-attributes-script', 
			'wsrc_custom_attributes_ajax', 
			array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( "wsrc_custom_attributes_ajax_retrieve_nonce" ),
			)
		);
	}


	public function wsrc_custom_attributes_ajax_retrieve()
	{
		check_ajax_referer( 'wsrc_custom_attributes_ajax_retrieve_nonce', 'nonce' );

		$product = wc_get_product( $_POST['product_id']);
		
		$total = $_POST['checked_sum']+$product->get_price();
		
		if( true )
			wp_send_json_success( wc_price($total) );
		else
			wp_send_json_error( array( 'error' => 'Error' ) );
	}
	
}

new wsrc_custom_attributes_woocommerce;


	
