<?php 
namespace Next3Offload\Modules;
defined( 'ABSPATH' ) || exit;

class Load{
    private static $instance;

    public static function _get_url(){
        return next3_core()->plugin::modules_url();
    }
    public static function _get_dir(){
        return next3_core()->plugin::modules_dir();
    }

    public static function _version(){
        return next3_core()->plugin::version();
    }

    public function _init() {    
        
        if(current_user_can('manage_options')){
            // proactive
            Proactive\N3aws_Init::instance()->_init();
        }
        
    }

    public static function instance(){
		if (!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
	}
}