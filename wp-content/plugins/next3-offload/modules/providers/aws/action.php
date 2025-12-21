<?php
namespace Next3Offload\Modules\Providers\Aws;
defined( 'ABSPATH' ) || exit;


use \Aws\S3\S3Client as S3Client;
use \Aws\S3\Exception\S3Exception as S3Exception;
use \Aws\Sdk as Sdk;

use \Aws\CloudFront\CloudFrontClient;

class N3aws_Action{

    private static $instance;

    protected $console_url = 'https://console.aws.amazon.com/s3/buckets/';

    private $s3Client_status;
    private $s3Client;
    private $aws_client;
    public $default_region = 'eu-west-2';
    public $default_bucket = '';
	protected $default_domain = 'amazonaws.com';
	protected $blocked_buckets = [];
    protected static $buckets_check = [];

    protected $default_domain_cloudfront = 'cloudfront.net';

	protected $console_url_cloudfront = 'https://console.aws.amazon.com/cloudfront/home';

    private $settingData = [];
    
    public function __construct(){
        require_once( next3_core()->plugin::plugin_dir(). 'vendor/amazon/aws-autoloader.php' );

        if ( ! function_exists( 'idn_to_ascii' ) && ! defined( 'IDNA_DEFAULT' ) ) {
			define( 'IDNA_DEFAULT', 0 );
		}
        
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

    public function configration( $region = ''){
        $data = $this->get_access();
        $access_key = ($data['access_key']) ?? '';
        $secret_key = ($data['secret_key']) ?? '';
        $default_bucket = ($data['default_bucket']) ?? '';
        $default_region = ($data['default_region']) ?? '';
        $default_region = !empty($default_region) ? $default_region : $this->default_region;
        
        $this->default_bucket = !empty($default_bucket) ? $default_bucket : $this->default_bucket;
        $this->default_region = !empty($region) && strlen($region) > 3 ? $region : $default_region;
        
        $endpoint = ($data['endpoint']) ?? '';
        
        $args = [
            'version' => 'latest'
        ];

        $settings_options = next3_options();
        $delivery_provider = ($settings_options['delivery']['provider']) ?? 'aws';
        if( $region == 'auto' && $delivery_provider == 'cloudflare'){
            $endpoint_id = ($settings_options['delivery']['provider_data'][$delivery_provider]) ?? '';
            if( !empty($endpoint_id) ){
                $endpoint = 'https://' . $endpoint_id . '.r2.cloudflarestorage.com';
            }
            $region = '';
        } else {
            $region = $this->default_region;
        }
        try {

            if( !empty($region) ){
                $args['region'] = $region;
            }
            if( !empty($endpoint) ){
                $args['endpoint'] = $endpoint;
            }
            $args = array_merge( array(
                'credentials' => array(
                    'key'    => $access_key,
                    'secret' => $secret_key,
                ),
            ), $args );
            $sdk = new Sdk( $args );

            if( !empty($region) ){
                $this->s3Client = $sdk->createS3( $args );
            }else{
                $this->s3Client = $sdk->createMultiRegionS3( $args );
            }
            $this->s3Client_status = 'success';

        } catch (S3Exception $e) {
            $this->s3Client = false;
            $this->s3Client_status = 'error';
            next3_core()->provider_ins->saveLogs( json_encode($e->getMessage()) );
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
        $list = [];
        try {
            $buckets = $this->s3Client->listBuckets();
            $data = ($buckets['Buckets']) ?? [];
            foreach($data as $v):
                $list[] = ($v['Name']) ?? '';
            endforeach;
        } catch (S3Exception $e) {
            $this->s3Client_status = 'error';
            next3_core()->provider_ins->saveLogs( json_encode( $e->getMessage() ) );
            return [ 'status' => false, 'msg' => $e->getMessage()];
        }
        return [ 'status' => true, 'data' => $list];
    }

    public function get_bucket_location( $bucket ){
        $bucket = self::sanitize_bucket($bucket);
        $region = '';
        if ( '' === $bucket ) {
			return $region;
		}
        $args = array( 'Bucket' => $bucket );
        try {
            $location = $this->s3Client->getBucketLocation( $args );
            $region   = empty( $location['LocationConstraint'] ) ? '' : $location['LocationConstraint'];
        }catch ( S3Exception $e ) {
			next3_core()->provider_ins->saveLogs( json_encode($e->getMessage()) );
		}
		return $region;
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
        $default_region = !empty($region) ? $region : $default_region;

        $this->default_region = empty( $default_region ) ? $this->get_bucket_location($bucket) : $default_region;
        if ( isset( self::$buckets_check[ $bucket ] ) ) {
			return self::$buckets_check[ $bucket ];
		}
        
        $key           = 'next3-permission-check.txt';
		$file_contents = __( 'This is a test file to check if the user has write permission to the bucket. Delete me if found.', 'next3-offload' );
        
		$can_write = $this->can_write( $bucket, $key, $file_contents );
        
        if ( is_string( $can_write ) || $can_write == false) {
			$error_msg = sprintf( __( 'There was an error attempting to check the permissions of the bucket %s: %s', 'next3-offload' ), $bucket, $can_write );
			next3_core()->provider_ins->saveLogs( json_encode( $error_msg ) );
            return false;
		}
		self::$buckets_check[ $bucket ] = $can_write;
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

        $listofbuckets = $this->get_buckets();
        $listofbuckets = ($listofbuckets['data']) ?? [];
        if( in_array($bucket, $listofbuckets)){
            $msg = "Sorry this $bucket already created.";
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
            return [ 'status' => false, 'msg' => $msg];
        }

        try {
            $args = array( 'Bucket' => $bucket );

            $obj = $this->s3Client;

            if ( ! is_null( $region ) && $this->default_region !== $region ) {
				$args['CreateBucketConfiguration']['LocationConstraint'] = $region;
                $obj = $this->configration( $region )->s3Client;
			}

            $obj->createBucket( $args );

            $msg = "Successfully $bucket create.";
            return [ 'status' => true, 'msg' => $msg];
        } catch (S3Exception $exception) {
            $msg = "Failed to create bucket $bucket with error: " . $exception->getMessage();
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
            return [ 'status' => false, 'msg' => $msg];
        }
        return [ 'status' => false, 'msg' => ''];
    }

    public function get_deleteBucket( $bucket ){
        $bucket = empty( $bucket ) ? false : strtolower(sanitize_text_field($bucket));
		if ( false === $bucket ) {
			$msg = __( 'No bucket name provided.', 'next3-offload' );
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
			return [ 'status' => false, 'msg' => $msg];
		}
        $bucket = self::sanitize_bucket($bucket);

        $listofbuckets = $this->get_buckets();
        $listofbuckets = ($listofbuckets['data']) ?? [];
        if( !in_array($bucket, $listofbuckets)){
            $msg = "Sorry invalid $bucket.";
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
            return [ 'status' => false, 'msg' => $msg];
        }
        $this->get_deleteObjects( $bucket, '', true);
        try {
            $this->s3Client->deleteBucket(
                [
                    'Bucket' => $bucket
                ]
            );
            $msg = "Deleted bucket this $bucket.";
            return [ 'status' => true, 'msg' => $msg];
        } catch (S3Exception $exception) {
            $msg = "Failed to delete bucket $bucket with error: " . $exception->getMessage();
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
            return [ 'status' => false, 'msg' => $msg];
        }
        return [ 'status' => false, 'msg' => ''];
    }

    public function get_deleteObjects( $bucket, $file_name = '', $all = false){
        $bucket = empty( $bucket ) ? false : strtolower(sanitize_text_field($bucket));
		if ( false === $bucket ) {
			$msg = __( 'No bucket name provided.', 'next3-offload' );
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
			return [ 'status' => false, 'msg' => $msg];
		}
        $bucket = self::sanitize_bucket($bucket);

        try {
            $objects = [];
            if( $all ){
                $contents = $this->s3Client->listObjects([
                    'Bucket' => $bucket,
                ]);
                foreach ($contents['Contents'] as $content) {
                    $objects[] = [
                        'Key' => ($content['Key']) ?? '',
                    ];
                }
            }
            $args['Bucket'] = $bucket;
            if( !empty( $file_name ) ){
                $args['Key'] = $file_name;
                $objects[] = [
                    'Key' => $file_name
                ];
            }
            if( !empty( $objects ) ){
                $args['Delete']['Objects'] = $objects;
                $this->s3Client->deleteObjects($args);
            }

            if( $all ){
                $check = $this->s3Client->listObjects([
                    'Bucket' => $bucket,
                ]);
                if (count($check) <= 0) {
                    $msg = "Deleted all objects and folders from $bucket.\n";
                    return [ 'status' => true, 'msg' => $msg];
                }
            }
            $msg = "Successfully Deleted this file from $bucket";
            return [ 'status' => true, 'msg' => $msg];
        } catch (S3Exception $exception) {
            $msg = "Failed to delete $file_name from $bucket with error: " . $exception->getMessage();
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
            return [ 'status' => false, 'msg' => $msg];
        }
        return [ 'status' => false, 'msg' => ''];
    }

    public function get_listObjects( $bucket ){
        $bucket = empty( $bucket ) ? false : strtolower(sanitize_text_field($bucket));
		if ( false === $bucket ) {
			$msg = __( 'No bucket name provided.', 'next3-offload' );
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
			return [ 'status' => false, 'msg' => $msg];
		}
        $bucket = self::sanitize_bucket($bucket);

        $list = [];
        try {
            $contents = $this->s3Client->listObjects([
                'Bucket' => $bucket,
            ]);
            foreach ($contents['Contents'] as $content) {
                $list[] = $content;
            }
        } catch (S3Exception $exception) {
            $msg = "Failed to list objects in $bucket_name with error: " . $exception->getMessage();
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
            return [ 'status' => false, 'msg' => $msg];
        }
        return [ 'status' => true, 'data' => $list];
    }

    public function get_manage_files($bucket, $folder = [], $return = true){
        $files = $this->get_files($bucket, $return);
        if( !empty($folder) ){
            foreach($folder as $f){
                $files = isset($files[$f]) ? $files[$f] : [['name' => '', 'icon' => 'dashicons', 'url' => '', 'type' => 'file', 'size' => -1]];
            }
        }
        return $files;
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
				$patharray = array_reverse(explode('/', $f['Key']));
				$thisarray = $this->create_folder($patharray, $f['Size'], $f['Key'], $bucket);
                $filearray = array_merge_recursive($filearray, $thisarray);
			}
        }
        if(is_array($filearray) && !empty($filearray)){
            file_put_contents($file, json_encode($filearray));
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
    
    public function create_folder($patharray, $filesize, $path, $bucket = ''){
        if(count($patharray) === 1) {
             $filename = array_pop($patharray);
             $url = $this->getFileUrl($bucket, $path);
             $tree[] = ['name' => $filename, 'size'=> $filesize, 'size_byte'=> next3_core()->action_ins->formatSizeUnits($filesize), 'path' => $path, 'url' => $url];
         } else {
             $pathpart = array_pop($patharray);
             $tree['_nx_'.$pathpart] = $this->create_folder($patharray, $filesize, $path, $bucket);
         }
         return $tree;
     }


     public function getObject( $bucket, $file_name){
        $bucket = empty( $bucket ) ? false : strtolower(sanitize_text_field($bucket));
		if ( false === $bucket ) {
			$msg = __( 'No bucket name provided.', 'next3-offload' );
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
			return [ 'status' => false, 'msg' => $msg];
		}
        $bucket = self::sanitize_bucket($bucket);
        try {
            $file = $this->s3Client->getObject([
                'Bucket' => $bucket,
                'Key' => $file_name,
            ]);
            $body = $file->get('Body');
            return [ 'status' => true, 'data' => $body];
        } catch (S3Exception $e) {
            $msg = "Failed to download $file_name from $bucket with error: " . $e->getMessage();
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
			return [ 'status' => false, 'msg' => $msg];
        }
    }

    public function getFileUrl( $bucket, $path ){
        $bucket = empty( $bucket ) ? false : strtolower(sanitize_text_field($bucket));
        $bucket = self::sanitize_bucket($bucket);

        if( defined('NEXT3_PUBLIC_ENDPOINT') && !empty(NEXT3_PUBLIC_ENDPOINT) ){
            if(strpos(NEXT3_PUBLIC_ENDPOINT, '.contabostorage.com') === true){
                $url_main =  NEXT3_PUBLIC_ENDPOINT . ':' . $bucket;
            } else {
                $url_main = NEXT3_PUBLIC_ENDPOINT . '/' . $bucket;
            }
            $url_main .= '/' . $path;
            return $url_main;
        }
        
		return $this->s3Client->getObjectUrl($bucket, $path);
    }

    public function getFilePath( $bucket, $path ){
        $bucket = empty( $bucket ) ? false : strtolower(sanitize_text_field($bucket));
        $bucket = self::sanitize_bucket($bucket);

        if( defined('NEXT3_PUBLIC_ENDPOINT') && !empty(NEXT3_PUBLIC_ENDPOINT) ){
            if(strpos(NEXT3_PUBLIC_ENDPOINT, '.contabostorage.com') === true){
                $url_main =  NEXT3_PUBLIC_ENDPOINT . ':' . $bucket;
            } else {
                $url_main = NEXT3_PUBLIC_ENDPOINT . '/' . $bucket;
            }
            $url_main .= '/' . $path;
            return $url_main;
        }

        return $this->s3Client->getObjectUrl($bucket, $path);
    }

    public function putObject( $bucket, $full_path, $file_name = '', $type = ''){
        $bucket = empty( $bucket ) ? false : strtolower(sanitize_text_field($bucket));
		if ( false === $bucket ) {
			$msg = __( 'No bucket name provided.', 'next3-offload' );
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
			return [ 'status' => false, 'msg' => $msg];
		}
        $bucket = self::sanitize_bucket($bucket);
        if( empty( $full_path ) ){
            return [ 'status' => false, 'msg' => 'invalid file path'];
        }
        if( empty($file_name) ){
            $exp = explode('/', $full_path);
            $file_name = end($exp);
        }
        //enable read/write permission
        chmod($full_path, 0777);

        // check read/write permission
        if( !is_writable( $full_path )){
            return [ 'status' => false, 'msg' => esc_html__('Error! file not executable (enable read/write permission).', 'next3-offload')];
        }

        try {
            
            $settings_options = next3_options();
            $delivery_provider = ($settings_options['delivery']['provider']) ?? 'aws';

            $fileData = file_get_contents($full_path);
            
            $obj = $this->s3Client;
            if( $delivery_provider == 'cloudflare'){
                $obj = $this->configration('auto')->s3Client;
            }
            
            $config_arr = [
                'Bucket' => $bucket,
                'Key' => $file_name,
                'Body' => $fileData,
                'ACL' => 'public-read'
            ];

            // new code v4.0.8
            $mimeType = next3_core()->action_ins->get_file_type( $full_path );
            $config_arr['ContentType'] = $mimeType . '; charset=utf-8';

            if (
				next3_core()->action_ins->should_gzip_file( $full_path ) &&
				false !== ( $gzip_body = gzencode( $fileData ) )
			) {
				//unset( $args['SourceFile'] );

				$config_arr['Body']            = $gzip_body;
				$config_arr['ContentEncoding'] = 'gzip';
			}

            // new code end


            $res = $obj->putObject($config_arr);

            return [ 'status' => true, 'data' => $res->get('ObjectURL') ];
        } catch (S3Exception $e) {
            $msg = "Failed to upload $file_name with error: " . $e->getMessage();
            return [ 'status' => false, 'msg' => $msg];
        }
        return [ 'status' => false, 'msg' => ''];
    }

    public function can_write( $bucket, $key, $file_contents ) {
        $bucket = empty( $bucket ) ? false : strtolower(sanitize_text_field($bucket));
		if ( false === $bucket ) {
			$msg = __( 'No bucket name provided.', 'next3-offload' );
            next3_core()->provider_ins->saveLogs( json_encode( $msg ) );
			return false;
		}
        $bucket = self::sanitize_bucket($bucket);
        
		try {

            $settings_options = next3_options();
            $delivery_provider = ($settings_options['delivery']['provider']) ?? 'aws';
            
            $obj = $this->s3Client;
            if( $delivery_provider == 'cloudflare'){
                $obj = $this->configration('auto')->s3Client;
            }

            $file_dir = __DIR__ . '/'. $key;
            // read/write permission
            chmod($file_dir, 0777);

            file_put_contents( $file_dir, $file_contents);
            

            $fileData = file_get_contents($file_dir);

			// Attempt to create the test file.
			$obj->putObject( array(
				'Bucket' => $bucket,
				'Key'    => $key,
				'Body'   => $fileData,
                'ACL' => 'public-read',
                'ContentType' => 'text/plain'
			) );

			// delete it straight away if created
			$obj->deleteObjects( array(
				'Bucket' => $bucket,
				'Key'    => $key,
                'Delete' => [
                    'Objects' => [
                        [
                            'Key' => $key
                        ]
                    ]
                ]
			) );

			return true;
		} catch ( S3Exception $e ) {
			next3_core()->provider_ins->saveLogs( json_encode( $e->getMessage() ) );
            return $e->getMessage();
		}
		return false;
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
				$region = 'eu-west-2';
				break;
		}
		return $region;
	}

    public function public_access_blocked( $bucket ){
        $bucket = self::sanitize_bucket($bucket);
        unset( $this->blocked_buckets[ $bucket ] );
        
        if ( isset( $this->blocked_buckets[ $bucket ] ) ) {
			return $this->blocked_buckets[ $bucket ];
		}
        $blocked = true;
        try {
			$result = $this->s3Client->getPublicAccessBlock( [ 'Bucket' => $bucket ] );
            if ( ! empty( $result ) && ! empty( $result['PublicAccessBlockConfiguration'] ) ) {
                $settings = $result['PublicAccessBlockConfiguration'];
                if (
                    empty( $settings['BlockPublicAcls'] ) &&
                    empty( $settings['BlockPublicPolicy'] ) &&
                    empty( $settings['IgnorePublicAcls'] ) &&
                    empty( $settings['RestrictPublicBuckets'] ) ) {
                    $blocked = false;
                } else {
                    $blocked = true;
                }
            }
            $this->blocked_buckets[ $bucket ] = $blocked;

		} catch ( S3Exception $e ) {
			if ( false !== strpos( $e->getMessage(), 'NoSuchPublicAccessBlockConfiguration' ) ) {
				$blocked = false;
			}
		}
		return $blocked;
    }

    public function block_public_access( $bucket, $block = false ) {
		if ( empty( $bucket ) || ! is_bool( $block ) ) {
			return false;
		}
        $bucket = self::sanitize_bucket($bucket);
		$setting = array(
			'Bucket'                         => $bucket,
			'PublicAccessBlockConfiguration' => array(
				'BlockPublicAcls'       => $block,
				'BlockPublicPolicy'     => $block,
				'IgnorePublicAcls'      => $block,
				'RestrictPublicBuckets' => $block,
			),
		);
        try {
            $this->s3Client->putPublicAccessBlock( $setting );
            unset( $this->blocked_buckets[ $bucket ] );
            return true;
        } catch ( S3Exception $e ) {
            return $e->getMessage();
        }
	}

    public function file_read(){
        return ['public-read' => 'Public Read', 'private' => 'Private Read', 'public-read-write' => 'Public Read Write', 'authenticated-read' => 'Authenticated Read'];
    }

    public function get_access(){
        $type = ($this->settingData['type']) ?? '';
        $config_data = [];

        $config_data['default_bucket'] = ($this->settingData['default_bucket']) ?? '';
        $config_data['default_region'] = ($this->settingData['default_region']) ?? 'eu-west-2';
        
        if($type == 'wp'){
            $config = defined('NEXT3_SETTINGS') ? unserialize(NEXT3_SETTINGS) : [];
            if( !empty($config) ){
                $config_data['access_key'] = ($config['access-key-id']) ?? '';
                $config_data['secret_key'] = ($config['secret-access-key']) ?? '';
                $config_data['endpoint'] = ($config['endpoint']) ?? '';
                if( isset($config['region']) ){
                    $config_data['default_region'] = !empty($config['region']) ? $config['region'] : $config_data['default_region'];
                }
            }
        }else{
            $config = ($this->settingData['credentails']) ?? [];
            if( !empty($config) ){
                $config_data['access_key'] = ($config['access_key']) ?? '';
                $config_data['secret_key'] = ($config['secret_key']) ?? '';
            }
        }
        return $config_data;
    }

    public function get_regions( $name = ''){
        $list = apply_filters('next3/regions/aws', [
            'us-east-1'      => 'US East (N. Virginia)',
            'us-east-2'      => 'US East (Ohio)',
            'us-west-1'      => 'US West (N. California)',
            'us-west-2'      => 'US West (Oregon)',
            'ca-central-1'   => 'Canada (Central)',
            'af-south-1'     => 'Africa (Cape Town)',
            'ap-east-1'      => 'Asia Pacific (Hong Kong)',
            'ap-south-1'     => 'Asia Pacific (Mumbai)',
            'ap-south-2'     => 'Asia Pacific (Hyderabad)',
            'ap-northeast-2' => 'Asia Pacific (Seoul)',
            'ap-northeast-3' => 'Asia Pacific (Osaka)',
            'ap-southeast-1' => 'Asia Pacific (Singapore)',
            'ap-southeast-2' => 'Asia Pacific (Sydney)',
            'ap-southeast-3' => 'Asia Pacific (Jakarta)',
            'ap-northeast-1' => 'Asia Pacific (Tokyo)',
            'cn-north-1'     => 'China (Beijing)',
            'cn-northwest-1' => 'China (Ningxia)',
            'eu-central-1'   => 'EU (Frankfurt)',
            'eu-central-2'   => 'EU (Zurich)',
            'eu-west-1'      => 'EU (Ireland)',
            'eu-west-2'      => 'EU (London)',
            'eu-south-1'     => 'EU (Milan)',
            'eu-south-2'     => 'EU (Spain)',
            'eu-west-3'      => 'EU (Paris)',
            'eu-north-1'     => 'EU (Stockholm)',
            'me-south-1'     => 'Middle East (Bahrain)',
            'me-central-1'   => 'Middle East (UAE)',
            'sa-east-1'      => 'South America (São Paulo)',
       ]);
       if( !empty($name) ){
            return ($list[$name]) ?? '';
       }
        return $list;
    }

    public static function instance(){
        if (!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
    }
}
