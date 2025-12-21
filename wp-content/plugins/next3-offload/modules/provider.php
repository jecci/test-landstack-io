<?php 
namespace Next3Offload\Modules;
defined( 'ABSPATH' ) || exit;

class Provider{
    private static $instance;

    public $settingData = [];
    public $provider = 'default';

    public function load( $key = ''){
        $credentials = next3_credentials();
        $provider = ($credentials['settings']['provider']) ?? 'default';
        $this->provider = !empty($key) ? $key : $provider;

        $this->settingData = ($credentials['settings']) ?? [];
        return $this;
    }

    public function access( $status = true){
        if( in_array($this->provider, ['default']) ){
            return false;
        }
        $ins = $this->provider_ins();
        if( !empty($ins) && method_exists( $ins, 'request')){
            return $ins::instance()->request( $status );
        }
        return false;
    }

    public function provider_ins(){
        $provider = apply_filters('next3/providers/instance', [
            'aws' => '\Next3Offload\Modules\Providers\Aws\N3aws_Action',
            'digital' => '\Next3Offload\Modules\Providers\Digital\N3aws_Action',
            'bunny' => '\Next3Offload\Modules\Providers\Bunny\N3aws_Action',
            'wasabi' => '\Next3Offload\Modules\Providers\Wasabi\N3aws_Action',
            'google' => '\Next3Offload\Modules\Providers\Google\N3aws_Action',
            'objects' => '\Next3Offload\Modules\Providers\Objects\N3aws_Action',
        ]);
        return ( $provider[ $this->provider ] ) ?? '';
    }

    public function saveLogs( $msg = []){
        $saveLog['datetime'] = date('Y-m-d\TH:i:s.u');
        $saveLog['msg'] = $msg;

        $files = __DIR__ . '/error.log';

        chmod($files, 0777);

        file_put_contents($files, print_r($saveLog, true), FILE_APPEND);
    }

    public function getLogs(){
        $files = __DIR__ . '/error.log';
        
        chmod($files, 0777);

        if( is_readable($files) ){
            return file_get_contents( $files );
        }
        return;
    }

    public static function instance(){
		if (!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
	}
}