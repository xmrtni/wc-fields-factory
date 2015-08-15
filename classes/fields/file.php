<?php 

if ( ! defined( 'ABSPATH' ) ) { exit; }

class wccpf_field_file extends wccpf_product_field {
	
	function __construct() {
		$this->name 		= "file";
		$this->label 		= "File";
		$this->required 	= false;
		$this->message 		= "This field can't be Empty";
		$this->params 		= array(
			'filetypes'	=>	''				
		);	

		/* File upload validator */
		add_filter( 'wccpf/upload/validate', array( $this, 'validate' ), 5, 3 );
		/* File upload filter */
		add_filter( 'wccpf/upload/type=file', array( $this, 'process_file_upload' ) );
		
    	parent::__construct();
	}
	
	function render_admin_field() { ob_start(); ?>
			<tr>
				<td class="summary">
					<label for="post_type"><?php _e( 'Required', 'wc-fields-factory' ); ?></label>
					<p class="description"><?php _e( 'Is this field Mandatory', 'wc-fields-factory' ); ?></p>
				</td>
				<td>
					<div class="wccpf-field-types-meta" data-type="radio" data-param="required">
						<ul class="wccpf-field-layout-horizontal">
							<li><label><input type="radio" name="wccpf-field-type-meta-required" value="yes" /> <?php _e( 'Yes', 'wc-fields-factory' ); ?></label></li>
							<li><label><input type="radio" name="wccpf-field-type-meta-required" value="no" checked/> <?php _e( 'No', 'wc-fields-factory' ); ?></label></li>
						</ul>						
					</div>
				</td>
			</tr>
			
			<tr>
				<td class="summary">
					<label for="post_type"><?php _e( 'Message', 'wc-fields-factory' ); ?></label>
					<p class="description"><?php _e( 'Message to display whenever the validation failed', 'wc-fields-factory' ); ?></p>
				</td>
				<td>
					<div class="wccpf-field-types-meta" data-type="text" data-param="message">
						<input type="text" id="wccpf-field-type-meta-message" value="<?php echo esc_attr( $this->message ); ?>" />						
					</div>
				</td>
			</tr>
			
			<tr>
				<td class="summary">
					<label for="post_type"><?php _e( 'Allowed File Types', 'wc-fields-factory' ); ?></label>
					<p class="description"><?php _e( 'Enter comma seperated list of file type extensions', 'wc-fields-factory' ); ?><br/><br/>pdf,docx,jpg,png</p>
				</td>
				<td>
					<div class="wccpf-field-types-meta" data-type="textarea" data-param="filetypes">
						<textarea rows="6" id="wccpf-field-type-meta-filetypes"></textarea>						
					</div>
				</td>
			</tr>		
			
		<?php
		return ob_get_clean();
	}
	
	function render_product_field( $field ) { ob_start(); ?>
	
		<?php if( has_action('wccpf/before/field/rendering' ) && has_action('wccpf/after/field/rendering' ) ) : ?>
		
			<?php do_action( 'wccpf/before/field/rendering', $field["name"], $field["label"] ); ?>
			
			<input type="file" name="<?php echo esc_attr( $field["name"] ); ?>" />
			
			<?php do_action( 'wccpf/after/field/rendering' ); ?>
		
		<?php else : ?>
		
		<table class="wccpf_fields_table <?php echo apply_filters( 'wccpf/fields/container/class', '' ); ?>" cellspacing="0">
			<tbody>
				<tr>
					<td class="wccpf_label"><label for="<?php echo esc_attr( $field["name"] ); ?>"><?php echo esc_html( $field["label"] ); ?></label></td>
					<td class="wccpf_value">
						<input type="file" name="<?php echo esc_attr( $field["name"] ); ?>" />
					</td>
				</tr>
			</tbody>
		</table>
		
		<?php endif ?>
		
	<?php return ob_get_clean();
	
	}
	
	function process_file_upload( $uploadedfile ) {	
		if ( !function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}			
		$movefile = wp_handle_upload( $uploadedfile, array( 'test_form' => false ) );		
		return $movefile;		
	}
	
	function validate( $uploadedfile, $file_types, $mandatory ) {
		
		$file_ok = false;
		$no_file = false;
		
		if( isset( $uploadedfile['error'] ) ) {
			switch ( $uploadedfile['error'] ) {
				case UPLOAD_ERR_OK:
					$file_ok = true;
					break;
				case UPLOAD_ERR_NO_FILE:
					$no_file = true;
					break;
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					$file_ok = false;
				default:
					$file_ok = false;
			}
		}
		
		if( $file_ok ) {				
			$filename = $uploadedfile['name'];
			$allowed_types = explode( ',', $file_types );	
			$ext = pathinfo( $filename, PATHINFO_EXTENSION );
			
			if( !in_array( $ext, $allowed_types ) || $ext == "php" ) {
				$file_ok = false;
			}			
		}			
		
		if( $no_file && $mandatory == "no" ) {
			return true;
		}
		return $file_ok;
		
	}
	
}

new wccpf_field_file();

?>