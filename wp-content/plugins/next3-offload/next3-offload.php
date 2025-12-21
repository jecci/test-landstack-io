<?php
defined( 'ABSPATH' ) || exit;
/**
 * Plugin Name: Next3 Offload
 * Description: Next3 Offload is a swift and intuitive WordPress offload media optimization plugin. The tool optimizes WordPress speed by offloading WordPress files from WordPress media library to Cloud including Amazon S3, DigitalOcean Spaces, Bunny CDN, Wasabi Cloud, and 20+ S3-Compatible Object Storage & CDNs.
 * Plugin URI: https://themedev.net/next3-offload/
 * Author: ThemeDev
 * Version: 4.1.7
 * Author URI: https://themedev.net/
 *
 * Text Domain: next3-offload
 *
 * @package Next3Offload
 * @category Pro
 *
 * Next3 Offload is a swift and intuitive WordPress offload media optimization plugin. The tool optimizes WordPress speed by offloading WordPress files from WordPress media library to Cloud including Amazon S3, DigitalOcean Spaces, Bunny CDN, Wasabi Cloud, and 20+ S3-Compatible Object Storage & CDNs.
 *
 * License:  GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

 if( !defined('NEXT3_FILE_') ){

	define( 'NEXT3_FILE_', __FILE__ );
	define( 'NEXT3_VERSION', '4.1.7' );
	define( 'NEXT3_SELF_MODE', false );

	// enable multi site mode
	if( !defined('NEXT3_MULTI_SITE')){
		define( 'NEXT3_MULTI_SITE', true);
	}

	if( !defined('NEXT3_NOT_FILE')){
		define( 'NEXT3_NOT_FILE', true);
	}

	if( !defined('NEXT3_PUBLIC_ENDPOINT')){
		//define( 'NEXT3_PUBLIC_ENDPOINT', 'https://eu2.contabostorage.com/00aafd3f6a554a10aefa275b123c4756'); // example endpoint
	}
	
	include( __DIR__ . '/functions.php' ); 
	include( __DIR__ . '/loader.php' ); 
	include( __DIR__ . '/plugin.php' );

	// load plugin
	add_action( 'plugins_loaded', function(){
		// load text domain
		load_plugin_textdomain( 'next3-offload', false, basename( dirname( __FILE__ ) ) . '/languages'  );
		if( !did_action('next3Aws/loaded') ){
			// load plugin instance
			\Next3Offload\N3aws_Plugin::instance()->init();
		}
	});

 }






	

