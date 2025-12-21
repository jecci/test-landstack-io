<?php
namespace Next3Offload;
defined( 'ABSPATH' ) || exit;

final class N3aws_Plugin{

    private static $instance;

    private $template;

    public function __construct(){
        N3aws_Loader::_run(); 
    }
    
    public static function version(){
        return NEXT3_VERSION;
    }
   
    public static function php_version(){
        return '5.6';
    }

    
	public static function plugin_file(){
		return NEXT3_FILE_;
	}
    
	public static function plugin_url(){
		return trailingslashit(plugin_dir_url( self::plugin_file() ));
	}

	
	public static function plugin_dir(){
		return trailingslashit(plugin_dir_path( self::plugin_file() ));
    }

	
	public static function modules_url(){
		return self::plugin_url() . 'modules/';
	}

	
	public static function modules_dir(){
		return self::plugin_dir() . 'modules/';
	}
    
    public function init(){
        add_action( 'in_admin_header', [ $this, 'remove_admin_notices' ] );   
        if ( version_compare( PHP_VERSION, self::php_version(), '<' ) ) {
			add_action( 'admin_notices', [next3_core()->admin_ins, '_check_php_version'] );
			return;
		}
       
        add_action( 'init', [next3_core()->admin_ins, 'init']);

        // load
        Modules\Load::instance()->_init();

        $get_package = next3_license_package();

        // svg
        next3_core()->svg_ins->init();

        if( in_array($get_package, ['business', 'developer', 'extended']) ){
            
            if(current_user_can('manage_options') && is_admin()){
                
                // optimizer
                next3_core()->optimizer_ins->init();
                //webp images
                next3_core()->webp_ins->init();
            }
            // database
            next3_core()->database_ins->init();
        }
        if( in_array($get_package, ['developer', 'extended']) ){
            // assets load
            next3_core()->assets_ins->init();
        }
            
        
        // call modules action
        next3_core()->action_ins->init();
        // compatiblity
        next3_core()->compatiblility_ins->init();
        // support
        next3_core()->support_ins->init();
    }
    
    public function remove_admin_notices() {
        $screen = get_current_screen();
        if( in_array($screen->id, [ 'next3_page_next3config', 'toplevel_page_next3aws'])){	
        //if( in_array($screen->id, [ 'next3_page_next3config', 'next3-offload_page_next3aws-file', 'toplevel_page_next3aws'])){	
            remove_all_actions( 'network_admin_notices' );
            remove_all_actions( 'user_admin_notices' );
            remove_all_actions( 'admin_notices' );
            remove_all_actions( 'all_admin_notices' );
        }
    }

    public static function instance(){
        if ( is_null( self::$instance ) ){
            self::$instance = new self();
            do_action( 'next3Aws/loaded' );
        }
        return self::$instance;
    }

}