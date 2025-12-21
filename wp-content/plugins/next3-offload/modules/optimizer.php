<?php 
namespace Next3Offload\Modules;
defined( 'ABSPATH' ) || exit;

class Optimizer{
    private static $instance;

    public $default_max_width_sizes = array(
		array(
			'label' => '2560px',
			'value' => 2560,
			'selected' => 0,
		),
		array(
			'label' => '2048px',
			'value' => 2048,
			'selected' => 0,
		),
		array(
			'label' => '1920px',
			'value' => 1920,
			'selected' => 0,
		),
		array(
			'label' => 'Disabled',
			'value' => 0,
			'selected' => 0,
		),
	);

    const BATCH_LIMIT = 200;

	const PNGS_SIZE_LIMIT = 1048576;

	public $type = 'image';

	public $non_optimized = 'next3_optimizer_total_unoptimized_images';

	public $batch_skipped = 'next3_optimizer_is_optimized';

	public $process_map = array(
		'filter'   => 'next3_optimizer_image_optimization_timeout',
		'attempts' => 'next3_optimizer_optimization_attempts',
		'failed'   => 'next3_optimizer_optimization_failed',
	);

    public $options_map = array(
		'completed' => 'next3_optimizer_image_optimization_completed',
		'status'    => 'next3_optimizer_image_optimization_status',
		'stopped'   => 'next3_optimizer_image_optimization_stopped',
	);
	
	public $compression_level_map = array(
		// IMAGETYPE_GIF.
		1 => array(
			'1'    => '-O1', // Low.
			'2' => '-O2', // Medium.
			'3'   => '-O3', // High.
		),
		// IMAGETYPE_JPEG.
		2 => array(
			'1'    => '-m85', // Low.
			'2' => '-m60', // Medium.
			'3'   => '-m20', // High.
		),
		// IMAGETYPE_PNG.
		3 => array(
			'1'    => '-o1',
			'2' => '-o2',
			'3'   => '-o3',
		),
	);


    public function init() {
		// compress action action included to compatiblility.php

        // Get the resize_images option
        $settings_options = next3_options();
        $optimizer_resize_images = ($settings_options['optimization']['optimizer_resize_images']) ?? 2560;
		$resize_images = apply_filters( 'next3_set_max_image_width', intval( $optimizer_resize_images ) );
		// Resize newly uploaded images
		if ( 2560 !== $resize_images ) {
			add_filter( 'big_image_size_threshold', array( $this, 'resize' ) );
		}

		add_filter( 'attachment_fields_to_edit', array( $this, 'edit_media_action_field' ), null, 2 );
    }

    public function resize( $image_data ) {
		// Getting the option value from the db and applying additional filters, if any.
		$settings_options = next3_options();
        $optimizer_resize_images = ($settings_options['optimization']['optimizer_resize_images']) ?? 2560;

		// Disable resize, if it's set so in the DB and no filters are found.
		if ( 0 === intval ( $optimizer_resize_images ) ) {
			return false;
		}

		// Adding a min value.
		$optimizer_resize_images = intval( $optimizer_resize_images ) < 1200 ? 1200 : intval( $optimizer_resize_images );

		return intval( $optimizer_resize_images );
	}

    public function delete_media_action( $id, $only_backup = true ) {
		
		$main_image = next3_get_attached_file( $id, true);
		$metadata   = next3_wp_get_attachment_metadata( $id, true);
		$basename   = basename( $main_image );

		if( $only_backup ){
			next3_delete_post_meta( $id, 'next3_optimizer_is_optimized');
			next3_delete_post_meta( $id, 'next3_optimizer_optimization_failed');
			next3_delete_post_meta( $id, 'next3_optimizer_original_filesize');
			next3_delete_post_meta( $id, 'next3_optimizer_compression_level');
		}
		
		next3_delete_post_meta( $id, 'next3_optimizer_orginal_file', true );
		
        $backup_file = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $main_image );
        if ( !file_exists( $backup_file ) ) {
            return;
        }
		@unlink( preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $main_image ) );

		if ( ! empty( $metadata['sizes'] ) ) {
			// Loop through all image sizes and optimize them as well.
			foreach ( $metadata['sizes'] as $size ) {
				@unlink( preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', str_replace( $basename, $size['file'], $main_image ) ) );
			}
		}
		
	}

    public function upload_media_action( $metadata, $id ) {
		
		// check settings
		$settings_options = next3_options();
		$copy_file = ($settings_options['storage']['copy_file']) ?? 'no';
		$remove_local = isset($settings_options['storage']['remove_local']) ? true : false;
		$webp_enable = ($settings_options['optimization']['webp_enable']) ?? 'no';

		// convert to compress
		$this->optimize( $id, $metadata );

		// compress webp
		if($webp_enable == 'yes'){
			next3_core()->webp_ins->optimize( $id, $metadata);
		}
		// offload status
		$status_data = next3_service_status();
		$offload_status = ($status_data['offload']) ?? false;
		if( !$offload_status ){
			return $metadata;
		}
		// offload settings
		if( next3_get_post_meta($id, '_next3_attached_url') === false){
			if( $copy_file == 'yes' ){
				next3_core()->action_ins->copy_to_cloud('', $id, $remove_local);
			}
		}

		return $metadata;

	}

    public function optimize( $id, $metadata, $main_image = '' ) {
		
        $settings_options = next3_options();
        $compression_level = ($settings_options['optimization']['compression_level']) ?? 0;
        $overwrite_custom = ($settings_options['optimization']['overwrite_custom']) ?? 'no';
        $webp_enable = ($settings_options['optimization']['webp_enable']) ?? 'no';
		$overwrite_webp = ($settings_options['optimization']['overwrite_webp']) ?? 'no';

		if(true === next3_check_post_meta($id, 'next3_optimizer_is_optimized') && 'yes' !== $overwrite_custom){
			return true;
		}
		// Bail if the override is disabled and the image has a custom compression level.
        if (
			'yes' !== $overwrite_custom &&
			! empty( next3_get_post_meta( $id, 'next3_optimizer_compression_level') )
		) {
			return false;
		}

		// Get path to main image.
		if( empty($main_image) || !file_exists($main_image) ){
			$main_image = next3_get_attached_file( $id, true);
		}
		$original_file = $main_image;

		// check webp
		$webp_status = false;
		if( true === next3_check_post_meta($id, 'next3_optimizer_is_converted_to_webp')){
			if(strpos($main_image, ".webp") === false){
				$main_image .= '.webp';
			}
			$webp_status = true;
		}

		// overwrite image
		if('yes' == $overwrite_custom || !is_readable($main_image) ){
			$old_main_image = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $original_file );
			if ( file_exists( $old_main_image ) ) {
				copy( $old_main_image, $main_image );
			}
		}
		
		// backup
		$backup_orginal = ($settings_options['optimization']['backup_orginal']) ?? 'no';
		$backup_filepath = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $original_file );
		if (
			$backup_orginal == 'yes' &&
			! file_exists( $backup_filepath ) &&
			file_exists($main_image)
		) {
			copy( $main_image, $backup_filepath );
			next3_update_post_meta( $id, 'next3_optimizer_orginal_file', 1 );
		}

		// Get the basename.
		$basename = basename( $main_image );
		// Get the command placeholder. It will be used by main image and to optimize the different image sizes.
		$status = $this->optimization_action( $main_image, $id, $compression_level);
		
		// Optimization failed.
		if ( true === boolval( $status ) ) {
			next3_update_post_meta( $id, 'next3_optimizer_optimization_failed', 1 );
			return false;
		}
		
		// Check if there are any sizes.
		if ( ! empty( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size ) {
				$file_name = $size['file'];

				$orginal_file_sub = str_replace( $basename, $file_name, $main_image );
				if( $webp_status ){
					if(strpos($file_name, ".webp") === false){
						$file_name .= '.webp';
					}
				}
				
				$main_image_sub = str_replace( $basename, $file_name, $main_image );
				// overwrite image
				if('yes' == $overwrite_custom || !is_readable($main_image_sub) ){
					$old_main_image = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $orginal_file_sub );
					if ( file_exists( $old_main_image ) ) {
						copy( $old_main_image, $main_image_sub );
					}
				}

				// backup path
				$backup_filepath = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $orginal_file_sub );
				if (
					$backup_orginal == 'yes' &&
					! file_exists( $backup_filepath ) &&
					file_exists($main_image_sub)
				) {
					copy( $main_image_sub, $backup_filepath );
				}

				$status = $this->optimization_action( $main_image_sub, $id, $compression_level);
			}

			next3_update_post_meta( $id, 'next3_optimizer_original_filesize', $metadata['filesize'] ) ;
		}

		// Everything ran smoothly.
		if ( true !== boolval( $status ) ) {
			next3_update_post_meta( $id, 'next3_optimizer_is_optimized', 1 );
		}
		return true;
	}

    private function optimization_action( $filepath,  $id = 0, $compression_level = null) {
		
		// if the file doens't exists.
		if ( ! file_exists( $filepath ) ) {
			return true;
		}
		
		//enable read/write permission
		chmod($filepath, 0777);
        
		//if compression level is set to None.
		if ( 0 == $compression_level ) {
			return true;
		}
        
		$status = $this->operation_media_optimize(
			$filepath,
			$compression_level
		);
        
		return $status;
	}

    private function operation_media_optimize( $filepath, $level ) {

		if( !function_exists('exif_imagetype')){
			$msg = __('Enable "php_exif.dll" from <code>php.ini</code> file. Open this file and remove ";" from <code>;extension=php_exif.dll</code>', 'next3-offload');
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
		}
		
		//$output_filepath = preg_replace( '~\.bak.(png|jpg|jpeg|gif)$~', '.$1', $filepath );
		$output_filepath = $filepath;
        
		$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
		if( function_exists('getimagesize')){
			$info = getimagesize($filepath);
			$mine_name = ($info['mime']) ?? '';
			if( !empty($mine_name) ){
				$mine_exp = explode('/', $mine_name);
				$ext = ($mine_exp[1]) ?? $ext;
			}
		}

		$quality = 100;
		$quality = ($level == 1) ? 25 : $quality;
		$quality = ($level == 2) ? 60 : $quality;
		$quality = ($level == 3) ? 85 : $quality;

		if (in_array($ext, ["png"])){
			$quality = ($level == 1) ? 9 : $quality;
			$quality = ($level == 2) ? 6 : $quality;
			$quality = ($level == 3) ? 3 : $quality;
		}
		$quality = apply_filters( 'next3_compress_quality', $quality );
		
		if (!in_array($ext, [ "jpg", "jpeg", "png", "gif"])){
			return self::action_compress_php($filepath, $output_filepath, $quality);  
		}

		// Get image type.
		if( function_exists('exif_imagetype')){
			$type = exif_imagetype( $filepath );
		} else {
			$type = $ext;
		}
		
		switch ( $type ) {
			case IMAGETYPE_GIF || 'gif':
				$placeholder = 'gifsicle %s --careful %s -o %s 2>&1';
				break;

			case IMAGETYPE_JPEG || 'jpg' || 'jpeg':
				//$placeholder = 'jpegoptim %s --stdout %s > %s';
				$placeholder = 'jpegoptim %1$s %3$s 2>&1';
				break;

			case IMAGETYPE_PNG || 'png':
				if ( filesize( $filepath ) > self::PNGS_SIZE_LIMIT ) {
					return true;
				}
				//$placeholder = 'pngquant %s %s --output %s';
				$placeholder = 'optipng %s %s -out=%s 2>&1';
				break;

			default:
				return true;
		}
        
		// Optimize the image.
		if( function_exists('exec')){
			exec(
				sprintf(
					$placeholder, // The command.
					$this->compression_level_map[ $type ][ $level ], // The compression level.
					$filepath, // Image path.
					$output_filepath // New Image path.
				),
				$output,
				$status
			);
		} else {
			$msg = __('Function: <code>exec()</code> could not found in webp.php file. Please contact with server team and enable the extension.', 'next3-offload');
        	next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
		}
		
		if( !function_exists('exec') || true === boolval( $status )){
			$status = self::action_compress_php($filepath, $output_filepath, $quality); 
		}
		return $status;
	}

	private static function action_compress_php($image, $output_filepath, $outputQuality = 100)
    {
        $extf = strtolower(pathinfo($image, PATHINFO_EXTENSION));
		$valid = ["bmp", "jpg", "jpeg", "png", "webp", 'gif'];
		
		if( function_exists('getimagesize')){
			$info = getimagesize($image);
			$mine_name = ($info['mime']) ?? '';
			if( !empty($mine_name) ){
				$mine_exp = explode('/', $mine_name);
				$extf = ($mine_exp[1]) ?? $extf;
			}
		} else {
			$info['mime'] = 'image/' . $extf;
		}
		
		if (!in_array($extf, $valid) ) { 
			return true; 
		}
		if (in_array($extf, ['jpg', 'jpeg']) ) { $extf = "jpeg"; }
		
		$fnf = "imagecreatefrom$extf";
      	$fnt = "image$extf";

		$isAlpha = false;
		
		if( function_exists($fnf) && function_exists($fnt)){
			
			// new code
			if ($info['mime'] == 'image/jpeg' || $info['mime'] == 'image/jpg'){
				$img = imagecreatefromjpeg($image);
			} else if ($isAlpha = $info['mime'] == 'image/gif') {
				$img = imagecreatefromgif($image);
			} else if ($isAlpha = $info['mime'] == 'image/png') {
				$img = imagecreatefrompng($image);
			} else if ($isAlpha = $info['mime'] == 'image/webp') {
				$img = imagecreatefromwebp($image);
			  } else {
				$img = @$fnf($image);
			}

			if( $img ){
				if ($isAlpha) {
					imagepalettetotruecolor($img);
					imagealphablending($img, true);
					imagesavealpha($img, true);
				} else{
					imagepalettetotruecolor($img);
				}
				
				if ($outputQuality !== 100) {
					$compressedImage = $fnt($img, $output_filepath, $outputQuality);
				} else { 
					$compressedImage = $fnt($img, $output_filepath);
				}

				imagedestroy($img);

				if ($compressedImage) {
					return "0";
				}else{
					return true;
				}
			}
		}else {
			$msg = __('Error: '.$fnf.'() or '.$fnt.'() not found.', 'next3-offload');
			next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
		}
		return true;
    }

    public function edit_media_action( $id ) {
		
        if ( ! isset( $_REQUEST['next3_compression_level'] ) ) {
			return $id;
		}

        if( next3_get_post_meta($id, '_next3_attached_url') === false){  
        
			$this->delete_media_action( $id );
			
			// Optimize the image.
			$metadata = next3_wp_get_attachment_metadata( $id, true);
			$this->upload_media_action($metadata, $id);
		}
		return $id;
	}

    public function edit_media_action_field( $form_fields, $post ) {
		$field_value = next3_get_post_meta( $post->ID, 'next3_optimizer_compression_level');
		if ( ! is_numeric( $field_value ) ) {
			$settings_options = next3_options();
            $field_value = ($settings_options['optimization']['compression_level']) ?? 0;
		}
		$html = '<select name="next3_compression_level">';
		$options = apply_filters('next3/selected/compression/level', next3_allowed_compression_level());
		foreach ( $options as $key => $value ) {
			$html .= '<option' . selected( $field_value, $key, false ) . ' value="' . $key . '">' . $value . '</option>';
		}
		$html .= '</select>';
		$form_fields['compression_level'] = array(
			'value' => $field_value ? intval( $field_value ) : '',
			'label' => __( 'Compression Level', 'next3-offload' ),
			'input' => 'html',
			'html'  => $html,
		);
		return $form_fields;
	}

    public function get_human_readable_size( $filepath ) {
		$size = filesize( $filepath );
		$units = array( 'B', 'kB', 'MB' );
		$step = 1024;
		$i = 0;

		while ( ( $size / $step ) > 0.9 ) {
			$size = $size / $step;
			$i++;
		}
		return round( $size, 2 ) . $units[ $i ];
	}

    public static function check_for_unoptimized_images( $type ) {

		$meta = array(
			'image' => array(
				'next3_optimizer_is_optimized',
				'next3_optimizer_optimization_failed',
			),
			'webp'  => array(
				'next3_optimizer_is_converted_to_webp',
				'next3_optimizer_webp_conversion_failed',
			),
		);

		$images = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => $meta[ $type ][0],
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => $meta[ $type ][1],
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		return count( $images );
	}

	public function restore_originals() {
		$basedir = self::get_uploads_dir();
		$result = true;
		if( function_exists('exec')){
			exec( "find $basedir -regextype posix-extended -type f -regex '.*bak.(png|jpg|jpeg|gif)$' -exec rename '.bak' '' {} \;", $output, $result );
		} else {
			$msg = __('Function: <code>exec()</code> could not found in webp.php file. Please contact with server team and enable the extension.', 'next3-offload');
        	next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
		}
		if ( true !== boolval( $result ) ) {
			$this->reset_images_filesize_meta();
			//$this->reset_image_optimization_status();
		}

		return $result;
	}
	public function reset_images_filesize_meta() {
		$images = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => 'next3_optimizer_original_filesize',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		if ( empty( $images ) ) {
			return;
		}

		foreach( $images as $image_id ) {
			$metadata = next3_wp_get_attachment_metadata( $image_id, true);
			$metadata['filesize'] = next3_get_post_meta( $image_id, 'next3_optimizer_original_filesize');
			wp_update_attachment_metadata( $image_id, $metadata );
		}
	}

	public function reset_image_optimization_status() {
		global $wpdb;
		$wpdb->query(
			"
				DELETE FROM $wpdb->postmeta
				WHERE `meta_key` = '" . $this->batch_skipped . "'
				OR `meta_key` = '" . $this->process_map['failed'] . "'
				OR `meta_key` = 'next3_optimizer_original_filesize'
			"
		);
	}

	public static function get_uploads_dir() {
		$upload_dir = wp_upload_dir();
		$base_dir = $upload_dir['basedir'];
		if ( defined( 'UPLOADS' ) ) {
			$base_dir = ABSPATH . UPLOADS;
		}
		return $base_dir;
	}

    public static function instance(){
		if (!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
	}
}