<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
* Meta Box General
*
* diplays text input for oembed general
*
* @version 1.0
* @author codeBOX
* @project lifterLMS
*/
class LLMS_Meta_Box_Coupon_Options {

	public $prefix = '_llms_';


	public static function output( $post ) {
		$prefix = '_llms_';
		global $post;
		wp_nonce_field( 'lifterlms_save_data', 'lifterlms_meta_nonce' );

				
		$coupon_creator_meta_fields = self::metabox_options();
					
		ob_start(); ?>

		<table class="form-table">
		<?php foreach ($coupon_creator_meta_fields as $field) {
			
			$meta = get_post_meta($post->ID, $field['id'], true); ?>

				<tr>
					<th><label for="<?php echo $field['id']; ?>"><?php echo $field['label']; ?></label></th>
					<td>
					<?php switch($field['type']) { 
						// text
						case 'text':?>
						
							<input type="text" name="<?php echo $field['id']; ?>" id="<?php echo $field['id']; ?>" value="<?php echo $meta; ?>" size="30" />
								<br /><span class="description"><?php echo $field['desc']; ?></span>
								
						<?php break;
						// textarea
						case 'textarea': ?>
						
							<textarea name="<?php echo $field['id']; ?>" id="<?php echo $field['id']; ?>" cols="60" rows="4"><?php echo $meta; ?></textarea>
								<br /><span class="description"><?php echo $field['desc']; ?></span>
								
						<?php break;
						// textarea
						case 'textarea_w_tags': ?>
						
							<textarea name="<?php echo $field['id']; ?>" id="<?php echo $field['id']; ?>" cols="60" rows="4"><?php echo $meta; ?></textarea>
								<br /><span class="description"><?php echo $field['desc']; ?></span>
						
						<?php break;
						//dropdown
						case 'dropdown': ?>
							
							<select name="<?php echo $field['id']; ?>" id="<?php echo $field['id']; ?>">
							<?php foreach ($field['options'] as $id => $option) :?>
								<option value="<?php echo $id; ?>"><?php echo $option; ?></option>
							<?php endforeach; ?>
							</select>
							<br /><span class="description"><?php echo $field['desc']; ?></span>
						
						<?php break;
						// image using Media Manager from WP 3.5 and greater
						case 'image': 
						
							$image = apply_filters( 'lifterlms_placeholder_img_src', LLMS()->plugin_url() . '/assets/images/optional_coupon.png' ); ?>
							<img id="<?php echo $field['id']; ?>" class="llms_achievement_default_image" style="display:none" src="<?php echo $image; ?>">
							<?php //Check existing field and if numeric
							if (is_numeric($meta)) { 
								$image = wp_get_attachment_image_src($meta, 'medium'); 
								$image = $image[0];
							} ?>
									<img src="<?php echo $image; ?>" id="<?php echo $field['id']; ?>" class="llms_achievement_image" /><br />
									<input name="<?php echo $field['id']; ?>" id="<?php echo $field['id']; ?>" type="hidden" class="upload_achievement_image" type="text" size="36" name="ad_image" value="<?php echo $meta; ?>" /> 
									<input id="<?php echo $field['id']; ?>" class="achievement_image_button" type="button" value="Upload Image" />
									<small> <a href="#" id="<?php echo $field['id']; ?>" class="llms_achievement_clear_image_button">Remove Image</a></small>
									<br /><span class="description"><?php echo $field['desc']; ?></span>
									
						<?php break;					
						// color
						case 'color': ?>
							<?php //Check if Values and If None, then use default
								if (!$meta) {
									$meta = $field['value'];
								}
							?>
							<input class="color-picker" type="text" name="<?php echo $field['id']; ?>" id="<?php echo $field['id']; ?>" value="<?php echo $meta; ?>" data-default-color="<?php echo $field['value']; ?>"/>
								<br /><span class="description"><?php echo $field['desc']; ?></span>
						
					<?php break;
	
						} //end switch
					
					?>
				</td></tr>
		<?php	
			//endif; //end if in section check
		
		} // end foreach ?>
			</table>	
	<?php
	echo ob_get_clean();
	}	


	public static function metabox_options() {
		$prefix = '_llms_';
		
		$coupon_creator_meta_fields = array(
			array(
				'label' => 'Coupon Code',
				'desc' => 'Enter a code that users will enter to apply this coupon to thier item.',
				'id' => $prefix . 'coupon_title',
				'type'  => 'text',
				'section' => 'coupon_meta_box'
			),
			array(
				'label' => 'Discount Type',
				'desc' => 'Select a dollar or percentage discount.',
				'id' => $prefix . 'discount_type',
				'type'  => 'dropdown',
				'section' => 'coupon_meta_box',
				'options' => array(
						'percent' => '% Discount',
						'dollar' => '$ Discount'
					)
			),
			array(
				'label'  => 'Coupon Amount',
				'desc' => 'The value of the coupon. do not include symbols such as $ or %.',
				'id'    => $prefix . 'coupon_amount',
				'type'  => 'text',
				'section' => 'coupon_meta_box'
			),
			array(
				'label'  => 'Usage Limit',
				'desc' => 'The amount of times this coupon can be used. Leave empty if unlimited.',
				'id'    => $prefix . 'usage_limit',
				'type'  => 'text',
				'section' => 'coupon_meta_box'
			),
			// array(
			// 	'label'  => 'Expiration Date',
			// 	'desc' => 'Enter a day the coupon will expire.',
			// 	'id'    => $prefix . 'expiration_date',
			// 	'type'  => 'date',
			// 	'section' => 'coupon_meta_box'
			// ),				
		);

		if(has_filter('llms_meta_fields')) {
			//Add Fields to the coupon Creator Meta Box
			$coupon_creator_meta_fields = apply_filters('llms_meta_fields', $coupon_creator_meta_fields);
		} 
		
		return $coupon_creator_meta_fields;
		}


	public static function save( $post_id, $post ) {
		global $wpdb;
		$prefix = '_llms_';

		$title = $prefix 	. 'coupon_title';
		$type = $prefix 	. 'dicount_type';
		$amount = $prefix 	. 'coupon_amount';
		$limit = $prefix 	. 'usage_limit';
		
		$update_title = ( llms_clean( $_POST[$title]  ) );
		update_post_meta( $post_id, $title, ( $update_title === '' ) ? '' : $update_title );

		$update_type = ( llms_clean( $_POST[$type]  ) );
		update_post_meta( $post_id, $type, ( $update_type === '' ) ? '' : $update_type );

		$update_amount = ( llms_clean( $_POST[$amount]  ) );
		update_post_meta( $post_id, $amount, ( $update_amount === '' ) ? '' : $update_amount );

		$update_limit = ( llms_clean( $_POST[$limit]  ) );
		update_post_meta( $post_id, $limit, ( $update_limit === '' ) ? '' : $update_limit );

	}

}