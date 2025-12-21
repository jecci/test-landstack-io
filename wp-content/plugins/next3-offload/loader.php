<?php
namespace Next3Offload;
defined( 'ABSPATH' ) || exit;

class N3aws_Loader{
    
	public static function _run() {
		spl_autoload_register( [__CLASS__, '_autoload' ] );
    }
  
	private static function _autoload( $load ) {
        if ( 0 !== strpos( $load, __NAMESPACE__ ) ) {
            return;
        }
        // get map setup data
        $map = self::class_map();
        $mapclass = preg_replace( '/^' . __NAMESPACE__ . '\\\/', '', $load );
        if( isset( $map[$mapclass] ) ){
            $name = $map[$mapclass];
        } else {
            $name = strtolower(preg_replace([ '/\b'.__NAMESPACE__.'\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ], [ '', '$1-$2', '-', DIRECTORY_SEPARATOR], $load) );
            $name = str_replace('n3aws-', '', $name). '.php';    
        }
        $filename = N3aws_Plugin::plugin_dir() . $name;
        if ( is_readable( $filename ) ) {
           require_once( $filename );
        }
    }

    // class map
    private static function class_map(){
        return [
            'Utilities\N3aws_Admin' => 'utilities/admin.php',
            'Modules\Load' => 'modules/load.php',
        ];
    }
}
