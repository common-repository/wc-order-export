<?php
/*
Plugin Name: WC Order Export
Plugin URI: http://sarkware.com
Description: It allows you export woocomerce orders with custom fields ( order product meta ) to xml, csv, json.
Version: 1.5.0
Author: sarkparanjothi
Author URI: http://www.iamsark.com/
License: GPL
Copyright: sarkware.com
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists( 'export_orders' ) ):

class wcexord_export_orders {
	
    var $info, $fields, $line_fields, $add_to_order, $row_type, $headers, $file_type, $file_name;
	
	public function __construct() {
		$this->info = array(
			'path'				=> plugin_dir_path( __FILE__ ),
			'dir'				=> plugin_dir_url( __FILE__ ),
		    'basename'          => plugin_basename(__FILE__),
			'version'			=> '1.5.0'
		);
		
		$this->fields = array(
		    "ID",
		    "order_date",
		    "order_status",
		    "_billing_first_name",
		    "_billing_last_name",
		    "_billing_company",
		    "_billing_address_1",
		    "_billing_address_2",
		    "_billing_city",
		    "_billing_state",
		    "_billing_postcode",
		    "_billing_country",
		    "_billing_email",
		    "_billing_phone",
		    "_shipping_first_name",
		    "_shipping_last_name",
		    "_shipping_company",
		    "_shipping_address_1",
		    "_shipping_address_2",
		    "_shipping_city",
		    "_shipping_state",
		    "_shipping_postcode",
		    "_shipping_country",
		    "_order_currency",
		    "_cart_discount",
		    "_cart_discount_tax",
		    "_order_shipping",
		    "_order_shipping_tax",
		    "_order_tax",
		    "_order_total",
		    "_billing_address_index",
		    "_shipping_address_index",
		    "_payment_method",
		    "_payment_method_title",
		);
		
		$this->line_fields = array(
		    "_product_id",
		    "_variation_id",
		    "_qty",
		    "_tax_class",
		    "_line_subtotal",
		    "_line_subtotal_tax",
		    "_line_total",
		    "_line_tax",
		    "product_type",
		    "product_name",
		    "product_slug",
		    "product_created_date",
		    "product_status",
		    "product_description",
		    "product_short_description",
		    "product_sku",
		    "product_price",
		    "product_reqular_price",
		    "product_sale_price",
		    "product_total_sale",
		    "product_stack_qty",
		    "product_stack_status",
		    "product_categories",
		    "products_categorie_ids"
		);
		$this->add_field_factory_field();
		add_filter( 'plugin_action_links_' . $this->info["basename"], array( $this, 'wcexord_plugin_setting' ) );
		add_action( 'admin_notices', array( $this,'wcexord_ask_rating') );
		add_action( 'init', array( $this, 'wcexord_init' ), 1 );
		
		$this->wcexord_includes();		
		
		add_action( 'admin_menu', array( $this, 'wcexord_admin_menu' ), 30 );
		add_action( 'admin_head', array( $this, 'wcexord_exporter_ajax_url' ) );
		/////
		
		
		
		
		
	}
	
	function wcexord_admin_menu() {
	    add_submenu_page( 'woocommerce', 'Order Export Page', 'Order Export', 'manage_options', 'wcexord-order-export-page', array( $this, "wcexord_main_view" ) );			
	}
	
	function wcexord_main_view(){
	    include_once $this->info["path"] . "views/ecexord_main_page.php";
	}
	
	function wcexord_exporter_ajax_url(){
		echo '<script type="text/javascript">var wcexord_ajax_url = "'.admin_url( 'admin-ajax.php' ).'";</script>';
	}
	
	private function add_field_factory_field(){
	    $f_type             = get_option( 'wcexord_file_type', "csv" );
	    $this->add_to_order = get_option( 'wcexord_add_to_order_mail', "no" ) == "no" ? false : true;
	    $this->file_type    = $f_type == "xlsx" ? "xlsx" : ( $f_type == "csv" ? "csv" : "json" );
	    $this->row_type     = get_option( 'wcexord_row_type', "by_order" ) == "by_order" ? "by_order" : "by_order_item";
	    $this->headers      = json_decode( get_option( 'wcexord_fields', "[]" ), true );
	    $this->file_name    = get_option( 'wcexord_file_name', "wc-order-export" );
	    if( function_exists( "wcff" ) ){
	        global $wpdb;
	        $wcff_fields = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."postmeta WHERE meta_key like 'wccpf%' or meta_key like 'wccaf%'" ); 
	        $custom_meta = array();
	        
	        for( $i = 0; $i < count( $wcff_fields ); $i++ ){
	            $key = $wcff_fields[$i]->meta_key;
    	        if ( $key != 'wccaf_condition_rules' &&
    	            $key  != 'wccaf_location_rules' &&
    	            $key  != 'wccaf_group_rules' &&
    	            $key  != 'wccaf_pricing_rules' &&
    	            $key  != 'wccaf_fee_rules' &&
    	            $key  != 'wccaf_field_rules' &&
    	            $key  != 'wccaf_sub_fields_group_rules' &&
    	            $key  != 'wccaf_field_location_on_product' &&
    	            $key  != 'wccaf_field_location_on_archive' &&
    	            $key  != 'wccpf_condition_rules' &&
    	            $key  != 'wccpf_location_rules' &&
    	            $key  != 'wccpf_group_rules' &&
    	            $key  != 'wccpf_pricing_rules' &&
    	            $key  != 'wccpf_fee_rules' &&
    	            $key  != 'wccpf_field_rules' &&
    	            $key  != 'wccpf_sub_fields_group_rules' &&
    	            $key  != 'wccpf_field_location_on_product' &&
    	            $key  != 'wccpf_field_location_on_archive') {
    	              $field_meta =  json_decode( $wcff_fields[$i]->meta_value, true );
    	              if( isset( $field_meta["label"] ) ){
    	                  $custom_meta[] = $field_meta["label"];
    	              }
    	            }
    	        }
	    }
	    $this->fields = array_merge( $this->fields, $custom_meta, $this->line_fields );
	}
	
	function wcexord_init() {
		if( is_admin() ) {	
		    if( get_option( 'wcexord_fields', null ) == null ){
		        update_option('wcexord_fields', json_encode( array(
		                "ID" => "Order Id",
                        "_billing_first_name" => "First Name",
                        "_billing_last_name" => "Last Name",
                        "_billing_phone" => "Phone",
                        "_billing_email" => "Email",
                        "_billing_address_1" => "Address Line 1",
		                "_billing_address_2" => "Address Line 2",
                        "_billing_postcode" => "Post Code",
                        "_billing_city" => "City",
                        "_shipping_last_name" => "Shpiing Last Name",
		                "_shipping_address_1"  => "Shpping Address",
                        "_shipping_postcode" => "Shipping Post Code",
                        "_shipping_city" => "Shipping City",
                        "post_date" => "Order Date",
                        "_payment_method_title" => "Payment method"
                    ) ) );
		    }
			add_action( 'admin_enqueue_scripts',  array( $this, 'wcexord_order_export_script' ) );
		}		
	}
	
	function wcexord_order_export_script(){	
	    global $wcexord;
	    $screen = get_current_screen();
	    if( is_object( $screen ) && isset( $screen->id ) && $screen->id == "woocommerce_page_wcexord-order-export-page" ){
	        wp_enqueue_style( 'wcexord-order-export-style', $wcexord->info['dir']. '/assets/css/exporter-style.css?ver?=' . $this->info["version"] );		
	        wp_enqueue_style( 'wcexord-jquery-date-picker-style', $wcexord->info['dir']. '/assets/css/jquery-ui.css?ver?=' . $this->info["version"] );
    		wp_enqueue_script( 'jquery' );
    		wp_enqueue_script( 'jquery-ui-datepicker' );
    		wp_enqueue_script( 'jquery-ui-sortable' );
    		wp_enqueue_script( 'wcexord-order-export-script',$wcexord->info['dir']. "assets/js/wc-order-export.js?ver?=" . $this->info["version"] );
	    }
	}
	
	/*
	 * To include class files foe retrive data and form file
	 */
	public function wcexord_includes() {	
		include_once( 'classes/wcexord_exporter.php' );
		include_once( 'classes/wcexord_order-genarate.php' );
	}
	
	public function wcexord_plugin_setting( $links ) {
	   $wcexord_links = array(
	        'export' => '<a href="' . admin_url( 'admin.php?page=wcexord-order-export-page' ) . '" aria-label="' . esc_attr__( 'Export', 'wc-fields-factory' ) . '">' . esc_html__( 'Export', 'wc-fields-factory' ) . '</a>',
	    );
	   return array_merge( $wcexord_links, $links );
	}
	
	public function  wcexord_ask_rating() {
	    
	    if( get_current_screen()->id == "woocommerce_page_wcexord-order-export-page" ):
	    ?>
            <div data-dismissible="disable-done-notice-forever" class="notice notice-success is-dismissible">
                <p><?php _e( 'Please rate and review wc Order Export to <a href="https://wordpress.org/support/plugin/wc-order-export/reviews/?rate=5#new-post" target="_blank">click</a>', 'wc-fields-factory' ); ?></p>
            </div>
            <?php
       endif;
    }
	
}

function wcexord_order_export() {
	global $wcexord;
	if ( !function_exists( 'WC' ) ) {
		add_action( 'admin_notices', 'wcexord_order_export_woocommerce_not_found_notice' );
		return;
	}	
	if( !isset( $wcexord ) && !isset( $wcexord->info ) ) {
	    $wcexord = new wcexord_export_orders();
	}	
	
	return $wcexord;
	
}

add_action( 'plugins_loaded', 'wcexord_order_export', 11 );

if( !function_exists( 'wcexord_order_export_woocommerce_not_found_notice' ) ) {
	function wcexord_order_export_woocommerce_not_found_notice() {
		?>
        <div class="error">
            <p><?php _e( 'WC order export requires WooCommerce, Please make sure it is installed and activated.', 'wc-order-export' ); ?></p>
        </div>
    <?php
    }
}

endif;

?>