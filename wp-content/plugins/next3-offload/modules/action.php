<?php 
namespace Next3Offload\Modules;
defined( 'ABSPATH' ) || exit;


use \Next3Offload\Vendor\Minify\Minifier as Minify;

class Action{
    private static $instance;

    public function init() {    
        // ajax request
        if(current_user_can('manage_options') && is_admin()){
            // setup config
            add_action( 'wp_ajax_next3offload_config', [ $this, 'save_config'] );
            
            // config aws bucket
            add_action( 'wp_ajax_next3_exitbucket', [ $this, 'save_exitbucket'] );
            add_action( 'wp_ajax_next3_publicbucket', [ $this, 'next3_publicbucket'] );
            add_action( 'wp_ajax_next3_bucketremove_files', [ $this, 'next3_bucketremove_files'] );

            // setting options save
            add_action( 'wp_ajax_next3_options', [ $this, 'next3_options'] );
            
            // offload ajax
            add_action( 'wp_ajax_next3offload_start', [ $this, 'next3offload_start'] );
            add_action( 'wp_ajax_next3offload_process', [ $this, 'next3offload_process'] );

            // copy to could or move
            add_action( 'wp_ajax_next3copy_move', [ $this, 'next3copy_move'] );
            
        }
        add_filter( 'upload_mimes', [ $this, 'add_mime_type' ]);
        add_filter( 'wp_check_filetype_and_ext', [ $this, 'fix_mime_type_data' ], 75, 4 );

        if(current_user_can('manage_options')){
            // rest api
            add_action('init', [ $this, 'rest_api']);
            add_action( 'wp_ajax_next3_uploadsfiles', [ $this, 'next3_uploadsfiles'] );
        }
        
    }

    public function save_config(){
        $post = wp_slash( next3_sanitize($_POST) );
       
        if( !isset( $post['form_data'] )){
            $res['messege'] = esc_html__('Couldn\'t found any data.', 'next3-offload');
            wp_send_json_error(  $res );
        }

        wp_parse_str( next3_sanitize($_POST['form_data']), $formdata);

        $get = next3_credentials();
        

        $settings = ($formdata['next3setup']) ?? [];

        $step = '';
        $services = ( $settings['settings']['services'] ) ?? [];
        if( isset( $settings['settings']['services'] )){
            if( in_array('offload', $services)){
                $step = 'config';
            } else {
                $step = 'dashboard';
            }
            
        }
        if( empty($services)){
            $settings['settings']['services'] = ['offload'];
        } 
        
        // provider
        $settings_provider = ($settings['settings']['provider']) ?? '';
        if( !empty($settings_provider) ){
            //service
            $services = ($get['settings']['services']) ?? [];
            $settings['settings']['services'] = $services;

            // provider
            $settings_options = next3_options();
            $settings_options['delivery']['provider'] = $settings_provider;
            next3_update_option(next3_options_key(), $settings_options, true);
        }
        
        $settings = array_merge($get, $settings);

        next3_update_option(next3_credentials_key(), $settings, true);

        $res['redirect'] = next3_admin_url( 'admin.php?page=next3aws&step=' . $step);
        $res['messege'] = esc_html__('Successfully saved.', 'next3-offload');
        wp_send_json_success(  $res );
    }

    public function save_exitbucket(){
        $post = wp_slash( next3_sanitize($_POST) );
       
        if( !isset( $post['bucket_name'] )){
            $res['messege'] = esc_html__('Couldn\'t found any data.', 'next3-offload');
            wp_send_json_error(  $res );
        }

        $bucket_name = ($post['bucket_name']) ?? '';
        $bucket_type = ($post['bucket_type']) ?? 'settings';
        
        $credentials = next3_credentials();

        $provider = ($credentials['settings']['provider']) ?? 'default';
        $obj = next3_core()->provider_ins->load($provider)->access();
       
        if( !$obj || !$obj->check_configration()){
            $res['message'] = esc_html__('Error!! provider connection not established.', 'next3-offload');
            wp_send_json_error(  $res );
        }
        $region = ($post['region']) ?? '';

        if( $bucket_type == 'create'){
            $result = $obj->get_createBucket($bucket_name, $region);
            
            if( $result['status'] == false){
                $res['messege'] = ($result['msg']) ?? '';
                wp_send_json_error(  $res );
            }
        }

        if( in_array($provider, ['aws', 'digital', 'wasabi', 'objects']) ){
            $buckets = $obj->get_buckets();
            if( $buckets['status'] == false){
                $res['messege'] = esc_html__('Sorry! invalid your credentails.', 'next3-offload');
                wp_send_json_error(  $res );
            }
            $buckets = ($buckets['data']) ?? [];
            if( !in_array($bucket_name, $buckets)){
                $res['messege'] = "Sorry!! invalid bucket: $bucket_name";
                wp_send_json_error(  $res );
            }
        }
        $credentials['settings'][$provider]['default_bucket'] = $bucket_name;
        
        if( in_array($provider, ['aws', 'digital', 'wasabi', 'objects']) ){
            if( !empty($region) && strlen($region) > 3 ){
                $credentials['settings'][$provider]['default_region'] = $region;
            }else{
                $credentials['settings'][$provider]['default_region'] = $obj->get_bucket_location($bucket_name);
            }
            
            $status = $obj->public_access_blocked($bucket_name);
            
            if( $status == false){
                $file_permission = $obj->check_write_permission( $bucket_name );
                
                $credentials['settings'][$provider]['public_access'] = true;
                $credentials['settings'][$provider]['file_permission'] = $file_permission;
            }

        } else {
            $credentials['settings'][$provider]['default_region'] = $region;
            $file_permission = $obj->check_write_permission( $bucket_name, $region);
            
            if( $file_permission ){
                $credentials['settings'][$provider]['file_permission'] = $file_permission;
            }
        }
        
        next3_update_option(next3_credentials_key(), $credentials, true);

        $res['redirect'] = next3_admin_url( 'admin.php?page=next3aws#ntab=settings');
        if( $bucket_type == 'create'){
            $res['messege'] = esc_html__('Successfully created: '.$bucket_name , 'next3-offload');
        }else{
            $res['messege'] = esc_html__('Successfully saved info.', 'next3-offload');
        }
        wp_send_json_success(  $res );
    }
    
    public function next3_publicbucket(){
        $post = wp_slash( next3_sanitize($_POST) );
        $res = [];
        if( !isset( $post['bucket_name'] )){
            $res['messege'] = esc_html__('Couldn\'t found any data.', 'next3-offload');
            wp_send_json_error(  $res );
        }

        $bucket_name = ($post['bucket_name']) ?? '';

        $credentials = next3_credentials();
        $provider = ($credentials['settings']['provider']) ?? 'default';
        $bucket = ($credentials['settings'][$provider]['default_bucket']) ?? '';
        $bucket = !empty($bucket) ? $bucket : $bucket_name;

        $obj = next3_core()->provider_ins->load($provider)->access();

        if( !$obj || !$obj->check_configration()){
            $res['message'] = esc_html__('Error!! provider connection not established.', 'next3-offload');
            wp_send_json_error(  $res );
        }

        $buckets = $obj->get_buckets();
        if( $buckets['status'] == false){
            $res['messege'] = esc_html__('Sorry! invalid your credentails.', 'next3-offload');
            wp_send_json_error(  $res );
        }
        $buckets = ($buckets['data']) ?? [];
        if( !in_array($bucket, $buckets)){
            $res['messege'] = "Sorry, Invalid bucket: $bucket";
            wp_send_json_error(  $res );
        }
        
        $public_status = $obj->block_public_access($bucket);
        $status = $obj->public_access_blocked($bucket);
        if( $status == false){
            $file_permission = $obj->check_write_permission( $bucket );
            if( $file_permission ){
                $res['messege'] = esc_html__('Successfully disabled public access of bucket.', 'next3-offload');
            } else {
                $res['messege'] = esc_html__('Warning: please manually disable bucket public access to login the Cloud.', 'next3-offload'); 
            }
            $res['redirect'] = next3_admin_url( 'admin.php?page=next3aws#ntab=settings');

            $credentials['settings'][$provider]['public_access'] = true;
            $credentials['settings'][$provider]['file_permission'] = $file_permission;

            next3_update_option(next3_credentials_key(), $credentials, true);

        } else {
            $res['messege'] = esc_html__('Warning: please manually disable public access to login the Cloud.', 'next3-offload');
        }
        if ( is_string( $public_status ) ) {
            $res['messege'] = $public_status;
        } 
        wp_send_json_success(  $res );
    }

    public function next3_bucketremove_files(){
        $post = wp_slash( next3_sanitize($_POST) );
        $res = [];
        if( !isset( $post['bucket_name'] )){
            $res['messege'] = esc_html__('Couldn\'t found any data.', 'next3-offload');
            wp_send_json_error(  $res );
        }

        $bucket_name = ($post['bucket_name']) ?? '';

        $credentials = next3_credentials();
        $provider = ($credentials['settings']['provider']) ?? 'default';
        $bucket = ($credentials['settings'][$provider]['default_bucket']) ?? '';
        $bucket = !empty($bucket) ? $bucket : $bucket_name;

        $obj = next3_core()->provider_ins->load($provider)->access();

        if( !$obj || !$obj->check_configration()){
            $res['message'] = esc_html__('Error!! provider connection not established.', 'next3-offload');
            wp_send_json_error(  $res );
        }

        $buckets = $obj->get_buckets();
        if( $buckets['status'] == false){
            $res['messege'] = esc_html__('Sorry! invalid your credentails.', 'next3-offload');
            wp_send_json_error(  $res );
        }
        $buckets = ($buckets['data']) ?? [];
        if( !in_array($bucket, $buckets)){
            $res['messege'] = esc_html__('Sorry! invalid bucket: ', 'next3-offload') . $bucket;
            wp_send_json_error(  $res );
        }
        
        $result = $obj->get_deleteObjects($bucket, '', true);
        if( $result['status'] == false){
            $res['messege'] = ($result['msg']) ?? '';
            wp_send_json_error(  $res );
        }
        $res['messege'] = ($result['msg']) ?? '';
        wp_send_json_success(  $res );
    }

    public function next3_options(){
        $post = wp_slash( next3_sanitize($_POST) );
       
        if( !isset( $post['form_data'] )){
            wp_send_json_error( esc_html__('Couldn\'t found any data.', 'next3-offload') );
        }

        wp_parse_str( next3_sanitize($_POST['form_data']), $formdata);

        $type = ($post['datatype']) ?? 'storage';

        $settings = ($formdata['next3settings']) ?? [];
        
        $settings_options = next3_options();
        
        $status_data = next3_service_status();
        $develeper_status = ($status_data['develeper']) ?? false;
        $assets_status = ($status_data['assets']) ?? false;

        if( $type == 'optimization' && !$develeper_status){
            wp_send_json_error( esc_html__('Do not save. Need to activate business plan.', 'next3-offload') );
        }

        if( $type == 'assets' && !$assets_status){
            wp_send_json_error( esc_html__('Do not save. Need to activate developer plan.', 'next3-offload') );
        }

        $settings_options[$type] = ($settings[$type]) ?? [];

        next3_update_option(next3_options_key(), $settings_options, true);

        $res['messege'] = esc_html__('Successfully saved.', 'next3-offload');
        wp_send_json_success(  $res );
    }

    public function next3_uploadsfiles(){
        check_ajax_referer('next3_upload', 'security');
        if( NEXT3_SELF_MODE ){
            wp_send_json_error( esc_html__('Sorry! trial mode enabled, you can\'t access.' , 'next3-offload'));
        }
        $post = wp_slash($_POST);
        if( !isset( $_FILES ) ){
            wp_send_json_error( esc_html__('Couldn\'t found any data.' , 'next3-offload'));
        }
       
        if( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
            wp_send_json_error( esc_html__('Invalid submission.', 'next3-offload'));
        }

        $credentials = next3_credentials();
        $provider = ($credentials['settings']['provider']) ?? '';
        $prodiver_data = ($credentials['settings'][$provider]) ?? [];
        $default_bucket = ($prodiver_data['default_bucket']) ?? '';
        $default_region = ($prodiver_data['default_region']) ?? 'us-east-1';

        $settings_options = next3_options();
        $delivery_provider = ($settings_options['delivery']['provider']) ?? $provider;

        if( $provider == '' || $default_bucket == '' || $default_region == ''){
			wp_send_json_error( esc_html__('Please select your bucket, region or provider.', 'next3-offload') );
		}
        
        $id = isset($post['id']) ? $post['id'] : '';
        $folder = isset($post['keys']) ? stripslashes($post['keys']) : '';
		$refresh = isset($post['refresh']) ? $post['refresh'] : true;
        $folder = json_decode(stripslashes($folder), true);
        $id = empty( $id ) ? $default_bucket : $id;

        $obj = next3_core()->provider_ins->load($provider)->access();
        
        if( !$obj || !$obj->check_configration()){
            wp_send_json_error( esc_html__('Error!! provider connection not established.', 'next3-offload') );
        }

        $message = $obj->getStatus();
        if( $message != 'success'){
            wp_send_json_error( esc_html__('Sorry! could not connect with the Cloud API.', 'next3-offload') );
        }
        
        $file = isset($_FILES['file']) ? $_FILES['file'] : [];
        if(isset($file['name']) && !empty($file['name']) ){

            if( is_array($folder) && !empty($folder)){
                $folder = array_filter($folder, function($v){
                    return !empty($v) || $v === 0;
                });
                $folder = implode('/', str_replace([' ', '_nx_'], ['-', ''], $folder));
            }else{
                $folder = '/';
            }

            $result = [];
            if( 'POST' == $_SERVER['REQUEST_METHOD'] &&  isset($_FILES['file']['name'])) {
                $file = isset($_FILES['file']) ? $_FILES['file'] : [];
                $name = isset($file['name']) ? $file['name'] : [];
                if( empty($name) ){
                    $result['error'] = true;
                    $result['message'] = esc_html__('Please select any files.', 'next3-offload');
                    return $result;
                }
                $settings_options = next3_options();
                $wpmedia = ($settings_options['storage']['wpmedia_upload']) ?? 'no';

                $data = [];
                foreach( $name as $k=>$v){
                    $name = $v;
                    $tmp = ($file['tmp_name'][$k]) ?? '';
                    $type = ($file['type'][$k]) ?? '';
                    $size = ($file['size'][$k]) ?? '';
                    $path = rtrim($folder, '/'). '/' . $name;

                    $upload = $obj->putObject($id, $tmp, $path, $type);

                    if( isset($upload['status']) && $upload['status'] == true){
                        $url = ($upload['data']) ?? '';
                        $dta = [];
                        if( !empty($url) ){
                            $length = (strlen($name) > 15) ? (strlen($name) - 10) : 0;
                            if($length > 0){	
                                $name_files = substr_replace($name, '...', -$length, -10);
                            }else{
                                $name_files = $name;
                            }
                            $dta['type'] = 'file';
                            $dta['name'] = $name_files;
                            $icon = $this->getExtension($url);
                            if( $icon['type'] == 'image'){
                                $dta['htl'] = $url;
                                $dta['is_image'] = 1;
                            } else {
                                $dta['htl'] = $icon['icon'];
                            }
                            $dta['link'] = $url;
                            $dta['path_key'] = $path;
                            $dta['size'] = $size;
                            $dta['size_byte'] = $this->formatSizeUnits($size);
            
                            if($wpmedia == 'yes'){
                                $idsData = $this->insert_cloud_to_wpmedia($url, $path);
                                if( $idsData != 0 && $idsData != ''){
                                    next3_update_post_meta( $idsData, '_next3_source_type', 'from_cloud' );
                                    next3_update_post_meta( $idsData, '_next3_attached_file', $path );
                                    next3_update_post_meta( $idsData, '_next3_attached_url', $url );
                                    next3_update_post_meta( $idsData, '_next3_provider', $provider );
                                    next3_update_post_meta( $idsData, '_next3_provider_delivery', $delivery_provider );
                                    next3_update_post_meta( $idsData, '_next3_bucket', $default_bucket );
                                    next3_update_post_meta( $idsData, '_next3_region', $default_region );
                                    $dta['ids'] = $idsData;
                                }
                                
                            }
                            $data[] = $dta;
                        }
                    }
                }
                $result['files'] = $data;
                $result['message'] = esc_html__('Successfully uploaded.', 'next3-offload');
            }
            wp_send_json_success($result);
        }
        wp_send_json_error( 'Something wrong!!');
    }

    public function next3copy_move(){
        $post = wp_slash( next3_sanitize($_POST) );
        if( NEXT3_SELF_MODE ){
            $res['messege'] = esc_html__('Error: Sorry, trial mode enabled, you can\'t access.', 'next3-offload');
            wp_send_json_error(  $res );
        }
        if( !isset( $post['form_id'] ) || !isset( $post['form_type'] )){
            $res['messege'] = esc_html__('Couldn\'t found any data.', 'next3-offload');
            wp_send_json_error(  $res );
        }
       
        $id = ($post['form_id']) ?? 0;
        $type = ($post['form_type']) ?? 0;

        if( !next3_upload_status() && in_array($type, [ 'copy', 'move']) ){
            $res['messege'] = esc_html__('Don\'t have upload permission, please provide your valid credentails.', 'next3-offload');
            wp_send_json_error(  $res );
        }

        if( $id == 0 | $id == '' ){
            $res['messege'] = esc_html__('Error: Sorry, invalid media file.', 'next3-offload');
            wp_send_json_error(  $res );
        }

        $credentials = next3_credentials();
        $provider = ($credentials['settings']['provider']) ?? '';
        
        $prodiver_data = ($credentials['settings'][$provider]) ?? [];
        $default_bucket = ($prodiver_data['default_bucket']) ?? '';
        $default_region = ($prodiver_data['default_region']) ?? 'us-east-1';

        $settings_options = next3_options();
        $remove_local = isset($settings_options['storage']['remove_local']) ? true : false;
        $compression_enable = ($settings_options['optimization']['compression']) ?? 'no';
        $webp_enable = ($settings_options['optimization']['webp_enable']) ?? 'no';

        $res['messege'] = esc_html__('System error!', 'next3-offload');

        if( $type == 'copy'){
            if( next3_get_post_meta($id, '_next3_attached_url') === false){
                // offload process
                $result = next3_core()->action_ins->copy_to_cloud('', $id, $remove_local); 
                if( isset(  $result['success']) ){
                    $url = ($result['message']) ?? '';
                    $res['messege'] = esc_html__('Successfully uploaded.', 'next3-offload');
                    wp_send_json_success(  $res );
                } else{
                    $res['messege'] = ($result['message']) ?? '';
                }

            } else{
                $res['messege'] = esc_html__('Already uploaded.', 'next3-offload');
            }
        } else if( $type == 'move'){
            // move to local
            $result = next3_core()->action_ins->move_to_local($id); 
            if( isset(  $result['success']) ){
                $res['messege'] = ($result['message']) ?? '';
                wp_send_json_success(  $res );
            } else{
                $res['messege'] = ($result['message']) ?? '';
            }
        } else if( $type == 'compress'){
            // compress and webp
            if( next3_get_post_meta($id, '_next3_attached_file')){
                $res['messege'] = esc_html__('File already offloaded. You can compress file before offload.', 'next3-offload');
                wp_send_json_error(  $res );
            }

            if( next3_get_post_meta($id, 'next3_optimizer_is_optimized') === false){

                $metadata = next3_wp_get_attachment_metadata( $id, true);

                $status_data = next3_service_status();
                $develeper_status = ($status_data['develeper']) ?? false;
                if($compression_enable == 'yes' && $develeper_status){
                    $status = next3_core()->optimizer_ins->optimize( $id, $metadata);
                    if ( false === $status ) {
                        next3_update_post_meta( $id, 'next3_optimizer_optimization_failed', 1 );
                        $res['messege'] = esc_html__('Error: failed image compression operation.', 'next3-offload');
                    } else {
                        $res['messege'] = esc_html__('Successfully compressed.', 'next3-offload');
                    }
                } else {
                    $res['messege'] = esc_html__('Error: Enable "Image Compression" switch from "Optimization Settings".', 'next3-offload');
                }
            }else{
                // restore action
                next3_core()->action_ins->restore_backup_action($id);
                // delete backup files
                if( next3_get_post_meta($id, 'next3_optimizer_is_converted_to_webp') === false){
                    next3_core()->optimizer_ins->delete_media_action( $id );
                } else {
                    next3_delete_post_meta( $id, 'next3_optimizer_is_optimized');
                    next3_delete_post_meta( $id, 'next3_optimizer_optimization_failed');
                    next3_delete_post_meta( $id, 'next3_optimizer_original_filesize');
                    next3_delete_post_meta( $id, 'next3_optimizer_compression_level');
                }
                
                $res['messege'] = esc_html__('Restored orginal file.', 'next3-offload');
            }
        } else if( $type == 'webp'){
            if( next3_get_post_meta($id, '_next3_attached_file')){
                $res['messege'] = esc_html__('File already offloaded. You can WebP file before offload.', 'next3-offload');
                wp_send_json_error(  $res );
            }

            if( next3_get_post_meta($id, 'next3_optimizer_is_converted_to_webp') === false){
                $metadata = next3_wp_get_attachment_metadata( $id, true);

                $status_data = next3_service_status();
                $develeper_status = ($status_data['develeper']) ?? false;
                if($webp_enable == 'yes'  && $develeper_status){
                    $status_webp =  next3_core()->webp_ins->optimize( $id, $metadata);
                    if ( false == $status_webp ) {
                        next3_update_post_meta( $id, 'next3_optimizer_is_converted_to_webp__failed', 1 );
                        $res['messege'] = esc_html__('Error: failed webp convert operation.', 'next3-offload');
                    }else {
                        $res['messege'] = esc_html__('Successfully converted WebP.', 'next3-offload');
                    }
                } else {
                    $res['messege'] = esc_html__('Error: Enable "WebP Images" switch from "Optimization Settings".', 'next3-offload');
                }
            }else{
                // restore action
                next3_core()->action_ins->restore_backup_action($id);

                // delete webp files
                next3_core()->webp_ins->delete_media_action( $id );
                // delete backup files
                if( next3_get_post_meta($id, 'next3_optimizer_is_optimized') === false){
                    next3_core()->optimizer_ins->delete_media_action( $id );
                }

                $res['messege'] = esc_html__('Restored WebP file.', 'next3-offload');
            }
        }

        wp_send_json_error(  $res );
    }

    public function next3offload_start(){
        global $wpdb;
        $source_type = 'media-library';

        $post = wp_slash( next3_sanitize($_POST) );

        $res = [];
        
        if( NEXT3_SELF_MODE ){
            $res['messege'] = esc_html__('Error: Sorry, trial mode enabled, you can\'t access.', 'next3-offload');
            wp_send_json_error(  $res );
        }
       
       
        $key = '_next3_offload_data';
        $type = ($post['form_type']) ?? '';

        $site = next3_get_current_blogs();

        $res['start_again'] = false;
        $res['start_type'] = $type;

        if( !next3_upload_status() && in_array($type, apply_filters('next3/upload/status', [ 'offload', 'unoffload', 'clean', 'wpoffload', 'styles', 'scripts', 'cloudto', 'localto']))){
            $res['messege'] = esc_html__('Don\'t have upload permission, please provide your valid credentails.', 'next3-offload');
            wp_send_json_error(  $res );
        }

        $res['messege'] = esc_html__('System error!', 'next3-offload');

        if( in_array($type, [ 'pause', 'cancel', 'resume']) ){

            $offload_data = next3_get_option($key, []);

            $res['start_type'] = ($offload_data['type']) ?? ''; 
            
            $offload_store = next3_core()->action_ins->get_offload_count();
            $off_per = ($offload_store['offload_per']) ?? 0;
            $unoffload_per = ($offload_store['unoffload_per']) ?? 0;
            $clean_per = ($offload_store['clean_per']) ?? 0;
            $wpoffload_per = ($offload_store['wpoffload_per']) ?? 0;
            $wpoffload = ($offload_store['wpoffload']) ?? 0;
            $wpoffload_done = ($offload_store['wpoffload_done']) ?? 0;
            $css_per = ($offload_store[ $res['start_type'] . '_per']) ?? 0;
            $total_css = ($offload_store['total_' . $res['start_type']]) ?? 0;
            $total_css_done = ($offload_store['total_' . $res['start_type'] . '_done']) ?? 0;

            $total_optimize = ($offload_store['total_optimize']) ?? 0;
            $total_webp_done = ($offload_store['total_webp_done']) ?? 0;
            $total_compress_done = ($offload_store['total_compress_done']) ?? 0;
            $webp_per = ($offload_store['webp_per']) ?? 0;
            $compress_per = ($offload_store['compress_per']) ?? 0;
            $total_backup = ($offload_store['total_backup']) ?? 0;

            $cloud_per = ($offload_store['cloudto_per']) ?? 0;
            $localto_per = ($offload_store['localto_per']) ?? 0;

            if( $type == 'pause') {
                $offload_data['status'] = 'pause';
                next3_update_option( $key, $offload_data);
                
                if( $res['start_type'] == 'offload'){
                    $res['messege'] = __( $unoffload_per .'% Local files are offloading process, Paused!', 'next3-offload');
                } else if( $res['start_type'] == 'clean' ){
                    $res['messege'] = __( $off_per .'% Offloaded files are removing process from local storage, Paused!', 'next3-offload');
                } else if( $res['start_type'] == 'wpoffload' ){
                    $res['messege'] = __( number_format($wpoffload - $wpoffload_done) .' Files are migration process to "Next3 Offload", Paused!', 'next3-offload');
                } else if( in_array($res['start_type'], [ 'styles', 'scripts'])){
                    $res['messege'] = __( number_format($total_css - $total_css_done) .' '. ucfirst($res['start_type']) .' files offloading process, Paused!', 'next3-offload');
                } else if( $res['start_type'] == 'compress' ){
                    $res['messege'] = __( 'Compress: '. number_format($total_optimize - $total_compress_done)  .' and WebP: '. number_format($total_optimize - $total_webp_done) .' Local files are optimizing process, Paused!', 'next3-offload');
                } else if( $res['start_type'] == 'cloudto'){
                    $res['messege'] = __( $cloud_per .'% Files are coping into current provider, Paused!', 'next3-offload');
                } else if( $res['start_type'] == 'localto'){
                    $res['messege'] = __( $localto_per .'% Files are sync process, Paused!', 'next3-offload');
                } else if( $res['start_type'] == 'backupre'){
                    $res['messege'] = __( number_format($total_backup) .' Backup files restore process, Paused!', 'next3-offload');
                } else if( $res['start_type'] == 'backude'){
                    $res['messege'] = __( number_format($total_backup) .' Backup files delete process, Paused!', 'next3-offload');
                } else {
                    $res['messege'] = __( $off_per .'% Files are moving process in local storage, Paused!', 'next3-offload');
                }
                
            } else if( $type == 'resume') {
                $offload_data['status'] = 'start';
                next3_update_option( $key, $offload_data);

                $res['start_again'] = true;
                $res['start'] = ($offload_data['start']) ?? 0;
            
                if( $res['start_type'] == 'offload'){
                    $res['messege'] = __( $unoffload_per .'% Local files are offloading process, Started!', 'next3-offload');
                } else if( $res['start_type'] == 'clean' ){
                    $res['messege'] = __( $off_per .'% Offloaded files are removing process from local storage, Started!', 'next3-offload');
                } else if( $res['start_type'] == 'wpoffload' ){
                    $res['messege'] = __( ($wpoffload - $wpoffload_done) .' Files are migration process to "Next3 Offload", Started!', 'next3-offload');
                } else if( in_array($res['start_type'], [ 'styles', 'scripts'])){
                    $res['messege'] = __( number_format($total_css - $total_css_done) .' '. ucfirst($res['start_type']) .' files offloading process, Started!', 'next3-offload');
                } else if( $res['start_type'] == 'compress' ){
                    $res['messege'] = __( 'Compress: '. number_format($total_optimize - $total_compress_done)  .' and WebP: '. number_format($total_optimize - $total_webp_done) .' Local files are optimizing process, Started!', 'next3-offload');
                } else if( $res['start_type'] == 'cloudto'){
                    $res['messege'] = __( $cloud_per .'% Files are coping into current provider, Started!', 'next3-offload');
                } else if( $res['start_type'] == 'localto'){
                    $res['messege'] = __( $localto_per .'% Files are sync process, Started!', 'next3-offload');
                } else if( $res['start_type'] == 'backupre'){
                    $res['messege'] = __( number_format($total_backup) .' Backup files restore process, Started!', 'next3-offload');
                } else if( $res['start_type'] == 'backude'){
                    $res['messege'] = __( number_format($total_backup) .' Backup files delete process, Started!', 'next3-offload');
                } else {
                    $res['messege'] = __( $off_per .'% Files are moving process in local storage, Started!', 'next3-offload');
                }
                
            } else{
                next3_update_option( $key , []);
                next3_delete_option( $key );

                $res['start_again'] = true;
                
                if( $res['start_type'] == 'offload'){
                    $res['messege'] = __( $unoffload_per .'% Local files are offloading process, Canceled!', 'next3-offload');
                } else if( $res['start_type'] == 'clean' ){
                    $res['messege'] = __( $off_per .'% Offloaded files are removing process from local storage, Canceled!', 'next3-offload');
                } else if( $res['start_type'] == 'wpoffload' ){
                    $res['messege'] = __( number_format($wpoffload - $wpoffload_done) .' Files are migration process to "Next3 Offload", Canceled!', 'next3-offload');
                } else if( in_array($res['start_type'], [ 'styles', 'scripts'])){
                    $res['messege'] = __( number_format($total_css - $total_css_done) .' '. ucfirst($res['start_type']) .' files offloading process, Canceled!', 'next3-offload');
                } else if( $res['start_type'] == 'compress' ){
                    $res['messege'] = __( 'Compress: '. number_format($total_optimize - $total_compress_done)  .' and WebP: '. number_format($total_optimize - $total_webp_done) .' Local files are optimizing process, Canceled!', 'next3-offload');
                } else if( $res['start_type'] == 'cloudto'){
                    $res['messege'] = __( $cloud_per .'% Files are coping into current provider, Canceled!', 'next3-offload');
                } else if( $res['start_type'] == 'localto'){
                    $res['messege'] = __( $localto_per .'% Files are sync process, Canceled!', 'next3-offload');
                } else if( $res['start_type'] == 'backupre'){
                    $res['messege'] = __( number_format($total_backup) .' Backup files restore process, Canceled!', 'next3-offload');
                } else if( $res['start_type'] == 'backude'){
                    $res['messege'] = __( number_format($total_backup) .' Backup files delete process, Canceled!', 'next3-offload');
                } else {
                    $res['messege'] = __( $off_per .'% Files are moving process in local storage, Canceled!', 'next3-offload');
                }
            }
            wp_send_json_error(  $res ); 
        }

        if( !in_array($type, apply_filters('next3/upload/operation', [ 'offload', 'unoffload', 'clean', 'wpoffload', 'styles', 'scripts', 'compress', 'cloudto', 'localto', 'backupre', 'backude'])) ){
            $res['messege'] = __('Error: Sorry, invalid type of operation.', 'next3-offload');
            wp_send_json_error(  $res );
        }
        
        $status_data = next3_service_status();
        $develeper_status = ($status_data['develeper']) ?? false;
        $assets_status = ($status_data['assets']) ?? false;

        if( in_array($type, apply_filters('next3/upload/status/developer', [ 'compress', 'cloudto', 'backupre', 'backude'])) && !$develeper_status){
            $res['messege'] = __('Do not allow. Need to activate business plan.', 'next3-offload');
            wp_send_json_error(  $res );
        }

        if( in_array($type, apply_filters('next3/upload/status/assets', [ 'styles', 'scripts', 'localto'])) && !$assets_status){
            $res['messege'] = __('Do not allow. Need to activate developer plan.', 'next3-offload');
            wp_send_json_error(  $res );
        }

        $settings_options = next3_options();
        $perpage = ($settings_options['storage']['offload_limit']) ?? -1;
        $paged = ($settings_options['storage']['offload_paged']) ?? 1;

        $paged = ($post['paged']) ?? $paged;

        if( count( $site ) > 1 && $perpage > 0){
            $perpage = round( $perpage / count($site) );
        }

        if( in_array($type, [ 'backupre', 'backude'])){
            $paged = 1;
            $perpage = -1;
        }
        // assets offload
        if( in_array($type, [ 'styles', 'scripts']) ){
            $post = [];
            $total = 0;

            // css
            $exclude_css = ($settings_options['assets']['exclude_css']) ?? [];
            $overwrite_css = ($settings_options['assets']['overwrite_css']) ?? 'no';
            if( $type == 'scripts'){
                $exclude_css = ($settings_options['assets']['exclude_js']) ?? [];
                $overwrite_css = ($settings_options['assets']['overwrite_js']) ?? 'no';
            }
            $all_css_files = next3_exclude_css_list($type, false);

            $offload_css = next3_get_option('next3_offload_' . $type , []);

            if( !empty($exclude_css) && is_array($exclude_css) ){
                foreach($exclude_css as $v){
                    if( array_key_exists($v, $all_css_files) ){
                        unset( $all_css_files[ $v ]);
                    }
                }
            }
            if($overwrite_css != 'yes'){
                $all_css_files =  array_diff_key($all_css_files, $offload_css);
            }

            $all_css_files = array_keys($all_css_files);
            $total_css = count( $all_css_files );

            if(is_array($all_css_files) && !empty($all_css_files)){
                
                $res['messege'] = $msg;
                $res['total'] = $total_css;
                $res['start'] = 0;
                $res['persent'] = floor(( 0 * 100) / $total_css);
                $res['txt'] = $res['persent'] . '% (0/'.$total_css.')';

                $offload_data['post'] = $all_css_files;
                $offload_data['total'] = $total_css;
                $offload_data['start'] = 0;
                $offload_data['status'] = 'start'; // pause
                $offload_data['type'] = $type; // pause

                next3_update_option( $key , $offload_data);

                wp_send_json_success(  $res );

            }else{
                $res['messege'] = esc_html__('Error: Sorry, could not found '. $type .' files.', 'next3-offload');
                $res['total'] = 0;
                $res['paged'] = $paged;
            }

            next3_delete_option( $key );
        
            wp_send_json_error(  $res );
        }

        
        // end assets offload
        $args = [
            'post_status' => 'inherit',
            'orderby'     => 'DESC',
            'order'       => 'ID',
        ];
        $args['posts_per_page'] = $perpage;
        $args['paged'] = $paged;
        $args['post_type'] = 'attachment';

        $match = '';

        $msg = __('Start offloading..', 'next3-offload');
        if($type == 'offload'){
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key'     => '_next3_attached_file',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_next3_attached_url',
                    'compare' => 'NOT EXISTS',
                )
            );
        } else if( $type == 'unoffload' ){
            $msg = __('Start moving...', 'next3-offload');
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key'     => '_next3_attached_file',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => '_next3_attached_url',
                    'compare' => 'EXISTS',
                )
            );
        
        } else if( $type == 'clean' ){
            $msg = __('Start cleaning...', 'next3-offload');
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key'     => '_next3_attached_file',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => '_next3_attached_url',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => '_next3_clean_status',
                    'compare' => 'NOT EXISTS',
                )
            );
        } else if( $type == 'wpoffload' ){
            $msg = __('Start restore...', 'next3-offload');
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key'     => '_next3_attached_file',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_next3_attached_url',
                    'compare' => 'NOT EXISTS',
                )
            );
        
        } else if( $type == 'compress' ){
            $msg = __('Start compress...', 'next3-offload');
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key'     => '_next3_attached_file',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_next3_attached_url',
                    'compare' => 'NOT EXISTS',
                )
            );
        
        } else if( $type == 'cloudto'){
            $credentials = next3_credentials();
            $provider = ($credentials['settings']['provider']) ?? '';

            $msg = __('Start coping...', 'next3-offload');
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key'     => '_next3_attached_file',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => '_next3_provider',
                    'value' => $provider,
                    'compare' => '!='
                )
            );
        } else if( $type == 'localto'){
            $msg = __('Start sync local and cloud...', 'next3-offload');
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key'     => '_next3_attached_file',
                    'compare' => 'NOT EXISTS',
                )
            );

            $match = next3_local_cloud_sync('cloud', 'path_new', false);
        } else if( $type == 'backupre'){
            $msg = __('Start backup restore...', 'next3-offload');
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'next3_optimizer_orginal_file',
                    'compare' => 'EXISTS',
                )
            );
        }else if( $type == 'backude'){
            $msg = __('Start backup delete...', 'next3-offload');
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'next3_optimizer_orginal_file',
                    'compare' => 'EXISTS',
                )
            );
        }
        
        // query for all attachment
        $total = 0;
        $post = [];
        foreach($site as $blog_id){
            if( $blog_id != 0){
                switch_to_blog($blog_id);
            }

            $query = new \WP_Query( $args );
            $count = $query->found_posts;

            if( $count > 0){
                
                if ( $query->have_posts() ) {
                    while ( $query->have_posts() ) {
                        $query->the_post();
                        $post_id = get_the_ID();
                        if( in_array($type, [ 'offload', 'clean']) ){
                           
                            $source_file = next3_get_attached_file( $post_id, true);
                            $source_file_back = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $source_file );
                            
                            //enable read/write permission
                            chmod($source_file, 0777);
        
                            $webp_status = false;
                            if( true === next3_check_post_meta($post_id, 'next3_optimizer_is_converted_to_webp') ){
                                if(strpos($source_file, ".webp") === false){
                                    $source_file .= '.webp';
                                }
                                $webp_status = true;
                            }
                            if( is_readable($source_file) || is_readable($source_file_back) ){
                                $post[] = $post_id . '__' . $blog_id;
                                $total++;
                            }
                        } else if( in_array($type, [ 'wpoffload'])){
                            if ( class_exists( '\WP_Offload_Media_Autoloader' ) ) {
                                $sql = $wpdb->prepare( "SELECT * FROM " . next3_wp_offload_table() . " WHERE source_type = %s AND source_id = %d", $source_type, $post_id );
        
                                $object = $wpdb->get_row( $sql );
                                if ( !empty( $object ) && isset($object->provider)) {
                                    $post[] = $post_id . '__' . $blog_id;
                                    $total++;
                                }
                            }
                            
                        } else if( in_array($type, [ 'compress'])){
                            $source_file = next3_get_attached_file( $post_id, true);
                            $source_file_back = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $source_file );
                            //enable read/write permission
                            chmod($source_file, 0777);
        
                            if( is_readable($source_file) || is_readable($source_file_back) ){
                                $post[] = $post_id . '__' . $blog_id;
                                $total++;
                            }
                            
                        } else if( in_array($type, [ 'localto'])){
                            $attached_file = next3_get_post_meta($post_id, '_wp_attached_file');
                            $attached_file_webp = $attached_file . '.webp';
                           
                            if( is_array($match) && (in_array($attached_file, $match) || in_array($attached_file_webp, $match)) ){
                                $post[] = $post_id . '__' . $blog_id;
                                $total++;
                            }
                            
                        }else if( in_array($type, [ 'backupre', 'backude'])){
                            $source_file = next3_get_attached_file( $post_id, true);
                            $source_file_back = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $source_file );
                            //enable read/write permission
                            chmod($source_file_back, 0777);
        
                            if( is_readable($source_file_back) || is_readable($source_file_back) ){
                                $post[] = $post_id . '__' . $blog_id;
                                $total++;
                            }
                            
                        } else{
                            $post[] = $post_id . '__' . $blog_id;
                            $total++;
                        }
        
                    }
                    
                    
                }
            }


            if( $blog_id != 0){
                wp_reset_postdata();
                restore_current_blog();
            }
        }

        if($total > 0){
                        
            $res['messege'] = $msg;
            $res['total'] = $total;
            $res['start'] = 0;
            $res['persent'] = floor(( 0 * 100) / $total);
            $res['txt'] = $res['persent'] . '% (0/'.$total.')';

            $offload_data['sites'] = $post;
            $offload_data['post'] = $post;
            $offload_data['total'] = $total;
            $offload_data['start'] = 0;
            $offload_data['status'] = 'start'; // pause
            $offload_data['type'] = $type; // pause

            next3_update_option( $key , $offload_data);

            wp_send_json_success( $res );

        }else{
           
            $res['messege'] = __('Error: Sorry! could not found any files in local storage.', 'next3-offload');
            $res['total'] = 0;
            $res['paged'] = $paged;

            // update paged setting
            $settings_options['storage']['offload_paged'] = $paged;
            next3_update_option(next3_options_key(), $settings_options, true);
        }

        // delete query settings
        next3_update_option( $key , []);
        next3_delete_option( $key , []);

       
        wp_send_json_error(  $res );
    }

    public function next3offload_process(){

        global $wpdb;
        $source_type = 'media-library';

        $res = [];

        $post = wp_slash( next3_sanitize($_POST) );
        $type = ($post['form_type']) ?? '';
        $step = ($post['step']) ?? 0;

        if( NEXT3_SELF_MODE ){
            $res['messege'] = __('Error: Sorry, trial mode enabled, you can\'t access.', 'next3-offload');
            wp_send_json_error(  $res );
        }
       

        $key = '_next3_offload_data';
        
        $offload_data = next3_get_option($key, []);

        $status_offload = ($offload_data['status']) ?? 'push';
        $total_offload = ($offload_data['total']) ?? 0;
        $start_offload = ($offload_data['start']) ?? 0;
        $type_offload = ($offload_data['type']) ?? $type;
        $post_data = ($offload_data['post']) ?? [];

        $offload_store = next3_core()->action_ins->get_offload_count();
        $off_per = ($offload_store['offload_per']) ?? 0;
        $unoffload_per = ($offload_store['unoffload_per']) ?? 0;
        $clean_per = ($offload_store['clean_per']) ?? 0;
        $wpoffload_per = ($offload_store['wpoffload_per']) ?? 0;
        $wpoffload = ($offload_store['wpoffload']) ?? 0;
        $wpoffload_done = ($offload_store['wpoffload_done']) ?? 0;
        $css_per = ($offload_store[ $type_offload . '_per']) ?? 0;
        $total_css = ($offload_store['total_' . $type_offload]) ?? 0;
        $total_css_done = ($offload_store['total_' . $type_offload . '_done']) ?? 0;

        $total_optimize = ($offload_store['total_optimize']) ?? 0;
        $total_webp_done = ($offload_store['total_webp_done']) ?? 0;
        $total_compress_done = ($offload_store['total_compress_done']) ?? 0;
        $webp_per = ($offload_store['webp_per']) ?? 0;
        $compress_per = ($offload_store['compress_per']) ?? 0;
        $cloud_per = ($offload_store['cloudto_per']) ?? 0;
        $localto_per = ($offload_store['localto_per']) ?? 0;
        $total_backup = ($offload_store['total_backup']) ?? 0;

        if( !next3_upload_status() && in_array($type_offload, apply_filters('next3/upload/status', [ 'offload', 'unoffload', 'clean', 'wpoffload', 'styles', 'scripts', 'cloudto', 'localto']) )){
            $res['messege'] = __('Don\'t have upload permission, please provide your valid credentails.', 'next3-offload');
            wp_send_json_error(  $res );
        }
        
        if( empty($offload_data) ){
            if( $type_offload == 'offload'){
                $res['messege'] = __( $off_per .'% Media files has been offloaded', 'next3-offload');
            } else if( $type_offload == 'clean'){
                $res['messege'] = __( $clean_per .'% Offloaded files removed from local stroage', 'next3-offload');
            } else if( $type_offload == 'wpoffload'){
                $res['messege'] = __( $wpoffload_per .'% Files have been migrated', 'next3-offload');
            } else if( in_array($type_offload, [ 'styles', 'scripts'])){
                $res['messege'] = __( $css_per .'% '. ucfirst($type_offload) .' files has been offloaded', 'next3-offload');
            } else if( $type_offload == 'compress'){
                $res['messege'] = __( 'Compress: '.$compress_per .'% and WebP: '.$webp_per.'% files has been optimized', 'next3-offload');
            } else if( $type_offload == 'cloudto'){
                $res['messege'] = __( $cloud_per .'% Files has been found in other cloud', 'next3-offload');
            } else if( $type_offload == 'localto'){
                $res['messege'] = __( $localto_per .'% Files has been sync between local and cloud', 'next3-offload');
            } else if( $type_offload == 'backupre'){
                $res['messege'] = __( number_format($total_backup) .' Backup files you will restore in local', 'next3-offload');
            } else if( $type_offload == 'backude'){
                $res['messege'] = __( number_format($total_backup) .' Backup files you will delete in local', 'next3-offload');
            } else{
                $res['messege'] = __( $unoffload_per .'% Media files has been local stroage', 'next3-offload');
            }

            $res['total'] = 0;
            $res['start'] = 0;
            $res['persent'] = 0;
            $res['txt'] = '';
            wp_send_json_success(  $res );
        }
       
        if( $status_offload == 'pause'){
            
            if( $type_offload == 'offload'){
                $res['messege'] = __( $unoffload_per .'% Local files are offloading process, Paused!', 'next3-offload');
            } else if( $type_offload == 'clean' ){
                $res['messege'] = __( $off_per .'% Offloaded files are removing process from local storage, Paused!', 'next3-offload');
            } else if( $type_offload == 'wpoffload' ){
                $res['messege'] = __( number_format($wpoffload - $wpoffload_done) .' Files are migration process to "Next3 Offload", Paused!', 'next3-offload');
            } else if( in_array($type_offload, [ 'styles', 'scripts'])){
                $res['messege'] = __( number_format($total_css - $total_css_done) .' '. ucfirst($type_offload) .' files offloading process, Paused!', 'next3-offload');
            } else if( $type_offload == 'compress' ){
                $res['messege'] = __( 'Compress: '. number_format($total_optimize - $total_compress_done)  .' and WebP: '. number_format($total_optimize - $total_webp_done) .' Local files are optimizing process, Paused!', 'next3-offload');
            } else if( $type_offload == 'cloudto'){
                $res['messege'] = __( $cloud_per .'% Files are coping into current provider, Paused!', 'next3-offload');
            } else if( $type_offload == 'localto'){
                $res['messege'] = __( $localto_per .'% Files are sync process, Paused!', 'next3-offload');
            } else if( $type_offload == 'backupre'){
                $res['messege'] = __( number_format($total_backup) .' Backup files restore process, Paused!', 'next3-offload');
            } else if( $type_offload == 'backude'){
                $res['messege'] = __( number_format($total_backup) .' Backup files delete process, Paused!', 'next3-offload');
            } else {
                $res['messege'] = __( $off_per .'% Files are moving process in local storage, Paused!', 'next3-offload');
            }

            $res['total'] = $total_offload;
            $res['start'] = $start_offload;
            $start_offload_per = $start_offload + 1;
            $res['persent'] = floor(( $start_offload_per * 100) /  $total_offload);
            $res['txt'] = $res['persent'] . '% ('.$start_offload_per.'/'.  $total_offload .')';
            wp_send_json_success(  $res );
        }

        $post_val = ($post_data[$step]) ?? '0__0';
        $post_exp = explode('__', $post_val);

        $post_id = ($post_exp[0]) ?? 0;
        $blog_id = ($post_exp[1]) ?? 0;

        if( $blog_id != 0){
            switch_to_blog($blog_id);
        }
        
        if( $type_offload == 'offload' && !empty($post_data)){
            $settings_options = next3_options();
            $remove_local = isset($settings_options['storage']['remove_local']) ? true : false;
            
            if( next3_get_post_meta($post_id, '_next3_attached_file') === false){
                $result = next3_core()->action_ins->copy_to_cloud('', $post_id, $remove_local); // remove status
                if( isset(  $result['success']) ){
                    $url = ($result['message']) ?? ''; 
                }
            }
        } else if( $type_offload == 'unoffload' && !empty($post_data) ){

            $result = next3_core()->action_ins->move_to_local($post_id); 
            if( isset(  $result['success']) ){

            }

        } else if($type_offload == 'clean' && !empty($post_data) ){
            
            $result = next3_core()->action_ins->clean_from_local($post_id); 
            if( isset(  $result['success']) ){

            }

        } else if($type_offload == 'wpoffload' && !empty($post_data) ){

            $result = next3_core()->action_ins->copy_wpmedia_to_next3($post_id); 
            if( isset(  $result['success']) ){

            }

        } else if( in_array($type_offload, [ 'styles', 'scripts']) && !empty($post_data) ){
            
            $get_files = next3_exclude_css_list($type_offload, false, $post_id);

            $offload_css = next3_get_option('next3_offload_' . $type_offload , []);

            if( !empty($get_files) && isset($get_files['value'])){
                $handler = ($get_files['value']) ?? '';
                $title = ($get_files['title']) ?? '';
                $group = ($get_files['group']) ?? '';

                if( !empty($handler) && !empty($title) ){
                    
                    $dir_name = get_home_path();
                    if( $group == 'plugin'){
                        $dir_name = WP_CONTENT_DIR;
                    }
                    
                    $total_path = $dir_name . $title;
                    $total_path = file_exists( $total_path ) ? $total_path : $title;
                    $data_upload = [
                        'source_file' => $total_path,
                        'key' => $title,
                        'handler' => $handler,
                        'group' => $group,
                        'type_offload' => $type_offload,
                    ];
                   
                    if( file_exists($total_path) && is_readable($total_path)){
                        $remove = false;
                        if( array_key_exists($handler, $offload_css)){
                            $remove = true;
                        }
                        $result = next3_core()->action_ins->assets_copy_to_cloud($data_upload, $remove); // remove status
                        if( isset(  $result['success']) ){
                            $mass = ($result['message']) ?? ''; 
                        }
                        
                    }
                }
            }

        } else if($type_offload == 'compress' && !empty($post_data) ){
            
            $result = next3_core()->action_ins->compress_media_to_local($post_id); 
            if( isset(  $result['success']) ){

            }
            
        } else if($type_offload == 'cloudto' && !empty($post_data) ){
            $result = next3_core()->action_ins->cloud_to_cloud_copy($post_id); 
            if( isset(  $result['success']) ){

            }
        } else if($type_offload == 'localto' && !empty($post_data) ){
            
            $result = next3_core()->action_ins->sync_between_cloud_local($post_id); 
            if( isset(  $result['success']) ){
                
            }
        }else if($type_offload == 'backupre' && !empty($post_data) ){
            
            // restore action
            next3_core()->action_ins->restore_backup_action($post_id);

            // delete webp files
            next3_core()->webp_ins->delete_media_action( $post_id );
            // delete backup files
            next3_core()->optimizer_ins->delete_media_action( $post_id );
            
            next3_delete_post_meta( $post_id, 'next3_optimizer_orginal_file' );

        } else if($type_offload == 'backude' && !empty($post_data) ){
            // delete backup files
            next3_core()->optimizer_ins->delete_media_action( $post_id, false);
            next3_delete_post_meta( $post_id, 'next3_optimizer_orginal_file' );

        }
        

        if( $blog_id != 0){
            wp_reset_postdata();
            restore_current_blog();
        }

        $res['total'] = $total_offload;
        $res['start'] = $start_offload;
        $res['offload_type'] = 'none';
        $res['print_r'] = $post_id . '-' . $blog_id;

        $start_offload_per = $start_offload + 1;

        $res_persent = floor(( $start_offload_per * 100) / $total_offload);
        $res['persent'] = ($res_persent > 100) ? 100 : $res_persent;

        $start_offload_per_display = ($start_offload_per > $total_offload) ? $total_offload : $start_offload_per;
        
        $res['txt'] = $res['persent'] . '% (' . $start_offload_per_display . '/' . $total_offload . ')';

        if( $total_offload >= $start_offload_per){
            $offload_data['start'] = $start_offload_per;
            next3_update_option( $key , $offload_data);    
        }

        // re-call process start
        if( $total_offload > 0 && $total_offload < $start_offload_per && !in_array($type_offload, [ 'styles', 'scripts'])){
            $offload_data['start'] = 0;
            next3_update_option( $key , $offload_data); 

            $settings_options = next3_options();
            $paged = ($settings_options['storage']['offload_paged']) ?? 1;
            $settings_options['storage']['offload_paged'] = ($paged + 1);

            next3_update_option(next3_options_key(), $settings_options);

            $res['offload_type'] = $type_offload;
            $res['start'] = 0;

            next3_core()->action_ins->get_offload_count( false );

        }
        // end re-call process end
        
        $offload_store = next3_core()->action_ins->get_offload_count();
       
        $res['offload_data'] = $offload_store;

        $off_per = ($offload_store['offload_per']) ?? 0;
        $unoffload_per = ($offload_store['unoffload_per']) ?? 0;
        $off_per_data = ($offload_store['offload']) ?? 0;
        $unoff_per_data = ($offload_store['unoffload']) ?? 0;
        $total_per_data = ($offload_store['total']) ?? 0;
        $clean_per = ($offload_store['clean_per']) ?? 0;
        $wpoffload_per = ($offload_store['wpoffload_per']) ?? 0;
        $css_per = ($offload_store[ $type_offload . '_per']) ?? 0;
        $total_optimize = ($offload_store['total_optimize']) ?? 0;
        $total_webp_done = ($offload_store['total_webp_done']) ?? 0;
        $total_compress_done = ($offload_store['total_compress_done']) ?? 0;
        $webp_per = ($offload_store['webp_per']) ?? 0;
        $compress_per = ($offload_store['compress_per']) ?? 0;
        $total_cloud = ($offload_store['total_cloud']) ?? 0;
        $cloud_per = ($offload_store['cloudto_per']) ?? 0;
        $localto_per = ($offload_store['localto_per']) ?? 0;
        $total_backup = ($offload_store['total_backup']) ?? 0;


        if( $type_offload == 'offload'){
            if( $off_per == 100){
                $res['messege'] = __('100% Media files has been offloaded, Congratulations!', 'next3-offload');
            } else{
                $res['messege'] = __( $off_per .'% Media files has been offloaded', 'next3-offload');
            }
        } else if( $type_offload == 'clean'){
            if( $clean_per == 100){
                $res['messege'] = __('100% Offloaded files removed from local stroage, Congratulations!', 'next3-offload');
            } else{
                $res['messege'] = __( $clean_per .'% Offloaded files removed from local stroage', 'next3-offload');
            }
        } else if( $type_offload == 'wpoffload'){
            if( $wpoffload_per == 100){
                $res['messege'] = __('100% Files migrated to "Next3 Offload", Congratulations!', 'next3-offload');
            } else{
                $res['messege'] = __( $wpoffload_per .'% Files migrated to "Next3 Offload"', 'next3-offload');
            }
        } else if( in_array($type_offload, [ 'styles', 'scripts'])){
            if( $css_per == 100){
                $res['messege'] = __('100% '. ucfirst($type_offload) .' files has been offloaded, Congratulations!', 'next3-offload');
            } else{
                $res['messege'] = __( $css_per .'% '. ucfirst($type_offload) .' files has been offloaded', 'next3-offload');
            }
        } else if( $type_offload == 'compress'){
            if( $webp_per == 100 && $compress_per == 100){
                $res['messege'] = __('Compress: 100% and WebP: 100% files has been optimized, Congratulations!', 'next3-offload');
            } else{
                $res['messege'] = __( 'Compress: '.$compress_per .'% and WebP: '.$webp_per.'% files has been optimized', 'next3-offload');
            }
        } else if( $type_offload == 'cloudto'){
            if( $cloud_per == 0){
                $res['messege'] = __('100% Files have been copied, Congratulations!', 'next3-offload');
            } else{
                $res['messege'] = __( $cloud_per .'% Files need to copy into current provider', 'next3-offload');
            }
        } else if( $type_offload == 'localto'){
            if( $localto_per == 0){
                $res['messege'] = __('100% Files have been sync, Congratulations!', 'next3-offload');
            } else{
                $res['messege'] = __( $localto_per .'% Files are need sync between local and cloud', 'next3-offload');
            }
        } else if( $type_offload == 'backupre'){
            if( $localto_per == 0){
                $res['messege'] = __('100% Files have been restored, Congratulations!', 'next3-offload');
            } else{
                $res['messege'] = __( number_format($total_backup) .' Backup files are need to restore in the local', 'next3-offload');
            }
        } else if( $type_offload == 'backude'){
            if( $localto_per == 0){
                $res['messege'] = __('100% Files have been deleted, Congratulations!', 'next3-offload');
            } else{
                $res['messege'] = __( number_format($total_backup) .' Backup files are need to delete in the local', 'next3-offload');
            }
        } else {
            if( $unoffload_per == 100){
                $res['messege'] = __('100% Media files has been local stroage, Congratulations!', 'next3-offload');
            } else{
                $res['messege'] = __( $unoffload_per .'% Media files has been local stroage', 'next3-offload');
            }
        }
        wp_send_json_success(  $res );
    }

    public function rest_api(){
        add_action( 'rest_api_init', function () {
            register_rest_route( 'themedev-submit-form', '/download-files/(?P<filesid>\w+)/', 
              array(
                  'methods' => 'GET',
                  'callback' => [$this, 'themedev_action_rest_download_files'],
                  'permission_callback' => '__return_true'
                ) 
              );
  
            register_rest_route( 'next3', '/v(?P<version>\d+)/(?P<route>\w+)', array(
                'methods' => 'GET',
                'callback' => [$this, '_get_template_info'],
                'permission_callback' => '__return_true'
            ));
    
            register_rest_route( 'next3', '/v(?P<version>\d+)/(?P<route>\w+)', array(
                'methods' => 'POST',
                'callback' => [$this, '_get_template_info'],
                'permission_callback' => '__return_true'
            ));
            
        } );
    }

    public function _get_template_info( \WP_REST_Request $request){
        $result['success'] = true;
        $version = isset($request['version']) ? $request['version'] : '1';
        $route = isset($request['route']) ? $request['route'] : '';
        if(empty($route)){
            $result['error'] = true;
			$result['message'] = esc_html__('Not found..', 'next3-offload');
            return $result;
		}
		$current_user = get_current_user_id();
		if( $current_user == 0){
			$result['error'] = true;
			$result['message'] = esc_html__('Sorry, Invalid user permission.', 'next3-offload');
            return $result;
		}
        
        $credentials = next3_credentials();
        $provider = ($credentials['settings']['provider']) ?? '';
        $prodiver_data = ($credentials['settings'][$provider]) ?? [];
        $default_bucket = ($prodiver_data['default_bucket']) ?? '';
        $default_region = ($prodiver_data['default_region']) ?? 'us-east-1';

        $settings_options = next3_options();
        $delivery_provider = ($settings_options['delivery']['provider']) ?? $provider;

        if( $provider == '' || $default_bucket == '' || $default_region == ''){
			$result['error'] = true;
			$result['message'] = esc_html__('Sorry, Please select your provider.', 'next3-offload');
            return $result;
		}

        $id = isset($request['id']) ? $request['id'] : '';
        //$folder = isset($request['keys']) ? stripslashes($request['keys']) : '';
        $folder = isset($request['keys']) ? $request['keys'] : '';
		$refresh = isset($request['refresh']) ? $request['refresh'] : true;

        $id = empty( $id ) ? $default_bucket : $id;

        $obj = next3_core()->provider_ins->load($provider)->access();

        if( !$obj || !$obj->check_configration() ){
            $result['error'] = true;
			$result['message'] = esc_html__('Error!! provider connection not established.', 'next3-offload');
            return $result;
        }
       
        $buckets_list = $obj->get_buckets();
        $message = $obj->getStatus();
        if( $message != 'success'){
            $result['error'] = true;
			$result['message'] = esc_html__('Sorry, Could not connect with providers API.', 'next3-offload');
            return $result;
        }
        switch($route){

			case 'manage':
                if( !empty($folder) ){
                    $folder = explode('/', $folder);
                }
                
				//$folder = json_decode($folder, true);
                
                $result['store'] = $id;
                $buck = [];
                $result['store_list'] = $buck;
                $filed_data = [];


                $fileList = $obj->get_manage_files($id, $folder, $refresh);
               
                if(is_array($fileList) && !empty($fileList)){
                    $arrayFIl = $this->natkrsort($fileList);
                    foreach($arrayFIl as $k=>$v):
    
                        if(isset($v['name'])){
                            $name = $v['name'];
                            $size = $v['size'];
                            $path = $v['path'];
                            $url = $v['url'];
                            $html = '';
                            if( !empty($url) ){
                                $icon = $this->getExtension($url);
                                if( $icon['type'] == 'image'){
                                    $html = $url;
                                    $dta['is_image'] = 1;
                                } else {
                                    $html = $icon['icon'];
                                    $dta['is_image'] = 0;
                                }
                            }
                            
                            $length = (strlen($name) > 15) ? (strlen($name) - 10) : 0;
                            if($length > 0){	
                                $name_files = substr_replace($name, '...', -$length, -10);
                            }else{
                                $name_files = $name;
                            }
                            $dta['type'] = 'file';
                            $dta['name'] = $name_files;
                            $dta['htl'] = $html;
                            $dta['link'] = $url;
                            $dta['path_key'] = $path;
                            $dta['size'] = $size;
                            if($size > 0){
                                $dta['size_byte'] = $this->formatSizeUnits($size);
                            } else{
                                $dta['size_byte'] = '';
                            }
                            
                        }else{
                            $length = (strlen($k) > 15) ? (strlen($k) - 10) : 0;
                            if($length > 0){	
                                $folder = substr_replace( str_replace(['_nx_', '-'], ['', ' '], $k), '...', -$length, -10);
                            }else{
                                $folder = str_replace(['_nx_', '-'], ['', ' '], $k) ;
                            }
                            $dta['type'] = 'folder';
                            $dta['name'] = $folder;
                            $dta['htl'] = 'dashicons dashicons-book';
                            $dta['link'] = $k;
                        }
                        $filed_data[] = $dta;
                        $keys[] = $k;
                    endforeach;
                    $result['files'] = $filed_data;
                }
                

			break;
			
			case 'save':
                if( NEXT3_SELF_MODE ){
                    $result['error'] = true;
			        $result['message'] = esc_html__('Sorry, Trial mode enabled, you can\'t access.', 'next3-offload');
                    return $result;
                }
                $url = isset($request['url']) ? $request['url'] : '';
                $path = isset($request['path']) ? $request['path'] : '';
                $idsData = $this->insert_cloud_to_wpmedia($url, $path);
                if( $idsData != 0 && $idsData != ''){
                   
                    next3_update_post_meta( $idsData, '_next3_source_type', 'from_cloud' );
                    next3_update_post_meta( $idsData, '_next3_attached_file', $path );
                    next3_update_post_meta( $idsData, '_next3_attached_url', $url );
                    next3_update_post_meta( $idsData, '_next3_provider', $provider );
                    next3_update_post_meta( $idsData, '_next3_provider_delivery', $delivery_provider );
                    next3_update_post_meta( $idsData, '_next3_bucket', $default_bucket );
                    next3_update_post_meta( $idsData, '_next3_region', $default_region );
                    
                    $result['success'] = true;
			        $result['message'] = esc_html__('Successfully added files into wp media.', 'next3-offload');
                } else {
                    $result['error'] = true;
                    $result['message'] = esc_html__('Sorry, Files already exits into wp media.', 'next3-offload');
                }
				
			break;

            case 'buckets':
                if( NEXT3_SELF_MODE ){
                    $result['error'] = true;
			        $result['message'] = esc_html__('Sorry, Trial mode enabled, you can\'t access.', 'next3-offload');
                    return $result;
                }
                $storename = str_replace( ['_', ' ', '  ', ',', '.', "'", '`'], '-', $id);
                $create = $obj->get_createBucket($storename, $default_region);
                if($create['status'] == true){
                    $result['message'] = $storename;
                } else{
                    $result['error'] = true;
			        $result['message'] = $create['msg'];
                }
            break;

            case 'deletes':
                if( NEXT3_SELF_MODE ){
                    $result['error'] = true;
			        $result['message'] = esc_html__('Sorry, Trial mode enabled, you can\'t access.', 'next3-offload');
                    return $result;
                }
                $keys = isset($request['path']) ? $request['path'] : '';
                if( !empty($keys) ){
                    if( in_array($provider, ['bunny'])){
                        $keys = str_replace($id, '', $keys);
                    }
                    $idsData = $obj->get_deleteObjects($id, $keys);
                    if( $idsData['status'] == true){
                        $post_ids = next3_post_id_by_meta('_next3_attached_file', $keys);
                        if( $post_ids !=  false && $post_ids != 0){
                            $source_type = next3_get_post_meta( $post_ids, '_next3_source_type');
                            if( $source_type == 'from_cloud'){
                                delete_post( $post_ids );
                            } else {
                                next3_delete_post_meta( $post_ids, '_next3_attached_file');
                                next3_delete_post_meta( $post_ids, '_next3_attached_url');
                                next3_delete_post_meta( $post_ids, '_next3_source_type');
                                next3_delete_post_meta( $post_ids, '_next3_provider');
                                next3_delete_post_meta( $post_ids, '_next3_provider_delivery');
                                next3_delete_post_meta( $post_ids, '_next3_bucket');
                                next3_delete_post_meta( $post_ids, '_next3_region');
                                next3_delete_post_meta( $post_ids, '_next3_attachment_metadata');
                                next3_delete_post_meta( $post_ids, '_next3_clean_status');
                                next3_delete_post_meta( $post_ids, '_next3_rename_file');
                                next3_delete_post_meta( $post_ids, '_next3_rename_orginal');
                            }
                        }
                        $result['success'] = true;
                        $result['message'] = esc_html__('Successfully deleted files.', 'next3-offload');
                    } else {
                        $result['error'] = true;
                        $result['message'] = esc_html__('Sorry, Files can not delete.', 'next3-offload');
                    }
                }
               
            break;
        }
        return $result;
	}

    public function themedev_action_rest_download_files(\WP_REST_Request $request){
		$return = ['success' => [], 'error' => [] ];
		$store = isset($request['store']) ? $request['store'] : '';
		$link = isset($request['link']) ? $request['link'] : '';
		return $return;
	}

    public function insert_cloud_to_wpmedia( $url , $path = ''){
        
        if ( !empty($url) ) {

            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
            require_once(ABSPATH . "wp-admin" . '/includes/media.php');

            $file_array  = [ 'name' => wp_basename( $url ), 'tmp_name' => download_url( $url ) ];
            $desc = 'Upload from Cloud';
            if ( is_wp_error( $file_array['tmp_name'] ) ) {
                return 0;
            }
            $id_media = media_handle_sideload( $file_array, 0, $desc );
            if ( is_wp_error( $id_media ) ) {
                @unlink( $file_array['tmp_name'] );
                return 0;
            }
            return $id_media;
        }
        return 0;
    }

    // copy to cloud from local media
    public function copy_to_cloud( $url = '', $post_id = 0, $remove = false, $match = false){
        $result = [];

        $credentials = next3_credentials();
        $provider = ($credentials['settings']['provider']) ?? '';
        $prodiver_data = ($credentials['settings'][$provider]) ?? [];
        $default_bucket = ($prodiver_data['default_bucket']) ?? '';
        $default_region = ($prodiver_data['default_region']) ?? 'eu-west-2';

        if( $provider == '' || $default_bucket == '' || $default_region == ''){
			$result['error'] = true;
			$result['message'] = esc_html__('Sorry, please configure your provider.', 'next3-offload');
            return $result;
		}
        // check provider connection
        $obj = next3_core()->provider_ins->load($provider)->access();

        if( !$obj || !$obj->check_configration()){
            $result['error'] = true;
			$result['message'] = esc_html__('Error! Provider connection not established.', 'next3-offload');
            return $result;
        }

        
        $settings_options = next3_options();
        $delivery_provider = ($settings_options['delivery']['provider']) ?? $provider;
        $compression_enable = ($settings_options['optimization']['compression']) ?? 'no';
        $webp_enable = ($settings_options['optimization']['webp_enable']) ?? 'no';
        $status_data = next3_service_status();
        $develeper_status = ($status_data['develeper']) ?? false;
        
        // source file
        $source_file_old = next3_get_attached_file( $post_id, true );
        $source_file = $source_file_webp = $source_file_old;
        $basename = $basename_webp = basename( $source_file );

        $metadata = next3_wp_get_attachment_metadata( $post_id, true);

        $attached_file = $attached_file_webp = next3_get_post_meta( $post_id, '_wp_attached_file');
        
        // match file 
        if($webp_enable == 'yes' && $develeper_status && is_array($match)){
            $attached_file_webp = $attached_file . '.webp';
            $source_file_webp = $source_file . '.webp';
        }

        $match_status = false;
        if( is_array($match) && in_array($attached_file, $match) ){
            $match_status = true;
        } else if($webp_enable == 'yes' && is_array($match) && in_array($attached_file_webp, $match)){
            $match_status = true;
            next3_update_post_meta( $post_id, 'next3_optimizer_is_converted_to_webp', 1 );
            $basename_webp   = basename( $source_file_webp );
        }
        // end match status

        // Optimize the main image and the other image sizes.
       if($compression_enable == 'yes' && $develeper_status && !$match_status){
            $status = next3_core()->optimizer_ins->optimize( $post_id, $metadata, $source_file);
            if ( false === $status ) {
                next3_update_post_meta( $post_id, 'next3_optimizer_optimization_failed', 1 );
            }
        }
        
        // webp convert
        $webp_status = false;
        if($webp_enable == 'yes'  && $develeper_status && !$match_status){
            $status_webp =  next3_core()->webp_ins->optimize( $post_id, $metadata, $source_file);
            if ( false == $status_webp ) {
                next3_update_post_meta( $post_id, 'next3_optimizer_is_converted_to_webp__failed', 1 );
            } else {
                $webp_status = true;
            }
        }
        //end optimize

        $setup_data = next3_check_setup('array');
        $step_data = ($setup_data['step']) ?? '';
        $msg_data = ($setup_data['msg']) ?? '';
        if( $step_data != 'dashboard'){
            $result['error'] = true;
			$result['message'] = $msg_data;
            return $result;
        }

        if( $post_id == 0 && !empty($url) ){
            $post_id =  attachment_url_to_postid( $url );
        }
        if( empty($url) ){
            $url = next3wp_get_attachment_url($post_id);
        }

        if( $post_id == 0 || empty($post_id) ){
            $result['error'] = true;
			$result['message'] = esc_html__('Sorry, Invalid media file.', 'next3-offload');
            return $result;
        }

        $settings_options = next3_options();
        $enable_mine = ($settings_options['storage']['enable_mine']) ?? 'no';
        $selected_files = ($settings_options['storage']['selected_files']) ?? ['all'];
        $unique_file = ($settings_options['storage']['unique_file']) ?? 'no';

        $enable_path = 'yes';
        $folder_format = 'yes';
        $addition_folder = 'no';
        if( !empty($settings_options) ){
            $enable_path = ($settings_options['storage']['enable_path']) ?? 'no';
            $folder_format = ($settings_options['storage']['folder_format']) ?? 'no';
            $addition_folder = ($settings_options['storage']['addition_folder']) ?? 'no';
        }
        $upload_path = ($settings_options['storage']['upload_path']) ?? $this->get_upload_prefix();
        // site id replace - {site_id}
        $upload_path = str_replace('{site_id}', get_current_blog_id(), $upload_path);

        $path_orginal = $attached_file;
        if( empty($path_orginal) ){
            $exp = explode('/', $url);
            $path_orginal = end($exp);
        }
        
        $source_file_back = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $source_file );
		if( is_readable($source_file_back) && !is_readable($source_file)){
            rename($source_file_back, $source_file);
		}
        
        // webp images status
        if( true === next3_check_post_meta($post_id, 'next3_optimizer_is_converted_to_webp') || file_exists( $source_file . '.webp') ){
            array_push($selected_files, '.webp');
            if ( file_exists( $source_file . '.webp' ) ) {
                $source_file .= '.webp';
                $webp_status = true;
                next3_update_post_meta($post_id, 'next3_optimizer_is_converted_to_webp', 1);
            } else {
               next3_delete_post_meta($post_id, 'next3_optimizer_is_converted_to_webp');
            }
        }


        // read file
        if( NEXT3_NOT_FILE && !is_readable($source_file) && !$match_status){
            $result['error'] = true;
			$result['message'] = esc_html__('Sorry, Invalid media file.', 'next3-offload');
            return $result;
        }

        // check file extention
        if( $enable_mine == 'yes' && !$match_status){
            $ext = '.' . strtolower(pathinfo($source_file, PATHINFO_EXTENSION));
            if( !in_array('all', $selected_files) && !in_array($ext, $selected_files)){
                $result['error'] = true;
                $result['message'] = esc_html__('Sorry, Invalid file type. Please enable mime type from Next3 Settings.', 'next3-offload');
                return $result;
            }
        }
        

        $file_type_check     = wp_check_filetype_and_ext( $source_file, wp_basename( $source_file ) );
		$allowed_types = next3_allowed_mime_type();
		// check mime type of file is in allowed provider mime types
		if ( ! in_array( $file_type_check['type'], $allowed_types, true ) && !$match_status) {
			$error_msg = sprintf( __( 'Mime type "%1$s" is not allowed', 'next3-offload' ), $file_type_check['type'] );
            $result['error'] = true;
            $result['message'] = $error_msg;
            return $result;
		}

        //file read/write permission
        chmod($source_file, 0777);

        // create new path for cloud
        $newpath = [];
        if( !empty($upload_path) && $enable_path == 'yes'){
            $exp_path = explode( '/', $upload_path);
            $exp_path = array_filter($exp_path, function($v){
                return !empty($v) || $v === 0;
            });
            $newpath = array_merge($newpath, $exp_path);
        }

        if( $folder_format == 'yes'){
            $exp_path = explode('/', $path_orginal);
            array_pop($exp_path);
            foreach($exp_path as $v){
                if( empty($v) ){
                    continue;
                }
                $newpath[] = $v;
            }
        }

        if( $addition_folder == 'yes'){
            $newpath[] = strtotime(wp_date("Y-m-d H:i"));
        }
        $exp_orginal_path = explode('/', $path_orginal);

        $file_name_end = end($exp_orginal_path);
        
        $file_name_without = pathinfo($source_file_old, PATHINFO_FILENAME);
        $unique_name = $file_name_without;

        //unique file name
        if( $unique_file == 'yes'){
            next3_update_post_meta( $post_id, '_next3_rename_orginal', $unique_name );

            $unique_name = next3_random_string();
            $file_name_end = str_replace($file_name_without, $unique_name, $file_name_end);
            $newpath[] = $file_name_end;
        } else {
            $newpath[] = $file_name_end;
        }
    
        $newpath_main = implode('/', str_replace([' '], ['-'], $newpath));
        // webp format
        if(true === $webp_status){
            if(strpos($newpath_main, ".webp") === false){
                $newpath_main .= '.webp';
            }
        }

		$mime_type = get_post_mime_type($post_id);
        // webp format
        if(true === $webp_status){
            $mime_type = 'image/webp';
        }
        
        
        if( NEXT3_NOT_FILE && is_readable($source_file) && !$match_status){
            $upload = $obj->putObject($default_bucket, $source_file, $newpath_main, $mime_type);
        } else {
            $upload = [
                'status' => true,
                'data' => '',
            ];
        }
        
        $upload_status = isset($upload['status']) ? $upload['status'] : false;
        
        if( $upload_status ){
            $url = ($upload['data']) ?? '';
            
            if( $unique_file == 'yes' && !$match_status){
                next3_update_post_meta( $post_id, '_next3_rename_file', $unique_name );
            }
            next3_update_post_meta( $post_id, '_next3_attached_file', $newpath_main );
            next3_update_post_meta( $post_id, '_next3_attached_url', $url );
            next3_update_post_meta(  $post_id, '_next3_source_type', 'wp_media' );
			next3_update_post_meta(  $post_id, '_next3_provider', $provider );
			next3_update_post_meta(  $post_id, '_next3_provider_delivery', $delivery_provider );
			next3_update_post_meta(  $post_id, '_next3_bucket', $default_bucket );
			next3_update_post_meta(  $post_id, '_next3_region', $default_region );

            $result['success'] = true;
            $result['message'] = $url;

            // crop images uploaded
            $crop = $metadata;
            if( !isset($crop['sizes'])){
                next3_delete_post_meta( $post_id, '_wp_attachment_metadata' );
                require_once( ABSPATH . 'wp-admin/includes/image.php');
                if( function_exists('wp_update_attachment_metadata') ){
                    wp_update_attachment_metadata( $post_id, wp_generate_attachment_metadata( $post_id, $source_file ) );
                    $metadata = $crop = next3_get_post_meta( $post_id, '_wp_attachment_metadata');
                }
            }
            
            $filesize = filesize($source_file);
            $filesize_total = $filesize;

            $crop_sizes = ($metadata['sizes']) ?? [];

            if( !empty($crop_sizes) ){
                $size = [ $path_orginal ];

                foreach($crop_sizes as $k=>$v){
                    $file = ( $v['file'] ) ?? '';
                    if( empty($k) || empty($file) ){
                        continue;
                    }

                    // match action
                    $match_status = false;
                    $attached_file_crop = str_replace( $basename, $file, $attached_file );
                    $attached_file_webp_crop = str_replace( $basename_webp, $file, $attached_file_webp );

                    if( is_array($match) && in_array($attached_file_crop, $match) ){
                        $match_status = true;
                    } else if($webp_enable == 'yes' && is_array($match) && in_array($attached_file_webp_crop, $match)){
                        $match_status = true;
                        $attached_file_crop = $attached_file_webp_crop;
                    }

                    if( $match_status ){
                        array_pop($newpath);
                        $v_explode = explode('/', $attached_file_crop);
                        $file_name = end($v_explode);

                        array_push($newpath, $file_name);
                        $newpath_sub = implode('/', str_replace([' '], ['-'], $newpath));

                        $crop['sizes'][$k]['org_file'] = $attached_file_crop;
                        $crop['sizes'][$k]['file'] = $newpath_sub;
                    }
                    // end match code

                    // start offload process
                    if( !in_array($file, $size) && !$match_status){
                        $size[] = $file;

                        $v_explode = explode('/', $file);
                        $file_name = end($v_explode);
                        
                        
                        // webp format
                        if( true === $webp_status){
                            $file_name .= '.webp';
                        }
                        // path recreate for sub
                        $s_explode = explode('/', $source_file);
                        array_pop($s_explode);
                        array_push($s_explode, $file_name);
                        $path = implode('/', $s_explode);

                        $crop['sizes'][$k]['path'] = $path;

                        // file recheck for not found
                        if( !is_readable($path) ){
                            if( true === $webp_status){
                                $path = str_replace('.webp', '', $path);
                            }
                            if( !is_readable($path) ){
                                // backup file
                                $path_back = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $path );
                                if( is_readable($path_back) ){
                                    rename($path_back, $path);
                                }
                            }

                            if( true === $webp_status){
                                $status_webp_suv =  next3_core()->webp_ins->generate_webp_action( $path, $post_id, $settings_options);
                                if ( true !== boolval( $status_webp_suv ) ) {
                                    $path .= '.webp';
                                }
                            }
                        }

                        if( NEXT3_NOT_FILE && !is_readable($path)){
                            continue;
                        }
                        
                        // destination path
                        array_pop($newpath);

                        if( $unique_file == 'yes'){
                            $file_name = str_replace($file_name_without, $unique_name, $file_name);
                        }
                        array_push($newpath, $file_name);
                        $newpath_sub = implode('/', str_replace([' '], ['-'], $newpath));

                        $crop['sizes'][$k]['org_file'] = $file;
                        $crop['sizes'][$k]['file'] = $newpath_sub;
                        

                        // webp format
                        if(true === $webp_status){
                            if(strpos($newpath_sub, ".webp") === false){
                                $newpath_sub .= '.webp';
                            }
                        }
                        if( NEXT3_NOT_FILE && is_readable($path)){

                            //file read/write permission
                            chmod($path, 0777);

                            $filesize_total += filesize( $path );

                            $obj->putObject($default_bucket, $path, $newpath_sub, $mime_type);
                        } 
                        
                    }
                }
            }
            
            $crop['org_file'] = ($crop['file']) ?? '';
            $crop['file'] = $newpath_main;
            $crop['filesize'] = $filesize;
            $crop['filesize_old'] = $metadata['filesize'];
            $crop['image_meta']['copyright'] = 'Via @Next3 Offload Plugin';

            $metadata['filesize'] = $filesize;
            
            next3_update_post_meta( $post_id, '_wp_attachment_metadata', $metadata);

            next3_update_post_meta( $post_id, '_next3_attachment_metadata', $crop );
            next3_update_post_meta( $post_id, '_next3_filesize_total', $filesize_total );
            
            if( $remove ){
               
                if( is_readable($source_file) ){
                    $basename   = basename( $source_file );
                    $unlink = @unlink( $source_file );
                    //size images
                    if ( ! empty( $metadata['sizes'] ) ) {
                        foreach ( $metadata['sizes'] as $size ) {
                            $file_name = $size['file'];
                            if( $webp_status == true){
                                if(strpos($file_name, ".webp") === false){
                                    $file_name .= '.webp';
                                }
                            }
                            $unlink = @unlink( str_replace( $basename, $file_name, $source_file ));
                        }
                    }
                    if( $unlink ){
                        next3_update_post_meta( $post_id, '_next3_clean_status', true );
                    }
                } else {
                    if( !NEXT3_NOT_FILE ){
                        next3_update_post_meta( $post_id, '_next3_clean_status', true );
                    }
                }
            }

        } else {
            $result['message'] = esc_html__($upload['msg'], 'next3-offload');
        }

        return $result;
    }
    // assets like css, js file offload to cloud
    public function assets_copy_to_cloud($data, $remove = false){
        
        $result = [];

        $source_file = ($data['source_file']) ?? '';
        $key = ($data['key']) ?? '';
        $handler = ($data['handler']) ?? '';
        $group = ($data['group']) ?? '';
        $type_offload = ($data['type_offload']) ?? '';

        if( !file_exists($source_file)){
            $result['error'] = true;
			$result['message'] = esc_html__('Sorry, Invalid source of file.', 'next3-offload');
            return $result;
        }

        $credentials = next3_credentials();
        $provider = ($credentials['settings']['provider']) ?? '';
        $prodiver_data = ($credentials['settings'][$provider]) ?? [];
        $default_bucket = ($prodiver_data['default_bucket']) ?? '';
        $default_region = ($prodiver_data['default_region']) ?? 'eu-west-2';

        if( $provider == '' || $default_bucket == '' || $default_region == ''){
			$result['error'] = true;
			$result['message'] = esc_html__('Sorry, please select your provider.', 'next3-offload');
            return $result;
		}

        $obj = next3_core()->provider_ins->load($provider)->access();

        if( !$obj || !$obj->check_configration()){
            $result['error'] = true;
			$result['message'] = esc_html__('Error!! provider connection not established.', 'next3-offload');
            return $result;
        }

        $status_data = next3_service_status();
        $assets_status = ($status_data['assets']) ?? false;
        if( !$assets_status ){
            $result['error'] = true;
			$result['message'] = esc_html__('Error!! need to purchase developer plan.', 'next3-offload');
            return $result;
        }

        $settings_options = next3_options();
        $delivery_provider = ($settings_options['delivery']['provider']) ?? $provider;

        $minify_css = ($settings_options['assets']['minify_css']) ?? 'no';
        $version_css = ($settings_options['assets']['version_css']) ?? 'no';
        
        $mime_type = 'text/css';
        if( $type_offload == 'scripts'){
            $version_css = ($settings_options['assets']['version_js']) ?? 'no';
            $minify_css = ($settings_options['assets']['minify_js']) ?? 'no';
            $mime_type = 'text/javascript';
        }

        // replace min
        $source_file_min = str_replace(['.min.css', '.min.js'], ['.css', '.js'], $source_file);
        $source_file_min = preg_replace( '~.(css|js)$~', '.min.$1', $source_file_min );

        // minify data
        if( $minify_css == 'yes' && !file_exists( $source_file_min )){
           
            if( $type_offload == 'scripts'){
                $modify_js = Minify::minify_js( file_get_contents( $source_file ));
                file_put_contents($source_file_min, $modify_js);
            }else{
                $modify_css = Minify::minify_css( file_get_contents( $source_file ));
                file_put_contents($source_file_min, $modify_css);
            }
            if( file_exists( $source_file_min) ){
                $source_file = $source_file_min;
            }
        }

        $key = ltrim($key, '/');
        if( $version_css == 'yes'){
            $exp_key = explode('/', $key);
            $exp_key = array_filter($exp_key, function($v){
                return !empty($v) || $v === 0;
            });
            $end_key = end($exp_key);
            array_pop($exp_key);

            $time = strtotime(wp_date("Y-m-d H:i"));

            array_push($exp_key, $time, $end_key);
            $key = implode('/', str_replace([' '], ['-'], $exp_key));
        }
        
        if( $minify_css == 'yes' && file_exists($source_file_min) ){
            $new_key = str_replace(['.min.css', '.min.js'], ['.css', '.js'], $key);
            $key = preg_replace( '~.(css|js)$~', '.min.$1', $new_key );
        }
        
        $upload = $obj->putObject($default_bucket, $source_file, $key, $mime_type);
        if( isset($upload['status']) && $upload['status'] == true){
            
            $offload_css = next3_get_option('next3_offload_' . $type_offload , []); 

            $offload_data = [
                'key' => $key,
                'bucket' => $default_bucket,
                'region' => $default_region,
                'provider' => $provider,
                'delivery_provider' => $delivery_provider,
                'source_type' => 'wp_assets',
            ];
            $offload_css[ $handler ] = $offload_data;
            next3_update_option( 'next3_offload_' . $type_offload , $offload_css); 

            $result['success'] = true;
            $result['message'] = next3_core()->action_ins->get_url_assets( $offload_data );
        }

        return $result;
    }
    // move cloud to local storage
    public function move_to_local( $post_id){
        $res = [];
        $res['error'] = true;

        if( next3_get_post_meta($post_id, '_next3_attached_file')){
                
            $unoffload_status  = true;
            $filepath = next3_get_post_meta( $post_id, '_next3_attached_file');
            $provider = next3_get_post_meta( $post_id, '_next3_provider');
            $bucket = next3_get_post_meta( $post_id, '_next3_bucket');
            $region = next3_get_post_meta( $post_id, '_next3_region');
            $provider_delivery = next3_get_post_meta( $post_id, '_next3_provider_delivery');

            $obj = next3_core()->provider_ins->load($provider)->access();

            if( !$obj || !$obj->check_configration()){
                $res['message'] = esc_html__('Error: Provider connection not established.', 'next3-offload');
                $unoffload_status = false;
            }
           
            $message = $obj->getStatus();
            if( $message != 'success'){
                $res['message'] = esc_html__('Error: Sorry, could not connect with the Cloud API.', 'next3-offload');
                $unoffload_status = false;
            }

            // copy file to local
            $size = [];
            
            $source_file = next3_get_attached_file( $post_id, true);
            
            // webp format
            $webp_status = false;
            if( true === next3_check_post_meta($post_id, 'next3_optimizer_is_converted_to_webp') ){
                if(strpos($source_file, ".webp") === false){
                    $source_file .= '.webp';
                }
                if(strpos($filepath, ".webp") === false){
                    $filepath .= '.webp';
                }
                $webp_status = true;
            }

            $size[] = basename($source_file);

            // main file back upload
            if( !is_readable($source_file) ){
                $source_url = next3_get_post_meta( $post_id, '_next3_attached_url');
                if ( !empty($source_url) ) {
                    $source = $this->create_temp_file_from_url($source_url, pathinfo( $source_file ));
                }
            }
            // end copy file to local
            
            // delete data from cloud
            if( $unoffload_status ){
                $resDelete = $obj->get_deleteObjects( $bucket, $filepath);
            }
            $crop = next3_get_post_meta($post_id, '_next3_attachment_metadata');
            
            if( isset($crop['sizes']) && !empty($crop['sizes']) ){
                
                foreach($crop['sizes'] as $k=>$v){
                    $file = ( $v['file'] ) ?? '';
                    if( empty($file) ){
                        continue;
                    }
                    
                    if( true === $webp_status ){
                        if(strpos($file, ".webp") === false){
                            $file .= '.webp';
                        }
                    }
                    // copy file to local
                    if( !in_array($file, $size) ){
                        $size[] = $file;

                        $path_orginal_sub = explode('/', $source_file);
                        array_pop($path_orginal_sub);
                        $path_org = ($v['org_file']) ?? $file;
                        array_push($path_orginal_sub, $path_org);

                        $source_file_sub = implode('/', $path_orginal_sub);
                        
                        // end file to local
                    
                        if( isset( $crop['sizes'][$k]['org_file'] ) ){
                            $crop['sizes'][$k]['file'] = $v['org_file'];
                            unset($crop['sizes'][$k]['org_file']);    
                        }
                        
                        if( is_readable($source_file_sub) ){
                            if( $unoffload_status ){
                                $obj->get_deleteObjects( $bucket, $file);
                            }
                            continue;
                        }

                        $crop_url = next3_core()->action_ins->get_attatchment_url_preview($post_id, $k);
                        if ( !empty($crop_url) ) {
                            $source = $this->create_temp_file_from_url($crop_url, pathinfo( $source_file_sub ));
                        }

                        if( $unoffload_status ){
                            $obj->get_deleteObjects( $bucket, $file);
                        }
                    }
                }
            }
            
            //replace old data
            if( isset( $crop['org_file'] )){
                $crop['file'] = ($crop['org_file']) ?? '';
                $crop['filesize'] = ($crop['filesize_old']) ?? '';
                unset($crop['org_file']);
                unset($crop['filesize_old']);
            }

            // restore wp meta data
            if( next3_get_post_meta($post_id, '_wp_attachment_metadata') === false){
                next3_update_post_meta( $post_id, '_wp_attachment_metadata', $crop);
            } else {
                $metadata = next3_wp_get_attachment_metadata( $post_id, true);
                $metadata['filesize'] = ($crop['filesize_old']) ?? '';
                next3_update_post_meta( $post_id, '_wp_attachment_metadata', $metadata);
            }
            
            // offload media data
            next3_delete_post_meta( $post_id, '_next3_attached_file');
            next3_delete_post_meta( $post_id, '_next3_attached_url');
            next3_delete_post_meta( $post_id, '_next3_source_type');
            next3_delete_post_meta( $post_id, '_next3_provider');
            next3_delete_post_meta( $post_id, '_next3_provider_delivery');
            next3_delete_post_meta( $post_id, '_next3_bucket');
            next3_delete_post_meta( $post_id, '_next3_region');
            next3_delete_post_meta( $post_id, '_next3_clean_status');
            next3_delete_post_meta( $post_id, '_next3_rename_file');
            next3_delete_post_meta( $post_id, '_next3_rename_orginal');
            next3_delete_post_meta( $post_id, '_next3_attachment_metadata');
            
            $res['success'] = true;
            $res['message'] = esc_html__('Successfully moved.', 'next3-offload');
            return $res;

        }else{
            $res['message'] = esc_html__('Already moved.', 'next3-offload');
        }
        return $res;
    }
    // clean file from local storage
    public function clean_from_local( $post_id ){
        $res = [];
        $res['error'] = true;

        $source_file = next3_get_attached_file( $post_id, true);

        // delete backupfile
        $source_file_back = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $source_file );
        if( is_readable($source_file_back) ){
            @unlink( $source_file_back );
        }

        $webp_status = false;
        if( true === next3_check_post_meta($post_id, 'next3_optimizer_is_converted_to_webp') ){
            if(strpos($source_file, ".webp") === false){
                $source_file .= '.webp';
            }
            $webp_status = true;
        }
        
        if( is_readable($source_file) ){
            $basename   = basename( $source_file );
            $meta        = next3_wp_get_attachment_metadata( $post_id, true);
            
            // main file
            $unlink = @unlink( $source_file );
            
            //size images
            if ( ! empty( $meta['sizes'] ) ) {
                foreach ( $meta['sizes'] as $size ) {
                    $file_name = $size['file'];

                    // delete backupfile
                    $file_name_back = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $file_name );
                    $source_file_back = str_replace( $basename, $file_name_back, $source_file_back );
                    if( is_readable($source_file_back) ){
                        @unlink( $source_file_back );
                    }

                    if( $webp_status == true){
                        if(strpos($file_name, ".webp") === false){
                            $file_name .= '.webp';
                        }
                    }
                    $unlink = @unlink( str_replace( $basename, $file_name, $source_file ));
                }
            }
            if( $unlink ){
                next3_update_post_meta( $post_id, '_next3_clean_status', true );
                next3_update_post_meta( $post_id, 'next3_optimizer_orginal_file', true );
            }
            $res['success'] = true;
            $res['messege'] = esc_html__('Successfully removed from local storage.', 'next3-offload');
            return $res;

        } else {
            if( !NEXT3_NOT_FILE ){
                next3_update_post_meta( $post_id, '_next3_clean_status', true );
                next3_update_post_meta( $post_id, 'next3_optimizer_orginal_file', true );
            }
        }

        $res['messege'] = esc_html__('Not remove from local storage.', 'next3-offload');
        return $res;
    }
    // wp offload media plugin to next3 media
    public function copy_wpmedia_to_next3( $post_id ){

        $res = [];
        $res['error'] = true;

        global $wpdb;
        $source_type = 'media-library';

        $res_option = next3_get_option('next3_oflload_count', []);

        if ( class_exists( '\WP_Offload_Media_Autoloader' ) ) {
            $sql = $wpdb->prepare( "SELECT * FROM " . next3_wp_offload_table() . " WHERE source_type = %s AND source_id = %d", $source_type, $post_id );
            $object = $wpdb->get_row( $sql );
            if ( !empty( $object ) && isset($object->provider)) {
                $newpath_main = ($object->path) ?? '';
                $default_bucket = ($object->bucket) ?? '';
                $default_region = ($object->region) ?? '';
                $delivery_provider = ($object->provider) ?? '';
                $map_provider = [
                    'aws' => 'aws',
                    'spaces' => 'digital',
                    'wasabi' => 'wasabi',
                ];
                
                /*
                // new code for remove multisite path
                $exp = explode('/', $newpath_main);
                if( count($exp) > 4){
                    unset($exp[2]);
                    unset($exp[3]);
                    $newpath_main = implode('/', $exp);
                }
                //end code for multisite path
                */

                if( in_array($delivery_provider, $map_provider) ){
                    next3_update_post_meta( $post_id, '_next3_attached_file', $newpath_main );
                    next3_update_post_meta( $post_id, '_next3_attached_url', next3wp_get_attachment_url( $post_id ) );
                    next3_update_post_meta(  $post_id, '_next3_source_type', 'wp_offload' );
                    next3_update_post_meta(  $post_id, '_next3_provider', $map_provider[ $delivery_provider ] );
                    next3_update_post_meta(  $post_id, '_next3_provider_delivery', $map_provider[ $delivery_provider ] );
                    next3_update_post_meta(  $post_id, '_next3_bucket', $default_bucket );
                    next3_update_post_meta(  $post_id, '_next3_region', $default_region );

                    next3_update_post_meta( $post_id, '_next3_attachment_metadata', next3_get_post_meta( $post_id, '_wp_attachment_metadata') );
                    next3_update_post_meta( $post_id, '_next3_filesize_total', 0 );

                    $source_file = next3_get_attached_file( $post_id, true );
                    
                    if( !is_readable($source_file) ){
                        next3_update_post_meta( $post_id, '_next3_clean_status', true );
                    }
                } else{
                    $settings_options = next3_options();
                    $remove_local = isset($settings_options['storage']['remove_local']) ? true : false;
                    if( next3_get_post_meta($post_id, '_next3_attached_url') === false){
                        next3_core()->action_ins->copy_to_cloud('', $post_id, $remove_local); // remove status
                    }
                }
                
                if( next3_get_post_meta($post_id, '_next3_attached_file') ) {
                    $wpoffload_done_count = ($res_option['wpoffload_done']) ?? 0;
                    $res_option['wpoffload_done'] = $wpoffload_done_count + 1;
                }

                // counting option of offload
                next3_update_option('next3_oflload_count', $res_option);

                $res['success'] = true;
                $res['messege'] = esc_html__('Successfully migrated.', 'next3-offload');
                return $res;
            }
        }

        $res['messege'] = esc_html__('Not migrate.', 'next3-offload');
        return $res;
    }

    // sync between cloud and local
    public function sync_between_cloud_local( $post_id ){

        $res = [];
        $res['error'] = true;

        $match = next3_local_cloud_sync('cloud', 'path_new', false);

        $settings_options = next3_options();
        $remove_local = isset($settings_options['storage']['remove_local']) ? true : false;

        $result = next3_core()->action_ins->copy_to_cloud('', $post_id, $remove_local, $match); // remove status
        if( isset(  $result['success']) ){
            $res['success'] = true;
            $res['messege'] = ($result['message']) ?? ''; 
        }
        return $res;
    }

    // file compress action
    public function compress_media_to_local( $post_id ){
        $res = [];
        $res['error'] = true;

        $main_image = next3_get_attached_file( $post_id, true);
        $basename = basename( $main_image );
        
        if ( file_exists( $main_image ) ) {
            
            $settings_options = next3_options();
            $compression_enable = ($settings_options['optimization']['compression']) ?? 'no';
            $webp_enable = ($settings_options['optimization']['webp_enable']) ?? 'no';
            $overwrite_custom = ($settings_options['optimization']['overwrite_custom']) ?? 'no';

            if(true === next3_check_post_meta($post_id, 'next3_optimizer_is_optimized') && 'yes' !== $overwrite_custom){
                $compression_enable = 'no';
            }
            // compress data
            $metadata = next3_wp_get_attachment_metadata( $post_id, true);

            // Optimize the main image and the other image sizes.
            $status_data = next3_service_status();
            $develeper_status = ($status_data['develeper']) ?? false;
            if($compression_enable == 'yes' && $develeper_status){
                $status = next3_core()->optimizer_ins->optimize( $post_id, $metadata, $main_image);
                if ( false === $status ) {
                    next3_update_post_meta( $post_id, 'next3_optimizer_optimization_failed', 1 );
                }
            }
            if($webp_enable == 'yes'  && $develeper_status){
                $status_webp =  next3_core()->webp_ins->optimize( $post_id, $metadata, $main_image );
                if ( false == $status_webp ) {
                    next3_update_post_meta( $post_id, 'next3_optimizer_is_converted_to_webp__failed', 1 );
                }
            }
            //end optimize
            $res['success'] = true;
            $res['messege'] = esc_html__('Successfully compressed.', 'next3-offload');
            return $res;
        }

        $res['messege'] = esc_html__('Not compress.', 'next3-offload');
        return $res;
    }

    // cloud to cloud copy files
    public function cloud_to_cloud_copy( $post_id ){
        $res = [];
        $res['error'] = true;

        $result = next3_core()->action_ins->move_to_local($post_id); 
        if( isset( $result['success']) ){
            $settings_options = next3_options();
            $remove_local = isset($settings_options['storage']['remove_local']) ? true : false;
            
            if( next3_get_post_meta($post_id, '_next3_attached_file') === false){
                $result = next3_core()->action_ins->copy_to_cloud('', $post_id, $remove_local); // remove status
                if( isset(  $result['success']) ){
                    $res['messege'] = esc_html__('Successfully copied.', 'next3-offload');
                } else{
                    $res['messege'] = ($result['message']) ?? '';
                }
                $res['success'] = true;
                return $res;
            }
        }

        $res['messege'] = esc_html__('Not copy.', 'next3-offload');
        return $res;
    }

    // restore back action 
    public function restore_backup_action( $id ){
        $main_image  = $orgianl_file = next3_get_attached_file( $id, true);
        $old_main_image = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $main_image );
        if ( file_exists( $old_main_image ) ) {
            copy( $old_main_image, $main_image );
        } else {
            
            if( true === next3_check_post_meta($id, 'next3_optimizer_is_converted_to_webp') ){
                if(strpos($orgianl_file, ".webp") === false){
                    $orgianl_file .= '.webp';
                }
                if( !file_exists($main_image) && file_exists($orgianl_file)){
                    copy( $orgianl_file, $main_image );
                }
            }
            
        }
        $metadata   = next3_wp_get_attachment_metadata( $id, true);
        if ( ! empty( $metadata['sizes'] ) ) {
            $basename = basename( $main_image );

            foreach ( $metadata['sizes'] as $size ) {
                $checkwebp = $orgianl_file = str_replace( $basename, $size['file'], $main_image );

                // size image
                $old_main_image = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.bak.$1', $checkwebp );
                if ( file_exists( $old_main_image ) ) {
                    copy( $old_main_image, $checkwebp );
                }  else {
            
                    if( true === next3_check_post_meta($id, 'next3_optimizer_is_converted_to_webp') ){
                        if(strpos($orgianl_file, ".webp") === false){
                            $orgianl_file .= '.webp';
                        }
                        if( !file_exists($checkwebp) && file_exists($orgianl_file)){
                            copy( $orgianl_file, $checkwebp );
                        }
                    }
                   
                }
            }
        }
    }

    private function _add_attachment( $data ){
		$name_files = ($data['post_name']) ?? '';
		$post_type = ($data['post_type']) ?? 'attachment';
		
		if ( ! function_exists( 'post_exists' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/post.php' );
		}
        $credentials = next3_credentials();
        $provider = ($credentials['settings']['provider']) ?? '';
        $prodiver_data = ($credentials['settings'][$provider]) ?? [];
        $default_bucket = ($prodiver_data['default_bucket']) ?? '';
        $default_region = ($prodiver_data['default_region']) ?? 'us-east-1';

        $settings_options = next3_options();
        $delivery_provider = ($settings_options['delivery']['provider']) ?? $provider;

        $exits = $this->get_page_by_guid($data['guid']);
		if( isset($post->ID) ){
            next3_update_post_meta(  $post->ID, '_next3_source_type', 'from_cloud' );
            next3_update_post_meta(  $post->ID, '_next3_attached_file', $post->post_name );
			next3_update_post_meta(  $post->ID, '_next3_attached_url', $post->guid );
			next3_update_post_meta(  $post->ID, '_next3_provider', $provider );
			next3_update_post_meta(  $post->ID, '_next3_provider_delivery', $delivery_provider );
			next3_update_post_meta(  $post->ID, '_next3_bucket', $default_bucket );
			next3_update_post_meta(  $post->ID, '_next3_region', $default_region );
			return $post->ID;
		}
        
		$post_id = wp_insert_post( $data );
		if($post_id != 0){

            next3_update_post_meta(  $post_id, '_next3_source_type', 'from_cloud' );
			next3_update_post_meta(  $post_id, '_wp_attached_file', $data['post_name'] );
			next3_update_post_meta(  $post_id, '_next3_attached_file', $data['post_name'] );
			next3_update_post_meta(  $post_id, '_next3_attached_url', $data['guid'] );
			next3_update_post_meta(  $post_id, '_next3_provider', $provider );
            next3_update_post_meta(  $post->ID, '_next3_provider_delivery', $delivery_provider );
			next3_update_post_meta(  $post_id, '_next3_bucket', $default_bucket );
			next3_update_post_meta(  $post_id, '_next3_region', $default_region );

			$meta = array (
				'width' => 900,
				'height' => 900,
				'file' => $data['post_name'],
			);
			next3_update_post_meta(  $post_id, '_wp_attachment_metadata', $meta );
		}
		return $post_id;

	}
    
    protected function get_page_by_guid($page_title, $output = OBJECT, $post_type = 'attachment'){
        global $wpdb;
 
        if ( is_array( $post_type ) ) {
            $post_type           = esc_sql( $post_type );
            $post_type_in_string = "'" . implode( "','", $post_type ) . "'";
            $sql = $wpdb->prepare(
                    "
                    SELECT ID
                    FROM $wpdb->posts
                    WHERE guid = %s
                    AND post_type IN ($post_type_in_string)
                ",
                    $page_title
                );
        } else {
            $sql = $wpdb->prepare(
                "
                SELECT ID
                FROM $wpdb->posts
                WHERE guid = %s
                AND post_type = %s
            ",
                $page_title,
                $post_type
            );
        }
        $page = $wpdb->get_var( $sql );
        if ( $page ) {
            return get_post( $page, $output );
        }
        return 0;
    }

    // convert wpoffload media to Next3 process
    public function wpoffload_to_next3( $perpage = 500, $paged = 0){
        global $wpdb;
        if ( !class_exists( '\WP_Offload_Media_Autoloader' ) ) {
            return;
        }
        $wpoffload_table = next3_wp_offload_table();
        $source_type = 'media-library';
        
        //[age setup]
        $settings_options = next3_options();
        $perpage = ($settings_options['storage']['offload_limit']) ?? $perpage;
        $paged = ($settings_options['storage']['offload_paged']) ?? $paged;
        $setpage = ($paged == 0 || $paged == 1) ? 0 : (($paged - 1) * $perpage);
        // end page setup

        $result = $wpdb->prepare(
            "
            SELECT 
                wpoffload.path,
                wpoffload.provider,
                wpoffload.region,
                wpoffload.bucket,
                wpoffload.source_id,
                wpoffload.source_path
            FROM 
                $wpoffload_table as wpoffload
            WHERE 
                wpoffload.source_type = %s
                ORDER BY wpoffload.id LIMIT $perpage OFFSET $setpage
            ", $source_type
        );
        $object = $wpdb->get_results( $result );
        if( !empty( $object ) ){
            foreach( $object as $v){
                $newpath_main = ($v->path) ?? '';
                $default_bucket = ($v->bucket) ?? '';
                $default_region = ($v->region) ?? '';
                $delivery_provider = ($v->provider) ?? '';
                $source_id = ($v->source_id) ?? '';
                $map_provider = [
                    'aws' => 'aws',
                    'spaces' => 'digital',
                    'wasabi' => 'wasabi',
                ];
                if( in_array($delivery_provider, $map_provider) && $source_id != 0){
                    $url = next3wp_get_attachment_url( $source_id );
                    $meta = serialize(next3_get_post_meta( $source_id, '_wp_attachment_metadata'));
                    $provider = $map_provider[ $delivery_provider ];

                    $wpdb->query(
                        "
                        DELETE FROM $wpdb->postmeta
                        WHERE 
                            `meta_key` IN( '_next3_attached_file', '_next3_attached_url', '_next3_source_type', '_next3_provider', '_next3_provider_delivery', '_next3_bucket', '_next3_region', '_next3_attachment_metadata', '_next3_filesize_total')
                            AND `post_id` = '$source_id'
                        "
                    );

                    /*
                    // new code for remove multisite path
                    $exp = explode('/', $newpath_main);
                    if( count($exp) > 4){
                        unset($exp[2]);
                        unset($exp[3]);
                        $newpath_main = implode('/', $exp);
                    }
                    //end code for multisite path
                    */
                    
                    $wpdb->query(
                        "
                        INSERT INTO 
                            $wpdb->postmeta(post_id, meta_key, meta_value)
                        VALUES
                            ($source_id, '_next3_attached_file', '$newpath_main'),
                            ($source_id, '_next3_attached_url', '$url'),
                            ($source_id, '_next3_source_type', 'wp_offload'),
                            ($source_id, '_next3_provider', '$provider'),
                            ($source_id, '_next3_provider_delivery', '$provider'),
                            ($source_id, '_next3_bucket', '$default_bucket'),
                            ($source_id, '_next3_region', '$default_region'),
                            ($source_id, '_next3_attachment_metadata', '$meta'),
                            ($source_id, '_next3_filesize_total', '0');
                        "
                    );
                }
            }
        }
       
    }

    public function get_upload_prefix() {
		if ( is_multisite()  && NEXT3_MULTI_SITE ) {
			return 'wp-content/uploads/sites/{site_id}/';
		}
		$uploads = wp_upload_dir();
		$parts   = parse_url( $uploads['baseurl'] );
        $path = ($parts['path']) ?? '';
        if( !empty($path) ){
            $path    = ltrim( $parts['path'], '/' );
        }
		
		return trailingslashit( $path );
	}

    public function get_local_url_preview( $escape = true, $suffix = 'photo.jpg' ) {
		$uploads = wp_upload_dir();
		$url     = trailingslashit( $uploads['url'] ) . $suffix;
		if ( $escape ) {
			$url = str_replace( '-', '&#8209;', $url );
		}
		return $url;
	}

    public function get_url_assets( $data_offload = []){
        $key = ($data_offload['key']) ?? '';
        $bucket = ($data_offload['bucket']) ?? '';
        $region = ($data_offload['region']) ?? 'eu-west-2';
        $provider = ($data_offload['provider']) ?? '';
        $delivery_provider = ($data_offload['delivery_provider']) ?? '';

        $url = [];
        $url_main = $this->get_url_scheme();
        
        $credentials = next3_credentials();
        $provider_wp = ($credentials['settings']['provider']) ?? '';
        $prodiver_data = ($credentials['settings'][$provider_wp]) ?? [];
        $type = ($prodiver_data['type']) ?? '';

        $settings_options = next3_options();
        if( !$delivery_provider || empty($delivery_provider)){
            $delivery_provider = $provider;
        }

        if( $provider == 'aws' && $delivery_provider == 'aws_cloudfront'){
            $delivery_provider = 'aws_cloudfront';
        } else if( $provider == 'aws' && $delivery_provider == 'cloudflare' ){
            $delivery_provider = 'cloudflare';
        } else if( $provider == 'digital' && $delivery_provider == 'digital_cdn' ){
            $delivery_provider = 'digital_cdn';
        }

        $force_cdn = ($settings_options['delivery']['force_cdn']) ?? 'no';
        if( $force_cdn == 'yes'){
            $delivery_provider = ($settings_options['delivery']['provider']) ?? $delivery_provider;
        }

        $s3Endpoint = '';
        $config = defined('NEXT3_SETTINGS') ? unserialize(NEXT3_SETTINGS) : [];
        if( !empty($config) && $type == 'wp'){
            $endpoint = ($config['endpoint']) ?? '';
            if( !empty($endpoint) ){
                $s3Endpoint = str_replace(['https://','http://'], '', trim($endpoint, '/') );
            }
        }

        if( $delivery_provider == 'aws'){
            
            if(empty($s3Endpoint)) {
                $url_main .= $bucket.'.' . 's3.' . $region . '.amazonaws.com';
            }
            $url_main .= $s3Endpoint;

        } else if( $delivery_provider == 'digital' ){
            if(empty($s3Endpoint)) {
                $url_main .= $bucket.'.' . $region . '.digitaloceanspaces.com';
            }
            $url_main .= $s3Endpoint;

        }  else if( $delivery_provider == 'digital_cdn' ){

            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? $s3Endpoint;
            if( !empty($endpoint) ){
                $url_main .= $endpoint;
            } else {
                $url_main .= $bucket.'.' . $region . '.cdn.digitaloceanspaces.com';
            }

        } else if( $delivery_provider == 'cloudflare' ){

            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? $s3Endpoint;
            if( !empty($endpoint) ){
                $url_main .= $bucket. '.' . $endpoint. '.r2.cloudflarestorage.com';
            } else {
                if(empty($s3Endpoint)) {
                    $url_main .= $bucket.'.' . 's3.' . $region . '.amazonaws.com';
                }
                $url_main .= $s3Endpoint;
            }

        } else if( in_array($delivery_provider, ['bunny', 'bunny_stream']) ){
            
            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? $s3Endpoint;
            if( !empty($endpoint) ){
                $url_main .= $endpoint;
            } else {
                if($region == 'de' || $region == '') {
                    $url_main .= 'storage.bunnycdn.com';
                } else{
                    $url_main .= $region. '.storage.bunnycdn.com';
                }    
            }
        } else if( in_array($delivery_provider, ['wasabi']) ){
            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? $s3Endpoint;
            
            if(empty($endpoint)) {
                $url_main .= 's3.' . $region . '.wasabisys.com/' . $bucket;
            }
            $url_main .= $endpoint;

        } else if( in_array($delivery_provider, ['objects']) ){
            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? $s3Endpoint;
            
            if(empty($endpoint)) {
                $settingData = ($credentials['settings'][ $delivery_provider ]) ?? [];
                $config_cre = ($settingData['credentails']) ?? [];
                $endpoint = ($config_cre['endpoint_stroage']) ?? '';

                $endpoint = trim($endpoint, '/');
                $endpoint = str_replace(['https://','http://'], '', $endpoint);
            }
            $endpoint .= '/' .  $bucket;
            $url_main .= $endpoint;

        } else {
            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? '';
            if( !empty($endpoint) ){
                $url_main .= $endpoint;
            }
        } 

        $url[] = apply_filters('next3_rewrite_url_assets_pre', $url_main, $provider, $delivery_provider);

        if( !empty($key) ){
            $url[] = trim($key, '/');
        }
        $url_data = implode('/', $url);
        
        return $url_data;
    }

    public function get_url_preview( $escape = true, $suffix = 'photo.jpg', $return = 'url' ) {
		$credentials = next3_credentials();
        $provider = ($credentials['settings']['provider']) ?? '';

        $prodiver_data = ($credentials['settings'][$provider]) ?? [];
        $default_bucket = ($prodiver_data['default_bucket']) ?? '';
        $default_region = ($prodiver_data['default_region']) ?? 'eu-west-2';

        $settings_options = next3_options();
        $upload_path = next3_core()->action_ins->get_upload_prefix();
        $enable_path = ($settings_options['storage']['enable_path']) ?? 'no';

        if( !empty($settings_options) && $enable_path == 'yes' ){
            $upload_path = ($settings_options['storage']['upload_path']) ?? $upload_path;
        }

        // site id replace - {site_id}
        $upload_path = str_replace('{site_id}', get_current_blog_id(), $upload_path);

        $delivery_provider = ($settings_options['delivery']['provider']) ?? $provider;

        $url = [];
        $title_url = ['Scheme'];

        $url_main = $this->get_url_scheme();

        $s3Endpoint = '';
        $config = defined('NEXT3_SETTINGS') ? unserialize(NEXT3_SETTINGS) : [];
        if( !empty($config) ){
            $endpoint = ($config['endpoint']) ?? '';
            if( !empty($endpoint) ){
                $s3Endpoint = str_replace(['https://','http://'], '', trim($endpoint, '/') );
            }
        }

        if( $delivery_provider == 'aws'){
            
            if(empty($s3Endpoint)) {
                $url_main .= $default_bucket.'.' . 's3.' . $default_region . '.amazonaws.com';
            }
            $url_main .= $s3Endpoint;

        } else if( $delivery_provider == 'digital' ){

            if(empty($s3Endpoint)) {
                $url_main .= $default_bucket.'.' . $default_region . '.digitaloceanspaces.com';
            }
            $url_main .= $s3Endpoint;

        }  else if( $delivery_provider == 'digital_cdn' ){

            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? $s3Endpoint;
            if( !empty($endpoint) ){
                $url_main .= $endpoint;
            } else {
                $url_main .= $default_bucket.'.' . $default_region . '.cdn.digitaloceanspaces.com';
            }

        } else if( $delivery_provider == 'cloudflare' ){

            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? $s3Endpoint;
            if( !empty($endpoint) ){
                $url_main .= $default_bucket. '.' . $endpoint. '.r2.cloudflarestorage.com';
            } else {
                if(empty($s3Endpoint)) {
                    $url_main .= $default_bucket.'.' . 's3.' . $default_region . '.amazonaws.com';
                }
                $url_main .= $s3Endpoint;
            }

        } else if( in_array($delivery_provider, ['bunny', 'bunny_stream'])){

            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? $s3Endpoint;
            if( !empty($endpoint) ){
                $url_main .= $endpoint;
            } else {
                if($default_region == 'de' || $default_region == '') {
                    $url_main .= 'storage.bunnycdn.com';
                } else{
                    $url_main .= $default_region. '.storage.bunnycdn.com';
                }    
            }
        } else if( in_array($delivery_provider, ['wasabi'])){
            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? $s3Endpoint;
            
            if(empty($endpoint)) {
                $url_main .= 's3.' . $default_region . '.wasabisys.com/' .  $default_bucket;
            }
            $url_main .= $endpoint;

        } else if( in_array($delivery_provider, ['objects', 'public_endpoint'])){
            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? $s3Endpoint;
            
            if(empty($endpoint)) {
                $settingData = ($credentials['settings'][ $delivery_provider ]) ?? [];
                $config_cre = ($settingData['credentails']) ?? [];
                $endpoint = ($config_cre['endpoint_stroage']) ?? '';

                $endpoint = trim($endpoint, '/');
                $endpoint = str_replace(['https://','http://'], '', $endpoint);
            }
            $endpoint .= '/' . $default_bucket;
            
            $url_main .= $endpoint;
        } else {
            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? '';
            if( !empty($endpoint) ){
                $url_main .= $endpoint;
            }
        }

        if( defined('NEXT3_PUBLIC_ENDPOINT') && !empty(NEXT3_PUBLIC_ENDPOINT) ){
            if(strpos(NEXT3_PUBLIC_ENDPOINT, '.contabostorage.com') === true){
                $url_main .=  str_replace(['https://','http://'], '', NEXT3_PUBLIC_ENDPOINT) . ':' . $default_bucket;
            } else {
                $url_main .= str_replace(['https://','http://'], '', NEXT3_PUBLIC_ENDPOINT) . '/' . $default_bucket;
            }
        }

        $url[] = apply_filters('next3_preview_url_pre', $url_main, $provider, $delivery_provider);

        if( $return == 'bunny_url'){
            if( !empty($suffix) ){
                $url[] = trim($suffix, '/');
            }
            $url_data = implode('/', $url);
            if ( $escape ) {
                //$url_data = str_replace( '-', '&#8209;', $url_data );
            }
            return $url_data;
        }

        if( $enable_path == 'yes' && !empty($upload_path) ){
            $title_url[] = 'Prefix';

            $url[] = trim($upload_path, '/');
        }
         
        $folder_format = ($settings_options['storage']['folder_format']) ?? 'no';
        if( $folder_format == 'yes'){
            $title_url[] = 'Year & Month';
            $url[] = date("Y/m");
        }
        $addition_folder = ($settings_options['storage']['addition_folder']) ?? 'no';
        if( $addition_folder == 'yes'){
            $title_url[] = 'Version';
            $url[] = strtotime(wp_date("Y-m-d H:i"));
        }

        if( !empty($suffix) ){
            $title_url[] = 'Filename';
            $url[] = trim($suffix, '/');
        }

        $url = apply_filters( 'next3_preview_url', $url, $provider, $delivery_provider);
        if( $return == 'array'){
            return [
                'url' => $url,
                'title_url' => $title_url,
            ];
        }
        $url_data = implode('/', $url);

		if ( is_wp_error( $url_data ) ) {
			return '';
		}
		if ( $escape ) {
			$url_data = str_replace( '-', '&#8209;', $url_data );
		}

		return $url_data;
	}

    public function get_attatchment_url_preview( $id , $size = 'full'){
        // cache 
        $to_cache = isset($_GET['next3-cache']) ? [] : next3_core()->compatiblility_ins->get_post_cache( $id, 'next3_get_attatchment_url_preview');
        if( isset($to_cache[ $id ])){
            return ($to_cache[ $id ]) ?? '';
        }
        // cache end

        if( $id == 0 || $id == ''){
            return;
        }
        
        if( next3_get_post_meta($id, '_next3_attached_url') === false){
            $url_default = next3wp_get_attachment_url($id);
            if( true === next3_check_post_meta($id, 'next3_optimizer_is_converted_to_webp') ){
                if(strpos($url_default, ".webp") === false){
                    $url_default .= '.webp';
                }
            }

            // cache 
            $to_cache[ $id ] = $url_default;
            next3_core()->compatiblility_ins->set_post_cache($id, 'next3_get_attatchment_url_preview', $to_cache);
            // cache end
            return $url_default;
        }
        $url = [];

        $url_main = $this->get_url_scheme();

        $credentials = next3_credentials();
        $provider_wp = ($credentials['settings']['provider']) ?? '';
        $prodiver_data = ($credentials['settings'][$provider_wp]) ?? [];
        $type = ($prodiver_data['type']) ?? '';

        $settings_options = next3_options();

        $bucket = next3_get_post_meta( $id, '_next3_bucket');
		$region = next3_get_post_meta( $id, '_next3_region');
		$key = next3_get_post_meta( $id, '_next3_attached_file');
		$provider = next3_get_post_meta( $id, '_next3_provider' );
		$delivery_provider = next3_get_post_meta( $id, '_next3_provider_delivery');
        if( !$delivery_provider || empty($delivery_provider)){
            $delivery_provider = $provider;
        }

        if( true === next3_check_post_meta($id, 'next3_optimizer_is_converted_to_webp') ){
            if(strpos($key, ".webp") === false){
                $key .= '.webp';
            }
        }

        if( $provider == 'aws' && $delivery_provider == 'aws_cloudfront'){
            $delivery_provider = 'aws_cloudfront';
        } else if( $provider == 'aws' && $delivery_provider == 'cloudflare' ){
            $delivery_provider = 'cloudflare';
        } else if( $provider == 'digital' && $delivery_provider == 'digital_cdn' ){
            $delivery_provider = 'digital_cdn';
        }

        $force_cdn = ($settings_options['delivery']['force_cdn']) ?? 'no';
        if( $force_cdn == 'yes'){
            $delivery_provider = ($settings_options['delivery']['provider']) ?? $delivery_provider;
        }
        
        $s3Endpoint = '';
        $config = defined('NEXT3_SETTINGS') ? unserialize(NEXT3_SETTINGS) : [];
        if( !empty($config) && $type == 'wp'){
            $endpoint = ($config['endpoint']) ?? '';
            if( !empty($endpoint) ){
                $s3Endpoint = str_replace(['https://','http://'], '', trim($endpoint, '/') );
            }
        }

        if( $delivery_provider == 'aws'){
            
            if(empty($s3Endpoint)) {
                $url_main .= $bucket.'.' . 's3.' . $region . '.amazonaws.com';
            }
            $url_main .= $s3Endpoint;

        } else if( $delivery_provider == 'digital' ){
            if(empty($s3Endpoint)) {
                $url_main .= $bucket.'.' . $region . '.digitaloceanspaces.com';
            }
            $url_main .= $s3Endpoint;

        }  else if( $delivery_provider == 'digital_cdn' ){

            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? $s3Endpoint;
            if( !empty($endpoint) ){
                $url_main .= $endpoint;
            } else {
                $url_main .= $bucket.'.' . $region . '.cdn.digitaloceanspaces.com';
            }

        } else if( $delivery_provider == 'cloudflare' ){

            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? $s3Endpoint;
            if( !empty($endpoint) ){
                $url_main .= $bucket. '.' . $endpoint. '.r2.cloudflarestorage.com';
            } else {
                if(empty($s3Endpoint)) {
                    $url_main .= $bucket.'.' . 's3.' . $region . '.amazonaws.com';
                }
                $url_main .= $s3Endpoint;
            }

        } else if( in_array($delivery_provider, ['bunny', 'bunny_stream']) ){
            
            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? $s3Endpoint;
            if( !empty($endpoint) ){
                $url_main .= $endpoint;
            } else {
                if($region == 'de' || $region == '') {
                    $url_main .= 'storage.bunnycdn.com';
                } else{
                    $url_main .= $region. '.storage.bunnycdn.com';
                }    
            }
        } else if( in_array($delivery_provider, ['wasabi']) ){
            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? $s3Endpoint;
            
            if(empty($endpoint)) {
                $url_main .= 's3.' . $region . '.wasabisys.com/' . $bucket;
            }
            $url_main .= $endpoint;

        } else if( in_array($delivery_provider, ['objects', 'public_endpoint']) ){
            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? $s3Endpoint;
            
            if(empty($endpoint)) {
                $settingData = ($credentials['settings'][ $delivery_provider ]) ?? [];
                $config_cre = ($settingData['credentails']) ?? [];
                $endpoint = ($config_cre['endpoint_stroage']) ?? '';
                $endpoint = trim($endpoint, '/');
                $endpoint = str_replace(['https://','http://'], '', $endpoint);
            }
            $endpoint .= '/' . $bucket;

            $url_main .= $endpoint;

        }else {
            $endpoint = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? '';
            if( !empty($endpoint) ){
                $url_main .= $endpoint;
            }
        } 

        if( defined('NEXT3_PUBLIC_ENDPOINT') && !empty(NEXT3_PUBLIC_ENDPOINT) ){
            if(strpos(NEXT3_PUBLIC_ENDPOINT, '.contabostorage.com') === true){
                $url_main .=  str_replace(['https://','http://'], '', NEXT3_PUBLIC_ENDPOINT) . ':' . $bucket;
            } else {
                $url_main .= str_replace(['https://','http://'], '', NEXT3_PUBLIC_ENDPOINT) . '/' . $bucket;
            }
        }
        
        $url[] = apply_filters('next3_rewrite_url_pre', $url_main, $provider, $delivery_provider);

        if( !empty($delivery_provider)){
            if( $size != 'full'){
                $url[] = next3_core()->action_ins->next3_get_metadata_size($id, $size, false);
            } else {
                $url[] = $key;
            }
        } else {
            $url_data = next3_get_post_meta( $id, '_next3_attached_url');
            $new_url = str_replace(['https://', 'http://'], '', $url_data);
            $exp_new_url = explode('/', $new_url);
            $new_ul_data = $exp_new_url[0] .'/'. next3_core()->action_ins->next3_get_metadata_size($id, $size, false);
            $url[] = $new_ul_data;
        }
        $url_new = apply_filters( 'next3_rewrite_url', $url, $provider, $delivery_provider);
        
        $url_data = implode('/', $url_new);

		if ( is_wp_error( $url_data ) ) {
			return '#';
		}

        // cache 
        $to_cache[ $id ] = $url_data;
        next3_core()->compatiblility_ins->set_post_cache($id, 'next3_get_attatchment_url_preview', $to_cache);
        // cache end

        return $url_data;
    }

    public function is_current_blog( $blog_id ) {
		$default = defined( 'BLOG_ID_CURRENT_SITE' ) ? BLOG_ID_CURRENT_SITE : 1;
		if ( $default === $blog_id ) {
			return true;
		}
		return false;
	}

    public function local_subsite_url( $url ) {
		
        $siteurl = trailingslashit( next3_get_option( 'siteurl' ) );

		if ( is_multisite() && NEXT3_MULTI_SITE && ! $this->is_current_blog( get_current_blog_id() ) && 0 !== strpos( $url, $siteurl ) ) {
			$orig_siteurl = trailingslashit( apply_filters( 'next3_get_orig_siteurl', network_site_url() ) );
			$url          = str_replace( $orig_siteurl, $siteurl, $url );
		}

		return $url;
	}
    
    public function next3_get_metadata_size($id, $size = 'full', $return = false){
        
        $sizes = next3_get_post_meta( $id, '_wp_attachment_metadata');
      
        // new code
        $main_file = next3_get_post_meta( $id, '_next3_attached_file');

        // webp format
        $webp_status = false;
        if( true === next3_check_post_meta($id, 'next3_optimizer_is_converted_to_webp') ){
            if(strpos($main_file, ".webp") === false){
                $main_file .= '.webp';
            }
            $webp_status = true;
        }
        $basename = basename( $main_file );
        
        // rename file
        $rename_status = next3_get_post_meta($id, '_next3_rename_file');
        $rename_orginal = next3_get_post_meta($id, '_next3_rename_orginal');
        
        $files = [];
        if( $size == 'full'){
            if( $return ){
                $files['width'] = ($sizes['width']) ?? '';
                $files['height'] = ($sizes['height']) ?? '';
                $files['file'] = $main_file;
                $files['filesize'] = ($sizes['filesize']) ?? '';
            } else{
                $files = $main_file;
            }
        } else{
            if( isset( $sizes['sizes'][$size] )){
                $data = ($sizes['sizes'][$size]) ?? [];
                if( $return ){
                    $files['width'] = ($data['width']) ?? '';
                    $files['height'] = ($data['height']) ?? '';
                    $file_name = ($data['file']) ?? '';
                    // rename
                    if( $rename_status != false && !empty($rename_status) ){
                        $file_name = str_replace($rename_orginal, $rename_status, $file_name);
                    }
                    if( true === $webp_status ){
                        if(strpos($file_name, ".webp") === false){
                            $file_name .= '.webp';
                        }
                    }
                   
                    $file_name = str_replace( $basename, $file_name, $main_file );
                    $files['file'] = $file_name;
                    $files['filesize'] = ($data['filesize']) ?? '';
                } else{
                    $files = ($data['file']) ?? '';
                    // rename
                    if( $rename_status != false && !empty($rename_status) ){
                        $files = str_replace($rename_orginal, $rename_status, $files);
                    }
                    if( empty($files) ){
                        if( true === $webp_status ){
                            if(strpos($files, ".webp") === false){
                                $files .= '.webp';
                            }
                        }
                        $files = str_replace( $basename, $files, $main_file );
                    } else{
                        if( true === $webp_status ){
                            if(strpos($files, ".webp") === false){
                                $files .= '.webp';
                            }
                        }
                        $files = str_replace( $basename, $files, $main_file );
                    }
                }
            } else{
                if( $return ){
                    $files['width'] = ($sizes['width']) ?? '';
                    $files['height'] = ($sizes['height']) ?? '';
                    $files['file'] = $main_file;
                    $files['filesize'] = ($sizes['filesize']) ?? '';
                } else{
                    $files = $main_file;
                }
            }
        }
        return $files;
    }

    public function get_url_scheme( $use_ssl = null ) {
		if ( $this->use_ssl( $use_ssl ) ) {
			$scheme = 'https://';
		} else {
			$scheme = 'http://';
		}
		return $scheme;
	}

    public function use_ssl( $use_ssl = null ) {
        $settings_options = next3_options();
        $force_https = isset($settings_options['delivery']['force_https']) ? true : false;

        if ( is_ssl() ) {
			$use_ssl = true;
		}
		if ( ! is_bool( $use_ssl ) ) {
			$use_ssl = $force_https;
		}
		if ( empty( $use_ssl ) ) {
			$use_ssl = false;
		}
		return apply_filters( 'next3_use_ssl', $use_ssl );
	}

    public function enabled_types() {
        $settings_options = next3_options();
        $selected_files = ($settings_options['storage']['selected_files']) ?? ['all'];

		$enabled_types    = in_array('all', $selected_files) ? next3_allowed_mime_ext() : $selected_files;

		$available_types  = next3__get_available_file_types();
		$return_types     = [];
        if( empty( $available_types )){
            return $return_types;
        }
		foreach ( $available_types as $type ) {
            $mime_ext = ($type['ext']) ?? '';
            $mime_type = ($type['mime']) ?? [];

			if ( in_array( $mime_ext, $enabled_types, true ) ) {

				$extension = trim( $mime_ext, '.' );
				$extension = str_replace( ',', '|', $extension );

				$return_types[ $extension ] = $mime_type;
			}
		}

		return $return_types;
	}

    public function add_mime_type( $mime_types ) {
        $settings_options = next3_options();
        $enable_mine = ($settings_options['storage']['enable_mine']) ?? 'no';
        if($enable_mine != 'yes'){
            return $mime_types;
        }
        $enabled_types = array_map(
			function( $enabled_types ) {
				return sanitize_mime_type( ! is_array( $enabled_types ) ? $enabled_types : end($enabled_types) );
			},
			$this->enabled_types()
		);
		return array_merge( $enabled_types, $mime_types);
    }
    
    public function fix_mime_type_data( $data, $file, $filename, $mimes){
        $filetype = wp_check_filetype( $filename, $mimes );
        $data['ext'] = ($filetype['ext']) ?? '';
        $data['type'] = ($filetype['type']) ?? '';
        $data['proper_filename'] = ($filetype['proper_filename']) ?? '';
        return $data;
    }

    public function switch_to_blog( $blog_id = false ) {
		if ( ! is_multisite() && !NEXT3_MULTI_SITE) {
			return;
		}
		if ( ! $blog_id ) {
			$blog_id = defined( 'BLOG_ID_CURRENT_SITE' ) ? BLOG_ID_CURRENT_SITE : 1;
		}
		if ( $blog_id !== get_current_blog_id() ) {
			switch_to_blog( $blog_id );
		}
	}

    public function memory_exceeded( $filter_name = null ) {
		$memory_limit   = $this->get_memory_limit() * 0.9;
		$current_memory = memory_get_usage( true );
		$return         = false;
		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}
		if ( is_null( $filter_name ) || ! is_string( $filter_name ) ) {
			return $return;
		}
		return apply_filters( $filter_name, $return );
	}

    public function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			$memory_limit = '128M';
		}
		if ( ! $memory_limit || -1 == $memory_limit ) {
			$memory_limit = '32000M';
		}

		return wp_convert_hr_to_bytes( $memory_limit );
	}

	public function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    public function  natkrsort($array) 
    {
        $keys = array_keys($array);
        natsort($keys);
        foreach ($keys as $k)
        {
            $new_array[$k] = $array[$k];
        }
        $new_array = array_reverse($new_array, true);
        return $new_array;
    }

	public function getExtension( $files ){
        //$ext = strtolower(pathinfo($files, PATHINFO_EXTENSION));
        $exp = explode('.', $files);
        $ext =  strtolower(end($exp));

        $data['type'] = 'file';
        $data['ext'] = $ext;
        if(in_array($ext, ['png', 'tiff', 'jpg', 'jpeg', 'svg', 'bmp', 'gif', 'ai', 'ico'])){
            $data['type'] = 'image';
            $data['icon'] = 'dashicons dashicons-format-image';
        }else if(in_array($ext, ['pdf'])){
            $data['icon'] = 'dashicons dashicons-media-spreadsheet';
        }else if(in_array($ext, ['txt', 'json'])){
            $data['icon'] = 'dashicons dashicons-media-text';
        }else if(in_array($ext, ['php', 'html', 'htm', 'css', 'js', 'py', 'c', 'java'])){
            $data['icon'] = 'dashicons dashicons-media-code';
        }else if(in_array($ext, ['mp3', 'MP3', 'm4a', 'ogg', 'wav'])){
            $data['icon'] = 'dashicons dashicons-media-audio';
        }else if(in_array($ext, ['mp4', 'avi', 'mkv', 'mov', 'flv', 'swf', 'wmv', 'dat', 'DAT', 'm4v', 'avi', 'mpg', 'ogv', '3gp', '3g2'])){
            $data['icon'] = 'dashicons dashicons-media-video';
        }else if(in_array($ext, ['zip', 'rar', 'tar', 'iso', 'mar'])){
            $data['icon'] = 'dashicons dashicons-media-archive';
        }else{
            $data['icon'] = 'dashicons dashicons-admin-links';
        }
        return $data;
    }

	public function convert_dir( $file ){
        return str_replace('/', DIRECTORY_SEPARATOR, $file);
    }

	public function get_file_type( $file ){
        $file_name     = wp_basename( $file );
        $file_type     = wp_check_filetype_and_ext( $file, $file_name );
        return ($file_type['type']) ?? '';
    }

    public function should_gzip_file( $file_path ) {
		$file_type = $this->get_file_type( $file_path );
		$mimes     = $this->get_mime_types_to_gzip( );
		if ( in_array( $file_type, $mimes ) && is_readable( $file_path ) ) {
			return true;
		}
		return false;
	}

    protected function get_mime_types_to_gzip( ) {
		return apply_filters(
			'next3_gzip_mime_types',
			array(
				'css'   => 'text/css',
				'eot'   => 'application/vnd.ms-fontobject',
				'html'  => 'text/html',
				'ico'   => 'image/x-icon',
				'js'    => 'application/javascript',
				'json'  => 'application/json',
				'otf'   => 'application/x-font-opentype',
				'rss'   => 'application/rss+xml',
				'svg'   => 'image/svg+xml',
				'ttf'   => 'application/x-font-ttf',
				'woff'  => 'application/font-woff',
				'woff2' => 'application/font-woff2',
				'xml'   => 'application/xml',
			)
		);
	}

    public function get_offload_count( $status = true, $perpage = -1, $paged = 1){
       
        $output = get_transient( 'next3offload-count' );
		if ( $output && $status) {
            return $output;
		}

        $res_option = next3_offload_count_setup( false );

        $res = [];

        $total = ($res_option['total']) ?? 0;
        $total_clean = ($res_option['clean']) ?? 0;
        $total_offload = ($res_option['offload']) ?? 0;
        $total_unoffload = ($res_option['unoffload']) ?? 0;
        $total_wpoffload = ($res_option['wpoffload']) ?? 0;
        $total_wpoffload_done = ($res_option['wpoffload_done']) ?? 0;

        $total_css = ($res_option['total_styles']) ?? 0;
        $total_css_done = ($res_option['total_styles_done']) ?? 0;
        $total_js = ($res_option['total_scripts']) ?? 0;
        $total_js_done = ($res_option['total_scripts_done']) ?? 0;

        $total_optimize = ($res_option['total_optimize']) ?? 0;
        $total_webp_done = ($res_option['total_webp_done']) ?? 0;
        $total_compress_done = ($res_option['total_compress_done']) ?? 0;

        $total_cloud = ($res_option['total_cloud']) ?? 0;
        $total_cloud_done = ($res_option['total_cloud_done']) ?? 0;

        $total_local = ($res_option['total_local']) ?? 0;
        $total_backup = ($res_option['total_backup']) ?? 0;

        $res['total'] = $total;
        $res['unoffload'] = $total_unoffload;
        $res['offload'] = $total_offload;

        $res['clean'] = $total_clean;
        $res['wpoffload'] = $total_wpoffload;
        $res['wpoffload_done'] = $total_wpoffload_done;
        $res['total_styles'] = $total_css;
        $res['total_styles_done'] = $total_css_done;

        $res['total_scripts'] = $total_js;
        $res['total_scripts_done'] = $total_js_done;

        $res['total_optimize'] = $total_optimize;
        $res['total_webp_done'] = $total_webp_done;
        $res['total_compress_done'] = $total_compress_done;

        $res['total_cloud'] = $total_cloud;
        $res['total_local'] = $total_local;
        $res['total_backup'] = $total_backup;

        $res['clean_per'] = 0;
        $res['unoffload_per'] = 0;
        $res['offload_per'] = 0;
        $res['wpoffload_per'] = 0;
        $res['styles_per'] = 0;
        $res['scripts_per'] = 0;
        $res['webp_per'] = 0;
        $res['compress_per'] = 0;
        $res['cloudto_per'] = 0;
        $res['localto_per'] = 0;

        if( $res['offload'] != 0  && $res['total'] != 0){
            $res['offload_per'] = number_format(( $res['offload'] * 100) / $res['total'] , 2);
        }
        if( $res['unoffload'] != 0 && $res['total'] != 0){
            $res['unoffload_per'] = number_format(( $res['unoffload'] * 100) / $res['total'], 2);
        }
        if( $total_clean != 0  && $res['offload'] != 0){
            $res['clean_per'] = number_format(( $total_clean * 100) / $res['offload'], 2);
        }
        // wp offload media
        if( $total_wpoffload != 0  && $res['wpoffload_done'] != 0){
            $res['wpoffload_per'] = number_format(( $res['wpoffload_done'] * 100) / $total_wpoffload, 2);
        }
        // total assets css
        if( $total_css != 0  && $res['total_styles_done'] != 0){
            $res['styles_per'] = number_format(( $res['total_styles_done'] * 100) / $total_css, 2);
        }
        // total assets js
        if( $total_js != 0  && $res['total_scripts_done'] != 0){
            $res['scripts_per'] = number_format(( $res['total_scripts_done'] * 100) / $total_js, 2);
        }

        // webP Done
        if( $total_webp_done != 0  && $res['total_optimize'] != 0){
            $res['webp_per'] = number_format(( $total_webp_done * 100) / $res['total_optimize'], 2);
        }
        if( $total_compress_done != 0  && $res['total_optimize'] != 0){
            $res['compress_per'] = number_format(( $total_compress_done * 100) / $res['total_optimize'], 2);
        }

        // cloud to cloud
        if( $total_offload != 0  && $res['total_cloud'] != 0){
            $res['cloudto_per'] = number_format(( $res['total_cloud'] * 100) / $total_offload, 2);
        }

        // local to cloud
        if( $total_unoffload != 0  && $res['total_local'] != 0){
            $res['localto_per'] = number_format(( $res['total_local'] * 100) / $total_unoffload, 2);
        }
        
        set_transient( 'next3offload-count', $res, MINUTE_IN_SECONDS ); //HOUR_IN_SECONDS

        return $res;
    }
    

    public function create_temp_file_from_url( $url, $info = [] ){
        $response = wp_remote_get( $url );
        
		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( 200 !== $response['response']['code'] ) {
			return false;
		}

        $exp = explode('/', $url);
        $filename = end($exp);
        if( isset( $info['basename']) ){
            $filename = !empty( $info['basename'] ) ? $info['basename'] : $filename;
        }

		return $this->create_temp_file( $response['body'], $filename, $info);
    }

    public function create_temp_file( $file_content, $file_name, $info = [] ) {
		if( isset( $info['dirname']) ){
            $uploads = wp_get_upload_dir();
            
            $temp_filename = !empty( $info['dirname'] ) ? $info['dirname'] : $uploads['basedir'];
            $temp_filename .= '/' . $file_name;
        } else{
            $temp_filename = $this->create_unique_dir() . $file_name;
        }
		file_put_contents( $temp_filename, $file_content ); // phpcs:ignore
		return $temp_filename;
	}

    public function create_unique_dir() {
		$unique_dir_path = $this->get_temp_dir() . 'tmp' . DIRECTORY_SEPARATOR;
        if( !is_dir($unique_dir_path) ){
            wp_mkdir_p( $unique_dir_path );
            chmod( $unique_dir_path, 0777 );
        }
		return $unique_dir_path;
	}

    public function get_temp_dir() {
		if ( ! $this->temp_dir ) {
			$wp_upload_dir = wp_upload_dir();
			$this->temp_dir = implode( DIRECTORY_SEPARATOR, [ $wp_upload_dir['basedir'], 'next3aws' ] ) . DIRECTORY_SEPARATOR;

			if ( ! is_dir( $this->temp_dir ) ) {
				wp_mkdir_p( $this->temp_dir );
                chmod( $this->temp_dir, 0777 );
			}
		}
		return $this->temp_dir;
	}

    public function remove_file_or_dir($dir) {
        if( is_file($dir) ){
            @unlink($dir);
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$todo($fileinfo->getRealPath());
        }
        @rmdir($dir);
        return;
    }

    public static function get_attachment_file_paths( $attachment_id, $exists_locally = true, $meta = false, $include_backups = true ) {
        
        $file_path = next3_get_attached_file( $attachment_id, true );
        $paths     = [
            'primary' => [
                'path' => $file_path,
                'file' => next3_get_post_meta( $attachment_id, '_wp_attached_file')
            ],
        ];

        if ( ! $meta ) {
            //$meta = next3_get_post_meta( $attachment_id, '_wp_attachment_metadata');
            $meta = next3_wp_get_attachment_metadata( $attachment_id, true);
        }

        if ( is_wp_error( $meta ) ) {
            return $paths;
        }

        $file_name = wp_basename( $file_path );

        if ( isset( $meta['file'] ) ) {
            $paths['file'] = $meta['file'];
            $paths['path'] = str_replace( $file_name, wp_basename( $meta['file'] ), $file_path );

            if ( $paths[ 'primary' ]['path'] === $paths['path'] ) {
                unset( $paths['file'] );
                unset( $paths['path'] );
            }
        }

        // Thumbnil
        if ( isset( $meta['thumb'] ) ) {
            $paths['thumb']['file'] = $meta['thumb'];
            $paths['thumb']['path'] = str_replace( $file_name, $meta['thumb'], $file_path );
        }

        // Original Image
        if ( isset( $meta['original_image'] ) ) {
            $paths['original_image']['file'] = $meta['original_image'];
            $paths['original_image']['path'] = str_replace( $file_name, $meta['original_image'], $file_path );
        }

        // all Sizes
        if ( isset( $meta['sizes'] ) ) {
            foreach ( $meta['sizes'] as $size => $file ) {
                if ( isset( $file['file'] ) ) {
                    $paths[ $size ]['file'] = $file['file'];
                    $paths[ $size ]['path'] = str_replace( $file_name, $file['file'], $file_path );
                }
            }
        }

        $backups = next3_get_post_meta( $attachment_id, '_wp_attachment_backup_sizes');

        // Backups sizes
        if ( $include_backups && is_array( $backups ) ) {
            foreach ( $backups as $size => $file ) {
                if ( isset( $file['file'] ) ) {
                    $paths[ $size ]['file'] = $file['file'];
                    $paths[ $size ]['path'] = str_replace( $file_name, $file['file'], $file_path );
                }
            }
        }
        $paths = apply_filters( 'next3_attachment_file_paths', $paths, $attachment_id, $meta );

        if ( $exists_locally ) {
            foreach ( $paths as $key => $path ) {
                if ( ! file_exists( $path['path'] ) ) {
                    unset( $paths[ $key ] );
                }
            }
        }

        return $paths;
    }
    
    public static function instance(){
		if (!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
	}
}