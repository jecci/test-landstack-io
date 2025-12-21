<?php
namespace Next3Offload\Utilities\Check;

defined( 'ABSPATH' ) || exit;

use \Next3Offload\Modules\Proactive\N3aws_Init as Init;
use \Next3Offload\N3aws_Plugin as N3aws_Plugin;

Class N3aws_Valid{
	
    private static $instance;

    private $product_id = 286;

    public function init() {
        if(current_user_can('manage_options')){
             // load script
             add_action( 'admin_enqueue_scripts', [ $this , '_admin_scripts'] );

            add_action( 'wp_ajax_nextactive_next3aws', [$this, '_active_license'] );
            add_action( 'wp_ajax_nextinactive_next3aws', [$this, '_inactive_license'] );
        }
        
    }

    public function _admin_scripts(){
        wp_register_script( 'next3aws-valitpro', N3aws_Plugin::plugin_url() . 'utilities/check/assets/js/valid.js', ['nextJs'], N3aws_Plugin::version(), true );
        $screen = get_current_screen();
        if( in_array($screen->id, [ 'toplevel_page_next3aws', 'next3_page_next3config', 'toplevel_page_next3aws-network']) ){
            wp_enqueue_script('next3aws-valitpro');
            wp_localize_script('next3aws-valitpro', 'nextactive', array( 'siteurl' => next3_get_option('siteurl'), 'nonce' => wp_create_nonce( 'wp_rest' ), 'resturl' => next3_rest_url(), 'next3_admin_url' => get_admin_url(), 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
        }
    }

    public function _active_license(){
        $key = isset($_POST['key_license']) ? sanitize_text_field($_POST['key_license']) : '';
        $result = [
            'status' => 'error',
            'message' => esc_html__('Something went wrong', 'next3-offload')
        ];
       
        $config['key'] = $key;
        $config['eddid'] = $this->product_id;
        $config['eddv'] = '1.0.0';
        $result = Init::instance()->activate( $config );
        _e( $this->__json($result) );
        wp_die();
    }

    private function __json( $result ){
        if(is_array($result) || is_object($result)){
            return  json_encode($result);
        }else{
            return $result;
        }
    }

    public function save_pro($v, $output){

        $ids_key = '-nx'.$this->product_id.'nx-';
        $key = empty($v) ? '' : htmlspecialchars($v);
        if(empty($key)){
            next3_update_option('__validate_author_next3aws__', false);
            return;
        }
        if ( 0 === strpos( $key, $ids_key ) ) {
            $key = $key.$ids_key.'singlelicense';
        }

        $exp_key = explode($ids_key, $key);
        $data['status'] = 'active';
        $data['keys'] = $v; 
        $data['key'] = current($exp_key);
        $data['checksum'] = end($exp_key);
        $data['domain'] = next3_home_url();
        
       
        // free license check
        $fre = explode('-', $v);
        $kfree = current($fre);
        $fitem = end($fre);
        if($kfree == 'free'){
            $timeout = ($output->timeout) ?? time();
            $todate = date("Y-m-d h:i:s");
            // date modify
            $dates = new \DateTime("$todate");
            $dates->modify('+7 days');
            $data['limit'] = strtotime($dates->format('Y-m-d h:i:s'));
            $data['typeitem'] = 'check';
            set_transient( '__validate_time_next3aws__', $data['limit'], (3600 * 24 * 7) ); 
        }
        // end free license check

        $data['date'] = date("Y-m-d h:i:s");
        $data['time'] = time();
        $data['datalicense'] = ($output->datalicense) ?? '';

        $file =  $this->get_file_path() . $data['key'] . '.json';
       
        file_put_contents($file, 
            $this->__json($data)
        );
        if( file_exists($file) && is_readable($file)){
            next3_update_option('__validate_author_next3aws_keys__', $data['key']);
            return true;
        }
        return false;
    }

    public function get_pro( $k){
        $file =  $this->get_file_path() . $k . '.json';
        if(file_exists($file) && is_readable($file) ){
            return json_decode(
                    file_get_contents($file)
                );
        }
        return null;
    }

    public function _get_action(){
       
        $key = next3_get_option('__validate_author_next3aws_keys__', '');
        $data = $this->get_pro($key);
       
        if( !empty($data) ){
            $status = next3_get_option('__validate_author_next3aws__', 'inactive');
            $domain = isset($data->domain) ? $data->domain : '';
            $key_data = isset($data->key) ? $data->key : '';
            
            if ( is_multisite() && !empty($domain) ) {
                return 'active';
            }
            // free license check
            $typeitem = isset($data->typeitem) ? $data->typeitem : ''; 
            if($typeitem == 'check'){
                $todate = time();
                $gettime   = get_transient( '__validate_time_next3aws__');
                if(  true == $gettime && !empty($gettime) && $todate <= $gettime ){
                    if( self::_site_url($domain) == self::_site_url(next3_home_url()) && $key_data == $key){
                        return 'active';
                    }
                }
                return 'inactive';
            }
            //end free license
            if( self::_site_url($domain) == self::_site_url(next3_home_url()) && $key_data == $key){
                return 'active';
            }
        }
        return 'inactive';
    }

    public static function _site_url( $url ){
		$url = strtolower( $url );
		$url = str_replace( array( '://www.', ':/www.' ), '://', $url );
		$url = str_replace( array( 'http://', 'https://', 'http:/', 'https:/' ), '', $url );
		$port = parse_url( $url, PHP_URL_PORT );
		if( $port ) {
			$url = str_replace( ':' . $port, '', $url );
		}
		return $url;
	}

    public function _inactive_license(){
        
        $key = isset($_GET['keys']) ? sanitize_text_field($_GET['keys']) : '';

        $key_save = next3_get_option('__validate_author_next3aws_keys__', '');
        $result['status'] = false;
        
        if( !empty($this->get_pro($key_save)) && $key == $key_save){
            $data = $this->get_pro($key);
            $domain = isset($data->domain) ? $data->domain : '';
            $key_data = isset($data->key) ? $data->key : '';
            if( self::_site_url($domain) == self::_site_url(next3_home_url()) && $key_data == $key_save){
                if( $this->revoke_save($key_save) ){
                    next3_delete_option('__validate_author_next3aws__');
                    next3_delete_option('__validate_author_next3aws_keys__');
                    $checksum = isset($data->checksum) ? $data->checksum : '';
                    $keys = isset($data->keys) ? $data->keys : '';

                    $config['key'] = $keys;
                    $config['eddid'] = $this->product_id;
                    $config['eddv'] = '1.0.0';
                    $result_revoke = Init::instance()->inactivate( $config );

                    $result['status'] = true;
                    $result['url'] = 'https://account.themedev.net/?views=products';
                }
            }
        }

        _e( $this->__json($result) );
        wp_die();
    }

    public function revoke_save($k){
        $file =  $this->get_file_path() . $k . '.json';
        if( file_exists( $file) && is_readable($file) ){
            unlink($file);
            return true;
        }
        return false;
    }
    
    public function get_file_path(){

        $default_dir = __DIR__ .'/views/key/';
        
        if ( is_multisite()  && NEXT3_MULTI_SITE ) {
            $site = get_current_blog_id();
            
            $uploads = wp_get_upload_dir();
            $uploads['basedir'] = WP_CONTENT_DIR . '/uploads/';
            $uploads['baseurl'] = WP_CONTENT_URL . '/uploads/';
            $uploads['path'] = $uploads['basedir'] . 'sites/' . $site . '/';
            $uploads['url'] = $uploads['baseurl'] . 'sites/' . $site  . '/';
        } else {
            $uploads = wp_get_upload_dir();
        }
        
        if ( false === $uploads['error'] ) {
            $default_dir = $uploads['basedir'] . '/next3/';
            if( !is_dir($default_dir) ){
                wp_mkdir_p($default_dir);
            }
        }
        return $default_dir;
    }

    public static function instance(){
		if (!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
	}

}