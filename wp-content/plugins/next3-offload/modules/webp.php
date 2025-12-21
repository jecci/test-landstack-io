<?php 
namespace Next3Offload\Modules;
defined( 'ABSPATH' ) || exit;

class Webp{
    private static $instance;

    const PNGS_SIZE_LIMIT = 1048576;

    public $options_map = array(
      'completed' => 'next3_optimizer_webp_conversion_completed',
      'status'    => 'next3_optimizer_webp_conversion_status',
      'stopped'   => 'next3_optimizer_webp_conversion_stopped',
    );

    public $non_optimized = 'next3_optimizer_total_non_converted_images';

    public $batch_skipped = 'next3_optimizer_is_converted_to_webp';

    public function init() {
      // webp action included to compatiblility.php
    }
    
    public function delete_webp_files() {
      $basedir = self::get_uploads_dir();

      $result = true;
      // delete all webp file
      if( function_exists('exec')){
        exec( "find $basedir -name '*.webp' -type f -print0 | xargs -L 500 -0 rm", $output, $result );
      } else {
        $msg = __('Function: <code>exec()</code> could not found in webp.php file. Please contact with server team and enable the extension.', 'next3-offload');
        next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
      }
      if ( true !== boolval( $result ) ) {
        $this->reset_image_optimization_status();
      }
      
      // back to orginal file
      if( function_exists('exec')){
        exec( "find $basedir -regextype posix-extended -type f -regex '.*bak.(png|jpg|jpeg|gif)$' -exec rename '.bak' '' {} \;", $output, $result );
      }
      return $result;
    }

    public function delete_media_action( $id, $only_backup = true ) {
      $main_image = next3_get_attached_file( $id, true);
      $metadata   = next3_wp_get_attachment_metadata( $id, true);
      $basename   = basename( $main_image );
      
      if(strpos($main_image, ".webp") === false){
          $main_image .= '.webp';
      }
      if ( !file_exists( $main_image ) ) {
          return;
      }
      // delete webp file
      @unlink( $main_image );
  
      if ( ! empty( $metadata['sizes'] ) ) {
        foreach ( $metadata['sizes'] as $size ) {
          // delete webp file
          $main_file_sub = str_replace( $basename, $size['file'], $main_image );
          if( !file_exists($main_file_sub)){
            continue;
          }
          @unlink( $main_file_sub );
        }
      }
      
      if( $only_backup ){
        next3_delete_post_meta( $id, 'next3_optimizer_is_converted_to_webp');
        next3_delete_post_meta( $id, 'next3_optimizer_is_converted_to_webp__failed');
      }
      return;
    }

    public function edit_media_action( $id ) {
      
      if( next3_get_post_meta($id, '_next3_attached_url') === false){  
        
        $this->delete_media_action( $id );

        // Optimize the image.
        $metadata = next3_wp_get_attachment_metadata( $id, true);
        $this->upload_media_action($metadata, $id);
      }
      return $id;
    }

    public function upload_media_action( $metadata, $id ) {
      
      $settings_options = next3_options();
      $copy_file = ($settings_options['storage']['copy_file']) ?? 'no';
      $remove_local = isset($settings_options['storage']['remove_local']) ? true : false;
      $compression_enable = ($settings_options['optimization']['compression']) ?? 'no';
      
      // check compress status 
     if( $compression_enable == 'yes'){
        next3_core()->optimizer_ins->optimize( $id, $metadata);
      }

      // convert webp format
      $this->optimize( $id, $metadata );

      $status_data = next3_service_status();
      $offload_status = ($status_data['offload']) ?? false;
      if( !$offload_status ){
        return $id;
      }
      
      // offload settings
      if( next3_get_post_meta($id, '_next3_attached_url') === false){     
        if( $copy_file == 'yes'){
            next3_core()->action_ins->copy_to_cloud('', $id, $remove_local);
        }
      }
      return $metadata;
    }

    public function optimize( $id, $metadata, $main_image = '') {
      // check overwrite webp
      $settings_options = next3_options();
      $overwrite_webp = ($settings_options['optimization']['overwrite_webp']) ?? 'no';
      if( true === next3_check_post_meta($id, 'next3_optimizer_is_converted_to_webp') && $overwrite_webp !== 'yes'){
        return true;
      }
      
      // overwrite file
      if( true === next3_check_post_meta($id, 'next3_optimizer_is_converted_to_webp') && $overwrite_webp === 'yes'){
        // back webp to orginal file
			  next3_core()->action_ins->restore_backup_action($id);
			  next3_core()->webp_ins->delete_media_action($id);
      }

      // chek main file
      if( empty($main_image) || !file_exists($main_image) ){
        $main_image = next3_get_attached_file( $id, true);
      }
      $basename = basename( $main_image );
     
      // overwrite image - back orginal file
      if(!is_readable($main_image) ){
        $old_main_image = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $main_image );
        if ( file_exists( $old_main_image ) ) {
          copy( $old_main_image, $main_image );
        }
      }

      // action webp
      $status = $this->generate_webp_action( $main_image, $id, $settings_options);
      
      if ( true === boolval( $status ) ) {
        $msg = __('Error: Your server does not support WebP. Please "PHP+GD" install or "extension=gd" enable from php.ini file.', 'next3-offload');
        next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
        return false;
      }
  
      if ( ! empty( $metadata['sizes'] ) ) {
        foreach ( $metadata['sizes'] as $size ) {
          $main_image_sub = str_replace( $basename, $size['file'], $main_image );
          
          // overwrite image
          if( !is_readable($main_image_sub) ){
            $old_main_image = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $main_image_sub );
            if ( file_exists( $old_main_image ) ) {
              copy( $old_main_image, $main_image_sub );
            }
          }
          $status = $this->generate_webp_action( $main_image_sub, $id, $settings_options);
        }
      }
      
      // Everything ran smoothly.
      if ( true !== boolval( $status ) ) {
        next3_update_post_meta( $id, 'next3_optimizer_is_converted_to_webp', 1 );
      }
      return true;
    }

    public function generate_webp_action( $filepath, $id = 0, $settings_options = [] ) {
      
      if( !function_exists('exif_imagetype')){
        $msg = __('Enable "php_exif.dll" from <code>php.ini</code> file. Open this file and remove";" from <code>;extension=php_exif.dll</code>', 'next3-offload');
        next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
      }

      if ( ! file_exists( $filepath ) ) {
        return true;
      }
      $overwrite_webp = ($settings_options['optimization']['overwrite_webp']) ?? 'no';
      //enable read/write permission
		  chmod($filepath, 0777);
      
      // overwrite
      if ( file_exists( $filepath . '.webp' ) ) {
        if( $overwrite_webp !== 'yes' ){
          return false;
        }
        @unlink( $filepath . '.webp' ); //phpcs:ignore
      }
      
      // backup
      $wepbackup_orginal = ($settings_options['optimization']['wepbackup_orginal']) ?? 'no';
      $backup_filepath = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $filepath );
      if (
        $wepbackup_orginal == 'yes' &&
        ! file_exists( $backup_filepath ) 
      ) {
        copy( $filepath, $backup_filepath );
        next3_update_post_meta( $id, 'next3_optimizer_orginal_file', 1 );
      }

      $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
      // check image types

      if( function_exists('getimagesize')){
        $info = getimagesize($filepath);
        $mine_name = ($info['mime']) ?? '';
        if( !empty($mine_name) ){
          $mine_exp = explode('/', $mine_name);
          $ext = ($mine_exp[1]) ?? $ext;
        }
      }else {
        $info['mime'] = 'image/' . $ext;
      }

      // check webp file type
      if ($info['mime'] == 'image/webp') {
        if(strpos($filepath, ".webp") === false){
          // rename file
          rename($filepath, $filepath . '.webp');
        }
        return false;
      }

      $mine_name = ($info['mime']) ?? '';
      if( !empty($mine_name) ){
        $mine_exp = explode('/', $mine_name);
        $ext = ($mine_exp[1]) ?? $ext;
      }

      if (!in_array($ext, [ "jpg", "jpeg", "png", "gif"])){
          $filepath_to = $filepath;
          if(strpos($filepath_to, ".webp") === false){
              $filepath_to .= '.webp';
          }
          return self::action_webp_php($filepath, $filepath_to); 
      }
      
      $quality      = apply_filters( 'next3_webp_quality', 100 );
      $quality_type = intval( apply_filters( 'next3_webp_quality_type', 0 ) );
  
      // Get image type.
      if( function_exists('exif_imagetype')){
        $type = exif_imagetype( $filepath );
      } else {
        $type = $ext;
      }

      switch ( $type ) {
        case IMAGETYPE_GIF || 'gif':
          $quality_type = 1 !== $quality_type ? '' : '-lossy';
          $placeholder  = 'gif2webp -q %1$s %2$s %3$s -o %3$s.webp 2>&1';
          break;
  
        case IMAGETYPE_JPEG || 'jpg' || 'jpeg':
          $quality_type = 1 !== $quality_type ? '' : '-lossless';
          $placeholder  = 'cwebp -q %1$s %2$s %3$s -o %3$s.webp 2>&1';
          break;
		
        case IMAGETYPE_PNG || 'png':
          if ( filesize( $filepath ) > self::PNGS_SIZE_LIMIT ) {
            return true;
          }
          $quality_type = 1 !== $quality_type ? '' : '-lossless';
          $placeholder  = 'cwebp -q %1$s %2$s %3$s -o %3$s.webp 2>&1';
          break;
  
        default:
          return true;
      }
      
      // Optimize the image.
      if( function_exists('exec')){
        exec(
          sprintf(
            $placeholder, // The command.
            $quality, // The quality %.
            $quality_type, // The quality type -lossless or -lossy.
            $filepath // Image path.
          ),
          $output,
          $status
        );
      }else {
        $msg = __('Function: <code>exec()</code> could not found in webp.php file. Please contact with server team and enable the extension.', 'next3-offload');
        next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
      }
      
      if( !function_exists('exec') || true === boolval( $status )){
        
        $filepath_to = $filepath;
        if(strpos($filepath_to, ".webp") === false){
            $filepath_to .= '.webp';
        }
       
        $status = self::action_webp_php($filepath, $filepath_to); 
      }
      return $status;
    }
    
    public static function action_webp_php($from, $to, $quality = null){
      $extf = strtolower(pathinfo($from, PATHINFO_EXTENSION));
      $extt = strtolower(pathinfo($to, PATHINFO_EXTENSION));
      $valid = ["bmp", "jpg", "jpeg", "png", "webp", 'gif'];

      if( function_exists('getimagesize')){
        $info = getimagesize($from);
        $mine_name = ($info['mime']) ?? '';
        if( !empty($mine_name) ){
          $mine_exp = explode('/', $mine_name);
          $extf = ($mine_exp[1]) ?? $extf;
        }
      } else {
        $info['mime'] = 'image/' . $extf;
      }

      if (!in_array($extf, $valid) || !in_array($extt, $valid)) { 
          return true; 
      }
      
      if (in_array($extf, ['jpg', 'jpeg']) ) { $extf = "jpeg"; }
      if (in_array($extt, ['jpg', 'jpeg']) ) { $extt = "jpeg"; }
      
      $fnf = "imagecreatefrom$extf";
      $fnt = "image$extt";

      // check png
      $isAlpha = false;
      
      if( function_exists($fnf) && function_exists($fnt)){
        // new code
        if ($info['mime'] == 'image/jpeg' || $info['mime'] == 'image/jpg'){
          $img = imagecreatefromjpeg($from);
        } else if ($isAlpha = $info['mime'] == 'image/gif') {
          $img = imagecreatefromgif($from);
        } else if ($isAlpha = $info['mime'] == 'image/png') {
          $img = imagecreatefrompng($from);
        } else if ($isAlpha = $info['mime'] == 'image/webp') {
          $img = imagecreatefromwebp($from);
        } else {
          $img = @$fnf($from);
        }
        if( $img ){
          
          if ($isAlpha) {
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);
          } else{
            imagepalettetotruecolor($img);
          }
          
          if ($quality !== null) {
              $fnt($img, $to, $quality); 
          } else { 
              $fnt($img, $to); 
          }
          if( imagedestroy($img) ){
            @unlink($from);
          }
          return "0";
        } 
      } else {
        $msg = __('Error: '.$fnf.'() or '.$fnt.'() not found.', 'next3-offload');
        next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
      }
      return true;
    }

    public function reset_image_optimization_status() {
      global $wpdb;
  
      $wpdb->query(
        "
          DELETE FROM $wpdb->postmeta
          WHERE `meta_key` = '" . $this->batch_skipped . "'
        "
      );
    }
  
    public static function get_uploads_dir() {
      // Get the uploads dir.
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