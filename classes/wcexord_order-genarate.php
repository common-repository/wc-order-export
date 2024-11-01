<?php 
/**
 * @author 		: Saravana Kumar K, sarkparanjothi
 * @author url  : iamsark.com
 * @copyright	: sarkware.com
 * One of the core module, of genarate order file
 *
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if( !class_exists( 'PHPExcel' ) ){
	require_once( 'PHPExcel.php' );
}

class wcexord_genarate_export_order {
	
	var $row_count;
	
	function __construct(){
	    global $wcexord;
		$this->row_count = 2;
		add_action( 'wp_ajax_wcexord_exporter', array( $this, 'wcexord_trigger_call' ) );
		add_action( 'wp_ajax_nopriv_wcexord_exporter', array( $this, 'wcexord_trigger_call' ));
		add_filter( 'export_genarate_excel', array( $this, 'wcexord_genarate_file_data' ) );
		add_filter( 'export_get_order_status', array( $this, 'wcexord_get_order_status' ) );
		
		$add_to_order = get_option( 'wcexord_add_to_order_mail', "no" ) == "no" ? false : true;
		if( $add_to_order ){
		    add_filter('woocommerce_email_attachments', array( $this, 'add_attachment' ), 10, 3 );
		}
	}
	
	// Add to email attachment
	public function add_attachment( $attachments, $id, $order ){
	    $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
	    if($id == 'new_order') {
	        $order_detailsAttachment = $this->wcexord_genarate_file_data( array( "wcexpo_orderid" => $order_id, "order_status" => "wc-all" ), true );
	        $attachments[] = $order_detailsAttachment;
	    }
	    
	    return $attachments;
	}
	
	/*
	 * Commaon ajax request farmetter
	 */
	function wcexord_trigger_call(){
		if( isset( $_POST["action"] ) ){
			if( isset( $_POST[ "payload" ] ) && filter_var( $_POST['action'], FILTER_SANITIZE_STRING ) ){					
				$payload = $_POST[ "payload" ];
				$request = array();
				$request[ "action" ]  = $payload[ "action" ];
				$request[ "payload" ] = $payload;
				if( filter_var( $request[ "payload" ][ "action" ], FILTER_SANITIZE_STRING ) && filter_var( json_encode( $request ), FILTER_UNSAFE_RAW ) ){
					$this->wcexord_call_devider( $request );
				}
			}
		}
	}
	
	
	/*
	 * Commaon ajax controller
	 */
	function wcexord_call_devider( $request ){
		if( isset( $request ) ){
			if( $request[ "action" ] == "export_order" ) {				
			    if( ( isset( $request["payload"][ "end_date" ] ) && isset(  $request["payload"][ "start_date" ] ) ) || ( isset( $request["payload"][ "wcexpo_orderid" ] ) && trim( $request["payload"][ "wcexpo_orderid" ] ) != "" ) ){
					$res = apply_filters( 'export_genarate_excel', $request[ "payload" ] );	
				} else {
					$res = array( "status" => false, "data" => "something wrong please try again" );
				}
			} else if( $request[ "action" ] == "get_order_status" ){
				$res = apply_filters( 'export_get_order_status', $request[ "payload" ] );
			} else if( isset( $request["payload"] )  && $request["action"] == "wcexpordercsvheader"  && isset( $request["payload"]['data'] ) ){
			    $datas = $request["payload"]['data'];
			    $add_to_order = $request["payload"]['add_to_order'];
			    $row_type = $request["payload"]['row_type'];
			    $file_type = $request["payload"]['file_type'];
			    $file_name = $request["payload"]['file_name'];
			    if( count( $datas ) != 0 ){
			        update_option( 'wcexord_fields', json_encode( $datas ) );
			    }
			    
			    update_option( 'wcexord_add_to_order_mail', $add_to_order );
			    update_option( 'wcexord_row_type', $row_type );
			    update_option( 'wcexord_file_type', $file_type );
			    update_option( 'wcexord_file_name', $file_name );
			    
			    echo json_encode( array( "status" => true ) );
			    die();
			}
			echo json_encode( $res );
			die();
		}
	}
	
	function wcexord_get_order_status(){
		$status = wc_get_order_statuses();		
		ob_start();
		$option = "";
		foreach ( $status as $key => $value ){
			$option .= '<label><input class="wc_order_status" type="checkbox" value="'.$key.'"> '.$value.'</label>';
		}		
		ob_end_clean();
		return array( "status" => true, "data" => $option );
	}
	
	/*
	 * Write excel data
	 */
	function wcexord_header_writer( $header ){
		Global $objPHPExcel;
		foreach( $header as $key => $value ){
			$header_exist    = false;
			$header_title    = apply_filters( "wc_order_export_header_render", $value["title"] );
			$heter_to_contend_map = "";
			$get_col_index   = PHPExcel_Cell::columnIndexFromString( $objPHPExcel->setActiveSheetIndex(0)->getHighestDataColumn() );
			
			// Check If header exist
			for( $i = 0; $i < $get_col_index; $i++ ){		
				if( !empty( PHPExcel_Cell::stringFromColumnIndex( $i ) ) ){					
				    if( $objPHPExcel->getActiveSheet()->getCell( PHPExcel_Cell::stringFromColumnIndex( $i ).'1' )->getValue() == $header_title && $header_title != "" && !$header_exist ){
						$header_exist = true;						
						$heter_to_contend_map = PHPExcel_Cell::stringFromColumnIndex( $i );						
					}
				}
			}
			// If exist header add content only
			$reender_val = false;
			if( $header_exist ) {		
			    $objPHPExcel->setActiveSheetIndex(0)->setCellValue( $heter_to_contend_map.$this->row_count,  apply_filters( "wc_order_export_value_render", $value["value"], $header_title ) );
			} else {
				$reender_val = true;
			}
			// If not exist header add header and content
			if( $reender_val ){
				$get_col_index_last = PHPExcel_Cell::stringFromColumnIndex( $get_col_index );
				$objPHPExcel->setActiveSheetIndex(0)->setCellValue( $get_col_index_last . "1",  $header_title  );
				$objPHPExcel->setActiveSheetIndex(0)->setCellValue( $get_col_index_last . $this->row_count,  apply_filters( "wc_order_export_value_render", $value["value"], $header_title ) );
			}
		}
		$this->row_count++;
	}
	
	
	private function change_order_status_title( $_status ){
		$_status = trim( ucwords( str_replace( array( "-", "_", "wc" ), array(" ", " ", " " ), $_status ) ), " " );
		return $_status;
	}
	
	/*	
	 * Write file data
	 */
	function wcexord_genarate_file_data( $payload, $add_to_order = false ){
	    Global $objPHPExcel, $wcexord;		
		$data           = apply_filters( "wc_order_export_data", $payload );	
		$file_type      = $wcexord->file_type;
		$file_name      = $wcexord->file_name;
		$filterd_data   = array();	
		$selectedfields = $wcexord->headers;
		if( sizeof( $data ) != 0 ){
		    $filterd_data = array();
	        for( $j = 0; $j < count( $data ); $j++ ){
    	        foreach( $selectedfields as $i => $title ){
    	            if( !isset( $filterd_data[$j] ) ){
    	                $filterd_data[$j] = array();
    	            } 
    	            if( strpos( $i, "empty" ) !== false ){
    	                $filterd_data[$j][$i] = array( "title" => "", "value" => "" );
    	            } else {
    	                if( isset( $data[$j][$i] ) ){
    	                    $filterd_data[$j][$i] =  array( "title" => $title, "value" => $data[$j][$i] );
    	                } else {
    	                    $filterd_data[$j][$i] =  array( "title" => $title, "value" => "" );
    	                }
    	            }
    	           
    	        }
		    }
		    error_log( json_encode( $filterd_data ) );
		} else {
			return array( 'status' => false, 'data' => "No order found you selected period." );
		}
		if( $file_type != "json" ){
			$objPHPExcel = new PHPExcel();
			// Set document properties
			$objPHPExcel->getProperties()->setCreator("wc order export")->setLastModifiedBy("wc order export")->setTitle("wc order export")->setSubject("wc order export")->setDescription("wc order export.")->setKeywords("wc order export")->setCategory("wc order export");
			
			// write excel file from $filterd_data
			$objPHPExcel->getActiveSheet()->getStyle('A1:XFD1')->getFont()->setBold( true );
			for( $j = 0; $j < sizeof( $filterd_data ); $j++ ){
			    $this->wcexord_header_writer( $filterd_data[ $j ] );			
			}			
			
			// Rename worksheet
			$objPHPExcel->getActiveSheet()->setTitle( 'wc_order_export' );			
			
			// Set active sheet index to the first sheet, so Excel opens this as the first sheet
			$objPHPExcel->setActiveSheetIndex(0);
			// Remove First Coloumn
			$objPHPExcel->getActiveSheet()->removeColumn( "A" );
			
			if( $file_type == "xlsx" ){
			    if( !class_exists( "XMLWriter" ) ){
			        return array( 'status' => false, 'data' => "Please install php XMLWriter." );
			    }
				// Set excel headers
			    if( !$add_to_order ){
				    header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );	
			    }
				$objWriter = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
			} else if( $file_type == "csv" ){
			    if( !$add_to_order ){
				    header('Content-type: text/csv');
			    }
				$objWriter = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'CSV' );
			}
			if( !$add_to_order ){
    			header( 'Content-Disposition: attachment;filename="'.$file_name.'.'.$file_type.'"' );
    			header( 'Cache-Control: max-age=0' );
    			header( 'Cache-Control: max-age=1' );
    			header( 'Last-Modified: '.gmdate( 'D, d M Y H:i:s' ).' GMT' );
    			header( 'Cache-Control: cache, must-revalidate' );
    			header( 'Pragma: public' );				
    			
    	 		ob_start();
    	 		$objWriter->save('php://output');
    	 		$xlsData = ob_get_contents();
    	 		ob_end_clean();
    	 		$response =  array( 'status' => true, 'data' => "data:application/vnd.ms-excel;base64,".base64_encode( $xlsData ), "type" => $file_type );
    	 		return $response;
			} else {
			    $objWriter->save( wp_upload_dir()["path"] . "/order_" . $payload[ "wcexpo_orderid" ] . ".". $file_type );
			    return wp_upload_dir()["path"] . "/order_" . $payload[ "wcexpo_orderid" ] . "." . $file_type;
			}
		} else if( $file_type == "json" ){
		    if( !$add_to_order ){
			     return array( 'status' => true, 'data' => json_encode( $filterd_data ), "type" => "json" );
		    } else {
		        $json_order_file_path = wp_upload_dir()["path"] . "/order_" . $payload[ "wcexpo_orderid" ] .".json";
		        $json_file = fopen( $json_order_file_path, "a" );
		        fwrite( $json_file, $filterd_data );
		        fclose( $json_file );
		        return $json_order_file_path;
		    }
		}
 	}
}
new wcexord_genarate_export_order();
?>