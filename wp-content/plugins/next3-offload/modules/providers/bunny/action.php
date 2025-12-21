<?php
namespace Next3Offload\Modules\Providers\Bunny;
defined( 'ABSPATH' ) || exit;

use \Next3Bunny\BunnyCdn\BunnyAPIException;
use \Next3Bunny\BunnyCdn\BunnyAPI;
use \Next3Bunny\BunnyCdn\BunnyAPIStorage;

class N3aws_Action{

    private static $instance;

    protected $api_url = 'https://api.bunny.net/';

    private $s3Client_status;
    private $s3Client = false;
    private $aws_client;

    public $default_region = 'de';
    public $default_bucket = '';
	protected $default_domain = 'b.cdn.net';
	protected $default_storage = 'storage.bunnycdn.com';

	protected $blocked_buckets = [];
    protected static $buckets_check = [];

    protected static $block_public_access_allowed = false;

    private $client_args = [];

    private $settingData = [];
    
    public function __construct(){
        require_once( next3_core()->plugin::plugin_dir(). 'vendor/bunny/autoload.php' );

        $config = defined('NEXT3_SETTINGS') ? unserialize(NEXT3_SETTINGS) : [];
        if( !empty($config) ){
            $endpoint = ($config['endpoint']) ?? '';
            if( !empty($endpoint) ){
                $this->default_domain = str_replace(['https://','http://'], '', $endpoint);
            }
        }
    }


    public function request( $status ){
        if( !$status ){
            return $this->handle_request();
        }
        
        $data = next3_core()->provider_ins->settingData;
        $provider = next3_core()->provider_ins->provider;

        $this->settingData = ($data[ $provider ]) ?? [];
        
        return $this->configration()->handle_request();
    }

    public function configration( $region = '', $bucket = ''){
        $data = $this->get_access();

        $api_key = ($data['api_key']) ?? '';
        $access_key = ($data['access_key']) ?? '';
        $default_bucket = ($data['default_bucket']) ?? $bucket;
        $default_region = ($data['default_region']) ?? '';
        $default_region = !empty($default_region) ? $default_region : $this->default_region;
        
        $this->default_bucket = !empty($default_bucket) ? $default_bucket : $this->default_bucket;
        $this->default_region = $default_region;

        if( !empty($region) ){
            $this->default_region = $region;
        }
       
        $bunnyApi = new BunnyAPI();
        $bunnyApi->apiKey( $api_key );

        try {
            
            if (!$bunnyApi->constApiKeySet()) {
                next3_core()->provider_ins->saveLogs( json_encode("You must provide an API key") );
                return $this;
            }
            $bunny = new BunnyAPIStorage();
            $bunny->zoneConnect($this->default_bucket, $access_key, $this->default_region);
            $this->s3Client = $bunny;
            $this->s3Client_status = 'success';
            
        } catch (BunnyAPIException $e) {
            next3_core()->provider_ins->saveLogs( json_encode($e->errorMessage()) );
        }
        return $this;
    }

    protected function handle_request(){
        return $this;
    }

    public function check_configration(){
        if( $this->s3Client_status == 'success' ){
            return true;
        }
        return false;
    }
 
    public function getStatus(){
         return $this->s3Client_status;
    }
 
    public function get_buckets(){
        
        $data = $this->get_access();
        $api_key = ($data['api_key']) ?? '';

        $list = [];
        $region_obj = $this->s3Client->listStorageZones();
        if( !empty($region_obj) && !isset($region_obj['http_code']) ){
            foreach($region_obj as $v){
                $name = ($v['Name']) ?? '';
                $password = ($v['Password']) ?? '';
                $id = ($v['Id']) ?? '';
                $region = ($v['Region']) ?? '';

                if( empty($name) || empty($id) ){
                    continue;
                }
                $list[ $id ] = $name;
            }
        }
        return [ 'status' => true, 'data' => $region_obj];
    }

    public function get_bucket_location( $bucket ){
        $bucket = self::sanitize_bucket($bucket);
        $region = '';

        $region_obj = $this->s3Client->listStorageZones();
        if( !empty($region_obj) && !isset($region_obj['http_code']) ){
            foreach($region_obj as $v){
                $name = ($v['Name']) ?? '';
                $password = ($v['Password']) ?? '';
                $id = ($v['Id']) ?? '';
                $region = ($v['Region']) ?? '';

                if( empty($name) || empty($id) ){
                    continue;
                }
                if( $name == $bucket){
                    $region = $region;
                }
            }
        }
		return strip_tags($region);
    }

    public function check_write_permission( $bucket = '', $region = ''){
        $default_bucket = ($this->settingData['default_bucket']) ?? $this->default_bucket;
        $default_region = ($this->settingData['default_region']) ?? $this->default_region;
        
        $bucket = empty( $bucket ) ? $default_bucket : strtolower(sanitize_text_field($bucket));
		if ( '' === $bucket ) {
			$msg = __( 'No bucket name provided.', 'next3-offload' );
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
			return false;
		}
        $this->default_bucket = $bucket;
        $this->default_region = !empty($region) ? $region : $default_region;
        
        $key           = 'next3-permission-check.txt';
		$file_contents = __( 'This is a test file to check if the user has write permission to the bucket. Delete me if found.', 'next3-offload' );
        
		$can_write = $this->can_write( $this->default_bucket, $key, $file_contents );
        
        if ( is_string( $can_write ) || $can_write == false) {
			$error_msg = sprintf( __( 'There was an error attempting to check the permissions of the bucket %s: %s', 'next3-offload' ), $bucket, $can_write );
			next3_core()->provider_ins->saveLogs( json_encode( $error_msg ) );
            return false;
		}
		return $can_write;
    }

    public function get_createBucket( $bucket, $region = ''){
        $bucket = empty( $bucket ) ? false : strtolower(sanitize_text_field($bucket));
		if ( false === $bucket ) {
			$msg = __( 'No bucket name provided.', 'next3-offload' );
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
			return [ 'status' => false, 'msg' => $msg];
		}

        $bucket = self::sanitize_bucket($bucket);
        $region = !empty($region) ? self::sanitize_region($region) : $this->default_region;
        $origin_url = '';

        $region_obj = $this->s3Client->addStorageZone($bucket, $origin_url, $region);
        if( !isset($region_obj['http_code']) ){
            return [ 'status' => true, 'msg' => 'Success: created storage successfully'];
        }
        return [ 'status' => false, 'msg' => 'Error: can not create storage'];
    }

    public function get_deleteBucket( $bucket ){
        $bucket = empty( $bucket ) ? false : strtolower(sanitize_text_field($bucket));
		if ( false === $bucket ) {
			$msg = __( 'No bucket name provided.', 'next3-offload' );
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
			return [ 'status' => false, 'msg' => $msg];
		}
        $bucket = self::sanitize_bucket($bucket);

        $region_obj = $this->s3Client->deleteStorageZone($bucket);
        if( !isset($region_obj['http_code']) ){
            return [ 'status' => true, 'msg' => 'Success: deleted storage successfully'];
        }

        return [ 'status' => false, 'msg' => 'Error: can not delete storage'];
    }

    public function get_deleteObjects( $bucket, $file_name = '', $all = false){
        $data = $this->get_access();
        $default_bucket = ($data['default_bucket']) ?? '';

        $bucket = empty( $bucket ) ? $default_bucket : strtolower(sanitize_text_field($bucket));
		if ( empty($bucket) ) {
			$msg = __( 'No bucket name provided.', 'next3-offload' );
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
			return [ 'status' => false, 'msg' => $msg];
		}
        
        if( !$this->s3Client ){
            return [ 'status' => false, 'msg' => esc_html__('Error!! provider connection not established.', 'next3-offload')];
        }
       
        $upload = $this->s3Client->deleteFile( $file_name);
        if( isset($upload['response']) && $upload['response'] == 'success'){
            return [ 'status' => true, 'msg' => esc_html__('Successfully Deleted this file', 'next3-offload') ];
        }

        return [ 'status' => false, 'msg' => ''];
    }

    public function get_listObjects( $bucket = '' ){
        
        $data = $this->get_access();
        $default_bucket = ($data['default_bucket']) ?? '';

        $list = [];

        if( empty($bucket) ){
            $bucket = '/'. $default_bucket. '/';
        }
        $bucket = self::sanitize_bucket($bucket);

        if( !$this->s3Client ){
            return $list;
        }

        $list_obj = $this->s3Client->getStorageObjects( $bucket );
       
        if( !empty($list_obj) ){
            foreach($list_obj as $v){
                if( !isset( $v->Guid )){
                    continue;
                }
                $new = [];
                $zone = ($v->StorageZoneName) ?? '';
                $new['guid'] = ($v->Guid) ?? '';

                $path = ($v->Path) ?? '';
                $key = str_replace('/'.$zone . '/',  '', $path);

                $new['path'] = $path;
                
                
                $new['zone'] = $zone;
                $new['size'] = ($v->Length) ?? 0;
                $new['file'] = ($v->ObjectName) ?? '';
                $new['folder'] = ($v->IsDirectory) ?? false;
                $new['key'] = $key;
                $new['key_name'] = $key . $new['file'];

                $list[] = $new;
            }
        }
        return [ 'status' => true, 'data' => $list];
    }

    public function get_manage_files($bucket, $folder = [], $return = true){
        
        if( !empty($folder) ){
            $path = [];
            foreach($folder as $f){
                $path[] = str_replace('_nx_', '', $f);
            }
            if( !empty($path) ){
                $bucket .= implode('/', $path);
            }
        }
        return $this->get_files($bucket, $return);
    }

    public function get_files( $bucket = '', $return = true){
        $filearray = [];

        $file = __DIR__ . '/save/'.$bucket.'.json';
        if($return && is_readable($file) ){
            return json_decode(
                file_get_contents($file),
                true
            );
        }
        
        $files = $this->get_listObjects( $bucket );
        if( $files['status'] == false){
            return [];
        }
        $data = ($files['data']) ?? [];
        if( !empty($data) ){
            foreach($data as $f) {
				
                $folder = ($f['folder']) ?? false;
                $file = ($f['file']) ?? '';
                $size = ($f['size']) ?? '';
                $path = ($f['path']) ?? '';
                $key = ($f['key']) ?? '';
                $key_name = ($f['key_name']) ?? '';

                $url = '';
                
                $newfiles = [
                    'size' => $size,
                    'size_byte' => ($folder == false) ? next3_core()->action_ins->formatSizeUnits($size) : '',
                    'path' => $path . $file,
                ];

                if( !$folder ){
                    $url = next3_core()->action_ins->get_url_preview(true, $key_name, 'bunny_url');
                    $newfiles['url'] = $url;
                    $newfiles['name'] = $file;

                    $filearray[] = $newfiles;
                } else {
                    $filearray['_nx_'.$file] = $newfiles;
                }
                
			}
        }
       
        return $filearray;
    }

    public function get_save_json_data(  $bucket = '' ){
        
        $file = __DIR__ . '/save/'.$bucket.'.json';

        if(is_readable($file) ){
            return json_decode(
                file_get_contents($file),
                true
            );
        }
        return $this->get_files($bucket, false);
    }

    public function putObject( $bucket, $full_path, $file_name = '', $type = ''){
        $data = $this->get_access();
        $default_bucket = ($data['default_bucket']) ?? '';
        
        $bucket = empty( $bucket ) ? $default_bucket : strtolower(sanitize_text_field($bucket));
		if ( empty($bucket) ) {
			$msg = __( 'No Storage name provided.', 'next3-offload' );
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
			return [ 'status' => false, 'msg' => $msg];
		}
        
        if( empty( $full_path ) || !is_file($full_path) ){
            return [ 'status' => false, 'msg' => esc_html__('Invalid File Path.', 'next3-offload')];
        }
        
        //enable read/write permission
        chmod($full_path, 0777);
       
        if( empty($file_name) ){
            $exp = explode('/', $full_path);
            $file_name = end($exp);
        }

        if( !$this->s3Client ){
            return [ 'status' => false, 'msg' => esc_html__('Error!! provider connection not established.', 'next3-offload')];
        }

        // check read/write permission
        if( !is_writable( $full_path )){
            return [ 'status' => false, 'msg' => esc_html__('Error! file not executable (enable read/write permission).', 'next3-offload')];
        }
        
        $upload = $this->s3Client->uploadFile($full_path, $file_name );
        
        if( isset($upload['response']) && $upload['response'] == 'success'){
            $url = next3_core()->action_ins->get_url_preview(true, $file_name, 'bunny_url');
            return [ 'status' => true, 'data' => $url ];
        }
        return [ 'status' => false, 'msg' => esc_html__('Error! file not offload.', 'next3-offload')];
    }

    public function can_write( $bucket, $key, $file_contents ) {
        $bucket = empty( $bucket ) ? false : strtolower(sanitize_text_field($bucket));
		if ( false === $bucket ) {
			$msg = __( 'No bucket name provided.', 'next3-offload' );
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
			return false;
		}
       
        $file_dir = __DIR__ . '/'. $key;
        
        // read/write permission
        chmod($file_dir, 0777);

        file_put_contents( $file_dir, $file_contents);

        // re-config action
        $obj = $this->configration($this->default_region, $bucket);

        if( !$obj->s3Client ){
            next3_core()->provider_ins->saveLogs( json_encode('Error!! provider connection not established.') );
            return false;
        }

        $upload = $obj->s3Client->uploadFile($file_dir, $key );
       
        if( isset($upload['response']) && $upload['response'] == 'success'){
            $obj->s3Client->deleteFileHTTP( $key );
            return true;
        }
		return false;
	}

    public function public_access_blocked( $bucket = '', $region = '' ){
        $default_bucket = ($this->settingData['default_bucket']) ?? $this->default_bucket;
        $default_region = ($this->settingData['default_region']) ?? $this->default_region;
        
        $bucket = empty( $bucket ) ? $default_bucket : strtolower(sanitize_text_field($bucket));
		if ( '' === $bucket ) {
			$msg = __( 'No bucket name provided.', 'next3-offload' );
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
			return false;
		}
        $this->default_bucket = $bucket;
        $this->default_region = !empty($region) ? $region : $default_region;

        // re-config action
        $obj = $this->configration($this->default_region);
        
        $blocked = false;
        
		return $blocked;
    }

    public function block_public_access( $bucket, $block = false ) {
		if ( empty( $bucket ) || ! is_bool( $block ) ) {
			return false;
		}
        $bucket = self::sanitize_bucket($bucket);

		return true;
	}
    private static function sanitize_bucket( $bucket ) {
		$bucket = sanitize_text_field( $bucket );
		return empty( $bucket ) ? false : strtolower( $bucket );
	}

    private static function sanitize_region( $region ) {
		if ( ! is_string( $region ) ) {
			return $region;
		}
		$region = strtolower( sanitize_text_field($region) );
		switch ( $region ) {
			case 'eu':
				$region = 'eu';
				break;
		}
		return $region;
	}

    public function file_read(){
        return ['public-read' => 'Public Read', 'private' => 'Private Read', 'public-read-write' => 'Public Read Write', 'authenticated-read' => 'Authenticated Read'];
    }

    public function get_access(){
        $type = ($this->settingData['type']) ?? '';
        $config_data = [];
        $config_data['default_bucket'] = ($this->settingData['default_bucket']) ?? '';
        $config_data['default_region'] = ($this->settingData['default_region']) ?? 'de';
        
        if($type == 'wp'){
            $config = defined('NEXT3_SETTINGS') ? unserialize(NEXT3_SETTINGS) : [];
            if( !empty($config) ){
                $config_data['api_key'] = ($config['api-key']) ?? '';
                $config_data['access_key'] = ($config['access-key']) ?? $config_data['api_key'];
                if( isset($config['region']) ){
                    $config_data['default_region'] = !empty($config['region']) ? $config['region'] : $config_data['default_region'];
                }
            }
        }else{
            $config = ($this->settingData['credentails']) ?? [];
            if( !empty($config) ){
                $config_data['api_key'] = ($config['api_key']) ?? '';
                $config_data['access_key'] = ($config['access_key']) ?? $config_data['api_key'];
            }
        }
        return $config_data;
    }

    public function get_regions( $name_re = ''){
        
        $list = [];
        $data = $this->get_access();
        
        $access_key = ($data['access_key']) ?? '';

        $args = [
            'version' => 'latest',
        ];
       
        $response = wp_remote_post( $this->api_url . 'region', [
                'method' => 'GET',
                'data_format' => 'body',
                'timeout' => 45,
                'headers' => [
                    'AccessKey' => $access_key,
                    'accept' => 'application/json',
                ],
                //'body' => $args
            ]
        );
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            next3_core()->provider_ins->saveLogs( json_encode( $error_message ) );
        } else {
            $region_obj = isset($response['body']) ? json_decode($response['body'], true) : [];
            if( !empty($region_obj) ){
                foreach($region_obj as $v){
                    $name = ($v['Name']) ?? '';
                    $code = ($v['RegionCode']) ?? '';
                    $id = ($v['Id']) ?? '';

                    if( empty($name) || empty($code) ){
                        continue;
                    }
                    $list[ strtolower($code) ] = str_replace([': ', ', '], [' (', ') '], $name);
                }
            }
        }

        if( !empty($name_re) ){
            $name_re = strip_tags($name_re);
            return ($list[$name_re]) ?? '';
        }

        return apply_filters('next3/regions/bunny', $list);
    }

    public static function instance(){
           
        if (!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
    }
}