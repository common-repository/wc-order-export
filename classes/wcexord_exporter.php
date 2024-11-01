<?php 
/**
 * @author 		: Saravana Kumar K, sarkparanjothi
 * @author url  : iamsark.com
 * @copyright	: sarkware.com
 * One of the core module, get allorder data from woocomerce
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class wcexord_exporter {
 	function __construct(){ 		
 		add_filter( "wc_order_export_data", array( $this, "wcexord_exporter_data" ), 1, 1 );
 	}	
 	
 	function wcexord_exporter_data( $payload ){
 	    global $wcexord;
 	    $start_date = isset( $payload[ "start_date" ] ) ? $payload[ "start_date" ] : "";
 	    $end_date 	=isset( $payload[ "end_date" ] ) ? $payload[ "end_date" ] : "";
 		$order_state_front = $payload[ "order_status" ];
 		$order_ids = $payload["wcexpo_orderid"];
 		//$order_exp_fields = $payload[ "ex_fields" ];
 		$date_start_exp = explode( "-", $start_date );
 		$date_end_exp 	= explode( "-", $end_date );
 		$order_state = $order_state_front == "wc-all" ? "any" : json_decode( preg_replace('/\\\"/',"\"", $order_state_front ) );
 		
 		if( is_array( $order_state ) ){
	 		if( sizeof( $order_state ) == 1 ){
	 			$order_state = $order_state[0];
	 		}
 		}
 		
 		$args = array(
 				'post_type' => 'shop_order',
 				'post_status' => $order_state,
 				'posts_per_page' => -1,
 		);
 		$is_ids_empty = empty( $order_ids );
 		if( !$is_ids_empty ){
 		    $order_ids = array_map('trim', explode( ",", $order_ids ) );
 		    $args['post__in'] = $order_ids;
 		} else {
 		    if( strlen( $start_date ) == 10 && strlen( $end_date ) == 10 ){
     		    $args['date_query'] = array(
     		        array(
     		            'after' =>  array(
     		                'year'  => $date_start_exp[2],
     		                'month' => $date_start_exp[1],
     		                'day'   => $date_start_exp[0],
     		            ),
     		            'before' => array(
     		                'year'  => $date_end_exp[2],
     		                'month' => $date_end_exp[1],
     		                'day'   => $date_end_exp[0],
     		            ),
     		            'inclusive' => true,
     		        ),
     		    );
 		    }
 		}
 		
 		$query = new WP_Query( $args );
 		$orders_list = $query->posts;
 		$orders_data = array();
 		// TO get order data ( billing address shipping address etc.. )
 		for ( $i = 0; $i < sizeof( $orders_list ); $i++ ){
 		    $order_meta = $this->wcexord_export_order_data( $orders_list[$i]->ID );
 		    $item_meta = $this->wcexord_export_order_meta_custom( $orders_list[$i]->ID );
 		    if( $wcexord->row_type == "by_order" ){
		        for( $t = 0; $t < count( $item_meta ); $t++ ){
		            foreach( $item_meta[$t] as $key => $val ){
 			            if( isset( $order_meta[$key] ) ){
 			                $order_meta[$key] .=  ", " . ( is_array( $val ) ?  implode( ",", $val ) : $val );
 			            } else {
 			                $order_meta[$key] = ( is_array( $val ) ?  implode( ",", $val ) : $val );
 			            }
 			        }
		        }
 			    $orders_data[] = $order_meta;
 			} else {
 			    for ( $n = 0; $n < count( $item_meta ); $n++ ){
 			        $orders_data[] = array_merge( $item_meta[$n], $order_meta ); 
 			    }
 			}
 		} 
 		return $orders_data;
 	}
 	
 	/*
 	 * get order meta for perticular orders
 	 */ 	
 	function wcexord_export_order_data( $order_id ){
 	    global $wcexord;
 		$order_meta = array();
 		$meta_array = $wcexord->headers;
 		foreach( $meta_array as $key => $val ){
 		    $meta = get_post_meta( $order_id, $key, true );
 			if( !empty( $meta ) ){
 			    $order_meta[ $key ] = $meta;
 			}
 		} 		
 		
 		$order_meta["ID"] = $order_id;
 		$order_meta["order_status"] = get_post_status( $order_id );
 		$order_meta["order_date"] = get_the_time( apply_filters( "wc_order_export_order_date_format", 'd-m-Y' ), $order_id );
 		
 		return apply_filters( "wc_order_export_order_data", $order_meta, $order_id ); 		
 	} 	
 	
 	/*
 	 * get order meta for perticular orders
 	 */
 	function wcexord_export_order_meta_custom( $order_id ){
 		global $wpdb;
 		$order_item_data = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."woocommerce_order_items WHERE order_id = '".$order_id."' AND order_item_type = 'line_item' " ); 
 		$datas = array();
 		for( $j = 0; $j < count( $order_item_data ); $j++ ){
 		    $custom_meta_data_fields = array();
 		    $order_item_id = $order_item_data[$j]->order_item_id;
 		    $order_item_meta_data = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."woocommerce_order_itemmeta WHERE order_item_id = '".$order_item_id."'" );
 		    for( $i = 0; $i < sizeof( $order_item_meta_data ); $i++ ){
 		        $meta_key = $order_item_meta_data[$i]->meta_key;
 		        $custom_meta_data_fields[ $meta_key ] = $order_item_meta_data[$i]->meta_value;
 		    }
 		    
 		    if( isset( $custom_meta_data_fields["_product_id"] ) ){
 		        $product = wc_get_product( $custom_meta_data_fields["_product_id"] );
 		        
 		        $custom_meta_data_fields["product_type"] = $product->get_type();
 		        $custom_meta_data_fields["product_name"] = $product->get_name();
 		        $custom_meta_data_fields["product_slug"] = $product->get_slug();
 		        $custom_meta_data_fields["product_created_date"] = $product->get_date_created();
 		        $custom_meta_data_fields["product_status"] = $product->get_status();
 		        $custom_meta_data_fields["product_description"] = $product->get_description();
 		        $custom_meta_data_fields["product_short_description"] = $product->get_short_description();
 		        $custom_meta_data_fields["product_sku"] = $product->get_sku();
 		        
 		        $custom_meta_data_fields["product_price"] = $product->get_price();
 		        $custom_meta_data_fields["product_reqular_price"] = $product->get_regular_price();
 		        $custom_meta_data_fields["product_sale_price"] = $product->get_sale_price();
 		        $custom_meta_data_fields["product_total_sale"] = $product->get_total_sales();
 		        $custom_meta_data_fields["product_stack_qty"] = $product->get_stock_quantity();
 		        $custom_meta_data_fields["product_stack_status"] = $product->get_stock_status();
 		        
 		        $custom_meta_data_fields["product_categories"] = wc_get_product_category_list( $custom_meta_data_fields["_product_id"] );
 		        $custom_meta_data_fields["products_categorie_ids"] = $product->get_category_ids();
 		        
 		        get_the_post_thumbnail_url( $product->get_id(), 'full' );
 		    }
 		    
 		    $datas[] = $custom_meta_data_fields;
 		}
 		return apply_filters( "wc_order_export_orderm_item_data", $datas, $order_id );
 	}
 	
 	
 	
 }
 
 new wcexord_exporter();


?>