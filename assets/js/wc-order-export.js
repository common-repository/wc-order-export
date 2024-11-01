/**
 * @author  	: Saravana Kumar K, sarkparanjothi
 * @author url 	: http://iamsark.com
 * @url			: http://sarkware.com/
 * @copyrights	: SARKWARE
 * @purpose 	: WC ORDER EXPORT
 */
var $ = jQuery;
var return_page = "";
$( document ).ready( function(){	
	var wcexord_order_export = new wcexord_order_export_obj();
	$( ".export_start_date" ).datepicker({ dateFormat: 'dd-mm-yy' });
	$( ".export_end_date" ).datepicker({ dateFormat: 'dd-mm-yy' }).datepicker('setDate', 'today');
	$( document ).on( "click", "#export-order-button", function(){
		var status_check = $( '.wc_order_status' ),
			status = "wc-all",
			field_meta = [];
		if( !$( ".all_oder_status_selected" ).is( ":checked" ) && $( '.wc_order_status' ).is( ":checked" ) ){
			status = [];
			for ( var i = 0; i < status_check.length; i++ ){
				select_single = $(  status_check[i] );
				if( select_single.is( ":checked" ) &&  select_single.val() != "wc-all" ){
					status.push( select_single.val() );
				}
			}
			status = JSON.stringify( status );
		}
		
		$.each($( ".wcordex_checkbox input:checked" ), function(){
			field_meta.push( $( this ).val() );
		});
		if( $( "input[name=export_start_date]" ).val() == "" && $( "input[name=wcexpo_orderid]" ).val() == "" ){
			$( "#wc_order_export_user_info_box" ).css( "color", "red" );
			$( "#wc_order_export_user_info_box" ).text( "Please select start date or fill order ids..." );
			$( "input[name=export_start_date]" ).css( "border-color", "red" ).focus();
			return ;
		}
		var data = { "start_date" : $( "input[name=export_start_date]" ).val(), "end_date" : $( "input[name=export_end_date]" ).val(), "order_status" : status, "wcexpo_orderid" : $( "input[name=wcexpo_orderid]" ).val(), "file_name" : $( "input[name=download_file_name]" ).val() };
		wcexord_order_export.conn(  "export_order", data );
	});
	
	$( document ).on( "click", ".wcordex_checkbox li span", function(){
		var field = $( this ).closest( "li" );
		field[0].addEventListener('dragstart', dragStart, false);
		field[0].addEventListener('dragend', dragEnd, false);
		$( ".wcorx-all-field-meta-key" ).append( field );
		$( ".wcorx-all-field-meta-key" ).find( ".update-old-title" ).remove();
	});
	
	$( document ).on( "click", "#add_new_field_button", function(){
		if( $( "#add_new_field_meta_name" ).val().trim() != "" && $( "#add_new_field_title" ).val().trim() != "" ){
			$( ".wcordex_checkbox" ).append( '<li data-key="'+$( "#add_new_field_meta_name" ).val()+'" class="ui-sortable-handle"><label>' + $( "#add_new_field_title" ).val() + '</label><span>&#10005;</span></li>' );
		} else {
			alert( "Title and meta should not be empty." );
		}
	});
	
	$( document ).on( "keyup", ".wcorx_add_new_field_title", function(){
		$( this ).closest( "li" ).find( "label" ).text(  $( this ).val() );
	});
	
	
	$( document ).on( "click", "ul.wcordex_checkbox li", function( e ){
		if( $( this ).find( ".update-old-title" ).length == 0 && !$( e.target ).is( "span" ) ){
			var html = '<div class="update-old-title"><div><input type="text" class="wcorx_add_new_field_meta_name" value="'+ $( this ).data( "key" ) +'" placeholder="Meta Key" disabled></div><div><input type="text" value="'+ $( this ).text().substring( 0,  $( this ).text().length-1 ) +'" class="wcorx_add_new_field_title" placeholder="Title"></div></div>';
			$( this ).append( html );
		} else {
			if( $( e.target ).is( "li" ) || $( e.target ).is( "label" ) ){
				$( this ).find( ".update-old-title" ).remove();
			}
		}
	});
	
	$( document ).on( "change", ".all_oder_status_selected", function(){
		if( $( this ).is( ":checked" ) ){
			$( '.wc_order_status' ).prop( "checked", true );
		} else {
			$( '.wc_order_status' ).prop( "checked", false );
		}
	});
	
	$( document ).on( "change", ".wc_order_status", function(){
		if( !$( this ).is( ":checked" ) ){
			$( '.all_oder_status_selected' ).prop( "checked", false );
		} 
	});
	
	if( $( ".order-export-option-select" ).length != 0 ){
		wcexord_order_export.conn(  "get_order_status", { "data" : "get_order_data" } );
	}
	
	
	$( document ).on( "change", ".select_all_meta_fields", function(){
		 $( this ).closest( "ul" ).find( "input" ).prop( "checked", $( this ).is( ":checked" ) );
	});
	
	$( document ).on( "click", "#wcexp-update-header-meta", function(){
		var list = $( ".wcordex_checkbox li" ),
		obj = {};
		for ( var i = 0; i < list.length; i++ ){
			obj[$( list[i] ).attr( "data-key" )] = $( list[i] ).find( "label" ).text();
		}
		var data = { "data" : obj, "add_to_order" : $( "input[name=wcorx_add_new_order]:checked" ).val(), "row_type" : $( "input[name=export_row_type]:checked" ).val(), "file_type" : $( "input[name=export_file_type]:checked" ).val(), "file_name" : $( "input[name=download_file_name]" ).val() };
		wcexord_order_export.conn(  "wcexpordercsvheader", data );
	});
	
	//
	$( ".all_oder_status_selected" ).prop( "checked", true ).trigger( "change" );
	
	jQuery( ".wcordex_checkbox" ).sortable();
	
	

	// for field drag and drop start
	var currenctDraggedField = "";
	var currenctDropField = "";
	function dragStart(e){
		 e.stopPropagation();
		 var fieldObj = this;
		 fieldObj.className = fieldObj.className + " is-dragged";
		 currenctDraggedField = e.target;
	}

	function dragEnd(e){
		 e.stopPropagation();
		 var fieldObj = this;
		 fieldObj.className = fieldObj.className.replace('is-dragged',''); 
		 currenctDraggedField = "";
		 $( currenctDropField ).removeClass( "dropover" );
		 currenctDropField = "";
	}

	function dragEnter(e){
		  e.stopPropagation();
		  this.className = this.className + " dropover";
	}

	function dragLeave(e){
		  e.stopPropagation();
		  this.className = this.className.replace('dropover','');
	}

	function dragOver(e){
		  e.stopPropagation();
		  currenctDropField = e.target;
		  e.preventDefault();
		  return false; 
	}

	function drop(e){
		 if (e.stopPropagation) e.stopPropagation();
		 $( dropArea ).append( $( currenctDraggedField ) );
		return false;
	}

	  var fields = document.querySelectorAll('.wcorx-all-field-meta-key li'),
	      dropArea = document.getElementsByClassName('wcordex_checkbox')[0];
	  
	  for( var i=0,len=fields.length; i<len; i++ ){
		  fields[i].addEventListener('dragstart', dragStart, false);
		  fields[i].addEventListener('dragend', dragEnd, false);
	  }
	  if( dropArea != null ){
		  dropArea.addEventListener('dragenter', dragEnter, false);
		  dropArea.addEventListener('dragleave', dragLeave, false);
		  dropArea.addEventListener('dragover', dragOver, false);
		  dropArea.addEventListener('drop', drop, false);
	  }

	// for field drag and drop end
	
});


var wcexord_order_export_obj = function(){
	this.ajaxFlaQ = true;
	this.conn = function( _action, _payload ) {	
		_payload[ "action" ] = _action;
		var me = this;
		/* see the ajax handler is free */
		if( !this.ajaxFlaQ ) {
			return;
		}	
		
		$.ajax({  
			type       : "POST",  
			data       : { action : "wcexord_exporter", payload : _payload },  
			dataType   : "json",  
			url        : wcexord_ajax_url,  
			responseType : 'blob',
			beforeSend : function(){  
				if( _action == "export_order" ){
					$( "#wc_order_export_user_info_box" ).css( "color", "black" );
					$( "#wc_order_export_user_info_box" ).text( "Please wait order export processing..." );
				}
				/* enable the ajax lock - actually it disable the dock */
				me.ajaxFlaQ = false;				
			},  
			success    : function(data) {				
				/* disable the ajax lock */
				me.ajaxFlaQ = true;				
				/* handle the response and route to appropriate target */
				/*me.responseHandler( _action, data );*/
				me.responseHandler( _action, data );
										
			},  
			error      : function(jqXHR, textStatus, errorThrown) {                    
				/* disable the ajax lock */
				me.ajaxFlaQ = true;
				
			},
			complete   : function( dsad ) {
				
			}   
		});
		
	};
	
	this.responseHandler = function( _action, data ){
		if( !data.status && typeof data.data != "undefined" ){
			if( _action == "export_order" ){
				$( "#wc_order_export_user_info_box" ).css( "color", "red" );
				$( "#wc_order_export_user_info_box" ).text( data.data+"." );
			}
		} else {
			if( _action ==  "wcexpordercsvheader" ){
				$( "#wc_order_export_user_info_box" ).css( "color", "green" );
				$( "#wc_order_export_user_info_box" ).text( "Successfully updated." );
				window.location.reload();
				return false;
			}
			
			if( _action == "export_order" ){
				$( "#wc_order_export_user_info_box" ).css( "color", "green" );
				$( "#wc_order_export_user_info_box" ).text( "Successfully Exported." );
			}
			if( _action == "export_order" ){
				if( data.type == "xlsx" || data.type == "csv" ){
				 var $a = $( "<a>" );
				    $a.attr( "href", data.data );
				    $( "body" ).append( $a );
				    var file_name = $( ".download_file_name" ).val().trim() == "" ? "wc-order-export" : $( ".download_file_name" ).val();
				    $a.attr( "download", file_name+"."+data.type );
				    $a[0].click();
				    $a.remove();
				} else if ( data.type == "json" ) {
					var textFile = null;
					 var json_data = new Blob( [ data.data ] , { type: 'text/plain'} );
					    if ( textFile !== null ) {
					      window.URL.revokeObjectURL( textFile );
					    }
					    textFile = window.URL.createObjectURL( json_data );
					    var $a = $( "<a>" );    
					    $a.attr( "href", textFile );
					    $( "body" ).append( $a );
					    $a.attr( "download", $( ".download_file_name" ).val()+".json" );
					    $a[0].click();
					    $a.remove();
				}
			} else if( _action == "get_order_status" ){
				$( ".order-export-option-select" ).append( data.data );
			}
		}
	};
	
	
		
};
