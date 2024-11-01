<?php if (!defined('ABSPATH')) { exit; }
    global $wcexord;
    $meta_array = $wcexord->headers;
?>
<div class="wc-order-export-container">
<h3>WC Order Export</h3>
	<table>
		<tbody>
			<tr>
				<td>Meta Fields Header<p class="description">Select fields want to export with order item meta</p></td>
				<td>
				
				<ul class="wcordex_checkbox">
    				<?php
                        
    				    foreach( $meta_array as $key => $val ) {
    				        echo '<li data-key="'.$key.'"><label>' . $val . '</label><span>&#10005;</span></li>';
    				    }
                    ?>
                    </ul>
                    
				</td>
			</tr>
			
			<tr>
				<td>Custom Meta<p class="description">If your meta field not in droggable list you can add your custom field here.<br><br>
				If you want empty column type empty_index ( ex: empty_1 empty_2 )
				</p></td>
				<td>
				<div style="display: inline-block;">
                        <input type="text" id="add_new_field_meta_name" placeholder="Meta Key">
                        <input type="text" id="add_new_field_title" placeholder="Title">
                        <button class="button" id="add_new_field_button" ><?php echo __( "Add Field" ); ?></button>
                   </div> 
                </td>
			</tr>
			
			<tr>
				<td>Row type</td>
				<td>
					<?php $row_type = $wcexord->row_type; ?>
					<label><input type="radio" name="export_row_type" value="by_order" <?php echo ( $row_type == "by_order" ? "checked" : "" ); ?> class="export_file_type" checked> By Order </label>
					<label><input type="radio" name="export_row_type" value="by_order_item" <?php echo ( $row_type == "by_order_item" ? "checked" : "" ); ?> class="export_file_type"> By Order Item </label>
				</td>
			</tr>
			
			<tr>
				<td>Add to new order mail</td>
				<td>
					<?php $is_add = $wcexord->add_to_order; ?>
					<label><input type="radio" name="wcorx_add_new_order" value="yes" <?php echo ( $is_add  ? "checked" : "" ); ?> class="export_file_type" checked> Yes </label>
					<label><input type="radio" name="wcorx_add_new_order" value="no" <?php echo ( $is_add  ? "" : "checked" ); ?> class="export_file_type"> No </label>
				</td>
			</tr>
			
			
			
			
    		<tr>
				<td>File name</td>
				<td>
				<input type="text" name="download_file_name" value="<?php echo $wcexord->file_name; ?>" class="download_file_name" checked></td>
			</tr>
			<tr>
				<td>File type</td>
				<td>
					<?php $file_type = $wcexord->file_type; ?>
					<label><input type="radio" name="export_file_type" value="xlsx" <?php echo ( $file_type == "xlsx" ? "checked" : "" );  ?> class="export_file_type" checked> XLSX </label>
					<label><input type="radio" name="export_file_type" value="csv" <?php echo ( $file_type == "csv" ? "checked" : "" );  ?> class="export_file_type"> CSV </label>
					<label><input type="radio" name="export_file_type" value="json" <?php echo ( $file_type == "json" ? "checked" : "" );  ?> class="export_file_type"> JSON </label>
					<button type="button" id="wcexp-update-header-meta" class="button button-primary button-large">Update</button>
				</td>
			</tr>


			<tr class="wc-export-selector-container-start wc-ord-exp-border">
				<td>Start export date</td>
				<td><input type="text" placeholder="dd-mm-yyyy"
					name="export_start_date" class="export_start_date"></td>
			</tr>
			<tr class="wc-ord-exp-border">
				<td>Start export date</td>
				<td><input type="text" placeholder="dd-mm-yyyy"
					name="export_end_date" class="export_end_date"> <strong>OR</strong></td>
			</tr>


			<tr class="wc-ord-exp-border">
				<td>Order Id <p class="description">For multiple order ids use cumma separate. ( Ex: 150, 151, 152 )</td>
				<td><input type="text" name="wcexpo_orderid"
					placeholder="Please enter your order ID."></td>
			</tr>

			<tr class="wc-ord-exp-border">
				<td>Order Status</td>
				<td class="order-export-option-select">
					<label><input class="wc_order_status all_oder_status_selected" type="checkbox" value="wc-all"> All </label>
				</td>
			</tr>




			<tr class="wc-export-selector-container-end wc-ord-exp-border">
    				<td>
    					
    				</td>
    				<td class="wp-core-u"><span id="wc_order_export_user_info_box"
    					style="text-transform: capitalize; padding-left: 10px; color: green; font-size: 16px;"></span>
    				<button type="button" id="export-order-button"
    						class="button button-primary button-large">Export</button></td>
    			</tr>
		<tbody>
	
	</table>
	<div>
		<h3>Export Fields</h3>
    	<div class="wcorx-all-field-meta-key">
    		
        	<ul>
        		<?php
        		for( $i = 0; $i < count( wcexord_order_export()->fields ); $i++ ){
        		    if( !isset( $meta_array[ wcexord_order_export()->fields[$i] ] ) ){
        		      echo '<li draggable="true" data-key="'.wcexord_order_export()->fields[$i].'"><label>'.wcexord_order_export()->fields[$i].'</label><span>&#10005;</span></li>';
        		    }
        		}
        		?>
        	</ul>
    		
    	</div>
    	<p class="description">Drag and drop to "meta fields" area.</p> 
	</div>
		<?php 
		do_action( "wc_order_export_render_page", "wcexord_order_export" );
		?>
</div>