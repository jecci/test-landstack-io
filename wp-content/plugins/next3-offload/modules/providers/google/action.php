<?php
namespace Next3Offload\Modules\Providers\Google;
defined( 'ABSPATH' ) || exit;

class N3aws_Action{

    private static $instance;

    protected $console_url = 'https://console.cloud.google.com/storage/browser/';

    private $s3Client_status;
    private $s3Client;
    private $aws_client;
    public $default_region = 'us';
	protected $default_domain = 'storage.googleapis.com';
	protected $blocked_buckets = [];
    protected static $buckets_check = [];

    private $settingData = [];
    
    public function __construct(){
        //require_once( next3_core()->plugin::plugin_dir(). 'vendor/google/autoload.php' );

    }


    public function request(){

        $data = next3_core()->provider_ins->settingData;
        $provider = next3_core()->provider_ins->provider;

        $this->settingData = ($data[ $provider ]) ?? [];

        return $this->configration()->handle_request();
    }

    public function configration( $region = ''){
        $data = $this->get_access();
        $file_data = ($data['file_data']) ?? '';

        $default_bucket = ($data['default_bucket']) ?? '';

        
        /*$endpoint = ($data['endpoint']) ?? '';

        try {

            $args = [
                'version' => 'latest'
            ];
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
        }*/

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

    // new code here


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
				$region = 'nyc3';
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
        $config_data['default_region'] = ($this->settingData['default_region']) ?? '';
        if($type == 'wp'){
            $config = defined('NEXT3_SETTINGS') ? unserialize(NEXT3_SETTINGS) : [];
            if( !empty($config) ){
                $file = ($config['key-file-path']) ?? '';
                if( !empty($file) && is_executable($file)){
                    $config_data['file_data'] = file_get_contents($file);
                }
            }
        }else{
            $config = ($this->settingData['credentails']) ?? [];
            if( !empty($config) ){
                $config_data['file_data'] = ($config['json_data']) ?? '';
            }
        }
        
        return $config_data;
    }

    public function get_regions( $name = ''){
        $list = apply_filters('next3/regions/google', [
            'asia'                    => 'Multi-Region (Asia)',
            'eu'                      => 'Multi-Region (EU)',
            'us'                      => 'Multi-Region (US)',
            'us-central1'             => 'North America (Iowa)',
            'us-east1'                => 'North America (South Carolina)',
            'us-east4'                => 'North America (Northern Virginia)',
            'us-east5'                => 'North America (Columbus)',
            'us-west1'                => 'North America (Oregon)',
            'us-west2'                => 'North America (Los Angeles)',
            'us-west3'                => 'North America (Salt Lake City)',
            'us-west4'                => 'North America (Las Vegas)',
            'us-south1'               => 'North America (Dallas)',
            'northamerica-northeast1' => 'North America (Montréal)',
            'northamerica-northeast2' => 'North America (Toronto)',
            'southamerica-east1'      => 'South America (São Paulo)',
            'southamerica-west1'      => 'South America (Santiago)',
            'europe-central2'         => 'Europe (Warsaw)',
            'europe-north1'           => 'Europe (Finland)',
            'europe-west1'            => 'Europe (Belgium)',
            'europe-west2'            => 'Europe (London)',
            'europe-west3'            => 'Europe (Frankfurt)',
            'europe-west4'            => 'Europe (Netherlands)',
            'europe-west6'            => 'Europe (Zürich)',
            'europe-west8'            => 'Europe (Milan)',
            'europe-west9'            => 'Europe (Paris)',
            'europe-southwest1'       => 'Europe (Madrid)',
            'me-west1'                => 'Middle East (Tel Aviv)',
            'asia-east1'              => 'Asia (Taiwan)',
            'asia-east2'              => 'Asia (Hong Kong)',
            'asia-northeast1'         => 'Asia (Tokyo)',
            'asia-northeast2'         => 'Asia (Osaka)',
            'asia-northeast3'         => 'Asia (Seoul)',
            'asia-southeast1'         => 'Asia (Singapore)',
            'asia-south1'             => 'India (Mumbai)',
            'asia-south2'             => 'India (Dehli)',
            'asia-southeast2'         => 'Indonesia (Jakarta)',
            'australia-southeast1'    => 'Australia (Sydney)',
            'australia-southeast2'    => 'Australia (Melbourne)',
            'asia1'                   => 'Dual-Region (Tokyo/Osaka)',
            'eur4'                    => 'Dual-Region (Finland/Netherlands)',
            'nam4'                    => 'Dual-Region (Iowa/South Carolina)',
        ]);
        if( !empty($name) ){
            $name = strip_tags($name);
            return ($list[$name]) ?? $list;
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