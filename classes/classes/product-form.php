<?php 
/**
 * @author 		: Saravana Kumar K
 * @copyright	: sarkware.com
 * @todo		: One of the core module, which renders the actual wccpf fields to the product page.
 * 
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class wccpf_product_form {
	
	function __construct() {
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'inject_wccpf' ) );
		add_action( 'woocommerce_add_to_cart_validation', array( $this, 'validate_wccpf' ), 1, 3 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'force_wccpf_unique' ), 10, 2 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'save_wccpf' ), 1, 5 );
		add_filter( 'woocommerce_cart_item_name', array( $this, 'render_wccpf_on_cart' ), 1, 3 );
		add_filter( 'woocommerce_checkout_cart_item_quantity', array( $this, 'render_wccpf_on_checkout' ), 1, 3 );
		add_action( 'woocommerce_add_order_item_meta', array( $this, 'save_wccpf_order_meta' ), 1, 3 );
	}
	
	function inject_wccpf() {
		Global $product;
		$all_fields = apply_filters( 'wccpf/load/all_fields', $product->id );
		
		foreach ( $all_fields as $fields ) {
			foreach ( $fields as $key => $field ) {
				/* generate html for wccpf fields */
				$html = apply_filters( 'wccpf/render_product_field/type='.$field["type"], $field );
				/* Allow third party apps logic to render wccpf fields with their own wish */
				if( has_filter( 'wccpf/before/fields/rendering' ) ) {
					$html = apply_filters( 'wccpf/before/fields/rendering', $field, $html );
				}
				echo $html;
			}
		}

		$this->wccpf_front_end_enqueue_scripts();
	}
	
	/**
	 * 
	 * @param 	BOOLEAN	 $unknown
	 * @param 	INT		 $pid
	 * @param 	INT		 $quantity
	 * @todo	There is an unsolved issue, when grouped products are validated ( There won't be $pid ).
	 */
	function validate_wccpf( $unknown, $pid = null, $quantity ) {
		if( isset( $pid ) ) {
			$is_error = true;
			$all_fields = apply_filters( 'wccpf/load/all_fields', $pid );
			foreach ( $all_fields as $fields ) {
				foreach ( $fields as $field ) {
					$val = $_REQUEST[ $field["name"] ];
					if( $field["required"] == "yes" ) {
						error_log("Fields Type : ". $field["type"].", Fields Val : ".$val);
						$res = apply_filters( 'wccpf/validate/type='.$field["type"], $val );
						error_log("Validation Status : ".$res);
						if( !$res ) {
							wc_add_notice( $field["message"], 'error' );
							$is_error = false;
						}
					}
				}
			}
			return $is_error;
		} else {
			return true;
		}
	}
	
	function force_wccpf_unique( $cart_item_data, $product_id ) {
		$unique_cart_item_key = md5( microtime().rand() );
		$cart_item_data['wccpf_unique_key'] = $unique_cart_item_key;		
		return $cart_item_data;
	}
	
	function save_wccpf( $cart_item_key, $product_id = null, $quantity= null, $variation_id= null, $variation= null ) {
		if( $product_id ) {
			$all_fields = apply_filters( 'wccpf/load/all_fields', $product_id );
			foreach ( $all_fields as $fields ) {
				foreach ( $fields as $field ) {
					if( $field["type"] != "checkbox" ) {
						WC()->session->set( $cart_item_key.$field["name"], $_REQUEST[ $field["name"] ] );
					} else {						
						WC()->session->set( $cart_item_key.$field["name"], implode( ", ", $_REQUEST[ $field["name"] ] ) );									
					}
				}
			}
		}
	}
	
	function render_wccpf_on_cart( $title = null, $cart_item = null, $cart_item_key = null ) {
		if( isset( $cart_item['product_id'] ) && $cart_item_key && is_cart() ) {
			$wccpf_items = "";
			$all_fields = apply_filters( 'wccpf/load/all_fields', $cart_item['product_id'] );
			foreach ( $all_fields as $fields ) {
				foreach ( $fields as $field ) {
					$wccpf_items .= '<dl class="">
						 <dt class="">'. $field["label"] .' : </dt>
						 <dd class=""><p>'. WC()->session->get( $cart_item_key.$field["name"] ) .'</p></dd>
					  </dl>';
				}
			}
			echo $title.$wccpf_items;
		} else {
			echo $title;
		}
	}
	
	function render_wccpf_on_checkout( $quantity = null, $cart_item = null, $cart_item_key = null ) {
		if( isset( $cart_item['product_id'] ) && $cart_item_key && $quantity ) {
			$wccpf_items = "";
			$all_fields = apply_filters( 'wccpf/load/all_fields', $cart_item['product_id'] );
			foreach ( $all_fields as $fields ) {
				foreach ( $fields as $field ) {
					$wccpf_items .= '<dl class="">
						 <dt class="">'. $field["label"] .' : </dt>
						 <dd class=""><p>'. WC()->session->get( $cart_item_key.$field["name"] ) .'</p></dd>
					  </dl>';
				}
			}
			echo $quantity.$wccpf_items;
		} else {
			echo $quantity;
		}
	}
	
	function save_wccpf_order_meta( $item_id, $values, $cart_item_key ) {
		if( isset($values["product_id"] ) ) {
			$all_fields = apply_filters( 'wccpf/load/all_fields', $values["product_id"] );
			foreach ( $all_fields as $fields ) {
				foreach ( $fields as $field ) {
					wc_add_order_item_meta( $item_id, $field["label"], WC()->session->get( $cart_item_key.$field["name"] ) );
				}
			}
		}			
	}
	
	function wccpf_front_end_enqueue_scripts() {
		if( is_shop() || is_product() ) {
			wp_register_style( 'wccpf-font-end-style', wccpf()->settings['dir'] . 'css/wccpf-front-end.css' );
			wp_enqueue_style( array( 'wccpf-font-end-style' ) );
		}
	}
} 

new wccpf_product_form();

?>