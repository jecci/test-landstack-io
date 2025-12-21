<?php 
namespace Next3Offload\Modules;
defined( 'ABSPATH' ) || exit;

class Assets{
    private static $instance;

    public $assets_dir = null;

    private $exclude_params = array(
		'pdf-catalog',
		'tve',
		'elementor-preview',
		'preview',
		'wc-api',
		'method',
	);

    private $css_ignore_list = array(
		'uag-style',
	);

    private $js_ignore_list = array(
		'jquery',
		'jquery-core',
		'ai1ec_requirejs',
		'moxiejs',
		'wc-square',
		'wc-braintree',
		'wc-authorize-net-cim',
		'sv-wc-payment-gateway-payment-form',
		'paypal-checkout-sdk',
		'uncode-app',
		'uncode-plugins',
		'uncode-init',
		'lodash',
		'wp-api-fetch',
		'wp-i18n',
		'wp-polyfill',
		'wp-url',
		'wp-hooks',
		'houzez-google-map-api',
		'wpascript',
		'wc-square',
	);

    public function init() {

        //$this->set_assets_directory_path();

        $settings_options = next3_options();
        $css_offload = ($settings_options['assets']['css_offload']) ?? 'no';
        $js_offload = ($settings_options['assets']['js_offload']) ?? 'no';
        $version_query = ($settings_options['assets']['version_query']) ?? 'no';

        if($css_offload == 'yes'){
            add_action( 'wp_print_styles', array( $this, 'minify_styles' ), 11 );
			add_action( 'wp_print_footer_scripts', array( $this, 'minify_styles' ), 11 );
        }
        if($js_offload == 'yes'){
            add_action( 'wp_print_scripts', array( $this, 'minify_scripts' ), 20 );
			add_action( 'wp_print_footer_scripts', array( $this, 'minify_scripts' ) );
        }

        if($version_query == 'yes'){
            add_filter( 'style_loader_src', array( $this->instance(), 'remove_query_strings' ) );
			add_filter( 'script_loader_src', array( $this->instance(), 'remove_query_strings' ) );
        }

    }

    public function minify_styles() {
		global $wp_styles;

		// Bail if the scripts object is empty.
		if (
			! is_object( $wp_styles ) ||
			$this->has_exclude_param()
		) {
			return;
		}

		$styles = wp_clone( $wp_styles );
		$styles->all_deps( $styles->queue );

		$settings_options = next3_options();
		$exclude_css = ($settings_options['assets']['exclude_css']) ?? [];

		$this->css_ignore_list = array_merge(
			$this->css_ignore_list,
			$exclude_css
		);

		$excluded_styles = apply_filters( 'next3_css_offload_exclude', $this->css_ignore_list );

        $offload_css = next3_get_option('next3_offload_styles', []); 

		// Get groups of handles.
		foreach ( $styles->to_do as $handle ) {
			// Skip styles.
			if (
				false === $wp_styles->registered[ $handle ]->src || // If the source is empty.
				in_array( $handle, $excluded_styles ) || // If the file is ignored.
				@strpos( self::get_home_url(), parse_url( $wp_styles->registered[ $handle ]->src, PHP_URL_HOST ) ) === false // Skip all external sources.
			) {
				continue;
			}

            $parsed_url = parse_url( $wp_styles->registered[ $handle ]->src );

            if( array_key_exists($handle, $offload_css)){
                $data_offload = $offload_css[ $handle ];
                $file_url = next3_core()->action_ins->get_url_assets( $data_offload );
                if( !empty($file_url) ){
                    if ( ! empty( $parsed_url['query'] ) ) {
                        $file_url = $file_url . '?' . $parsed_url['query'];
                    }
                    $wp_styles->registered[ $handle ]->src = $file_url;
                }
            }
		}
	}

    public function minify_scripts() {
		global $wp_scripts;

		// Bail if the scripts object is empty.
		if (
			! is_object( $wp_scripts ) ||
			$this->has_exclude_param()
		) {
			return;
		}

		$scripts = wp_clone( $wp_scripts );
		$scripts->all_deps( $scripts->queue );

		$settings_options = next3_options();
		$exclude_js = ($settings_options['assets']['exclude_js']) ?? [];
		
		$this->js_ignore_list = array_merge(
			$this->js_ignore_list,
			$exclude_js
		);

		$excluded_scripts = apply_filters( 'next3_js_offload_exclude', $this->js_ignore_list );

        $offload_js = next3_get_option('next3_offload_scripts', []); 

		// Get groups of handles.
		foreach ( $scripts->to_do as $handle ) {
			if (
				false === $wp_scripts->registered[ $handle ]->src || // If the source is empty.
				in_array( $handle, $excluded_scripts ) || // If the file is ignored.
				@strpos( self::get_home_url(), parse_url( $wp_scripts->registered[ $handle ]->src, PHP_URL_HOST ) ) === false // Skip all external sources.
			) {
				continue;
			}

            $parsed_url = parse_url( $wp_scripts->registered[ $handle ]->src );

            if( array_key_exists($handle, $offload_js)){
                $data_offload = $offload_js[ $handle ];
                $file_url = next3_core()->action_ins->get_url_assets( $data_offload );
                if( !empty($file_url) ){
                    if ( ! empty( $parsed_url['query'] ) ) {
                        $file_url = $file_url . '?' . $parsed_url['query'];
                    }
                    $wp_scripts->registered[ $handle ]->src = $file_url;
                }
            }
		}
	}

    public function has_exclude_param( $params = array() ) {
		// If there are no params we don't need to check the query params.
		if ( ! isset( $_REQUEST ) ) {
			return false;
		}

		if ( empty( $params ) ) {
			$params = $this->exclude_params;
		}

		// Check if any of the excluded params exists in the request.
		foreach ( $params as $param ) {
			if ( array_key_exists( $param, $_REQUEST ) ) {
				return true;
			}
		}

		return false;
	}

    public static function get_uploads_dir() {
		// Get the uploads dir.
		if ( is_multisite() ) {
			$site = get_current_blog_id();

			$uploads = wp_get_upload_dir();
            $uploads['basedir'] = WP_CONTENT_DIR . '/uploads/';
            $uploads['baseurl'] = WP_CONTENT_URL . '/uploads/';
            $uploads['path'] = $uploads['basedir'] . $site;
            $uploads['url'] = $uploads['baseurl'] . $site;
		}else{
			$uploads = wp_upload_dir();
		}
		
		$base_dir = $uploads['basedir'];

		if ( defined( 'UPLOADS' ) ) {
			$base_dir = ABSPATH . UPLOADS;
		}

		return $base_dir;
	}

    public function create_directory( $directory ) {
		// Create the directory and return the result.
		$is_directory_created = wp_mkdir_p( $directory );

		// Bail if cannot create temp dir.
		if ( false === $is_directory_created ) {
			//error_log( sprintf( 'Cannot create directory: %s.', $directory ) );
		}

		return $is_directory_created;
	}

    private function set_assets_directory_path() {
		// Bail if the assets dir has been set.
		if ( null !== $this->assets_dir ) {
			return;
		}

		$uploads_dir = self::get_uploads_dir();

		// Build the assets dir name.
		$directory = $uploads_dir . '/next3-optimizer-assets';

		// Check if directory exists and try to create it if not.
		$is_directory_created = ! is_dir( $directory ) ? $this->create_directory( $directory ) : true;

		// Set the assets dir.
		if ( $is_directory_created ) {
			$this->assets_dir = trailingslashit( $directory );
		}

	}

    public function assets_list(){
        // Get the global varialbes.
        global $wp;
        global $wp_styles;
        global $wp_scripts;

        // Pre-load Woocommerce functionality, if needed.
        if ( function_exists( '\WC' ) && defined( '\WC_ABSPATH' ) ) {
            include_once \WC_ABSPATH . 'includes/wc-cart-functions.php';
            include_once \WC_ABSPATH . 'includes/class-wc-cart.php';

            if ( is_null( WC()->cart ) ) {
                wc_load_cart();
            }
        }

        // Remove the jet popup action to prevent fatal errros.
        remove_all_actions( 'elementor/editor/after_enqueue_styles', 10 );

        //$wp_scripts->queue[] = 'wc-jilt';

        ob_start();
        // Call the action to load the assets.
        do_action( 'wp', $wp );
        do_action( 'wp_enqueue_scripts' );
        do_action( 'elementor/editor/after_enqueue_styles' );
        ob_get_clean();

        unset( $wp_scripts->queue['wc-jilt'] );


        // Build the assets data.
        return array(
            'scripts' => $this->get_assets_data( $wp_scripts ),
            'styles'  => $this->get_assets_data( $wp_styles ),
        );
    }

    private function get_assets_data( $assets ) {
		$excludes = apply_filters( 'next3_excludes_assets_offload_handler', array(
			'moxiejs',
			'elementor-frontend',
			'nextJs',
			'hJsSelect2',
		));

		// Init the data array.
		$data = array(
			'header'       => array(),
			'default'      => array(),
			'non_minified' => array(),
		);

		// CLone the global assets object.
		$items = wp_clone( $assets );
		$items->all_deps( $items->queue );


		// Loop through all assets and push them to data array.
		foreach ( $items->to_do as $index => $handle ) {
			if (
				in_array( $handle, $excludes ) || // Do not include excluded assets.
				! is_bool( @strpos( $handle, 'next3' ) ) ||
				! is_string( $items->registered[ $handle ]->src ) || // Do not include asset without source.
                @strpos( self::get_home_url(), parse_url( $items->registered[ $handle ]->src, PHP_URL_HOST ) ) === false // Skip all external sources.
			) {
				continue;
			}

			if ( 1 !== $items->groups[ $handle ] ) {
				$data['header'][] = $this->get_asset_data( $items->registered[ $handle ] );
			}

			if ( @strpos( $items->registered[ $handle ]->src, '.min.' ) === false ) {
				$data['non_minified'][] = $this->get_asset_data( $items->registered[ $handle ] );
			}

			$data['default'][] = $this->get_asset_data( $items->registered[ $handle ] );
		}

		// Finally return the assets data.
		return $data;
	}

    public function get_asset_data( $item ) {
		// Strip the protocol from the src because some assets are loaded without protocol.
		$src = preg_replace( '~https?://~', '', self::remove_query_strings( $item->src ) );

		// Do regex match to the the plugin name and shorten src link.
		preg_match( '~wp-content(/(.*?)/(.*?)/.*)~', $src, $matches );

		// Push everything in the data array.
		$data = array(
			'value'       => $item->handle, // The handle.
			'title'       => ! empty( $matches[1] ) ? $matches[1] : $item->src, // The assets src.
			'group'       => ! empty( $matches[2] ) ? substr( $matches[2], 0, -1 ) : __( 'others', 'next3-offload' ), // Get the group name.
			'name'        => ! empty( $matches[3] ) ? $this->get_plugin_info( $matches[3] ) : false, // The name of the parent( plugin or theme name ).
		);

		$data['group_title'] = empty( $data['name'] ) ? $data['group'] : $data['group'] . ': ' . $data['name'];

		return $data;
	}

    private function get_plugin_info( $path, $field = 'name' ) {
		// Get active plugins.
		$active_plugins = next3_get_option( 'active_plugins' );

		// Check if the path is presented in the active plugins.
		foreach ( $active_plugins as $plugin_file ) {
			if ( false === @strpos( $plugin_file, $path ) ) {
				continue;
			}

			// Get the plugin data from the main plugin file.
			$plugin = get_file_data( WP_PLUGIN_DIR . '/' . $plugin_file, array( $field => 'Plugin Name' ) );
		}

		// Return the date from plugin file.
		if ( ! empty( $plugin[ $field ] ) ) {
			return $plugin[ $field ];
		}

		// Otherwise return the path.
		return $path;
	}

    public static function remove_query_strings( $src ) {
		// Get the host.
		$host = parse_url( $src, PHP_URL_HOST );

		// Bail if the host is empty.
		if ( empty( $host ) ) {
			return $src;
		}

		// Skip all external sources.
		if ( @strpos( self::get_home_url(), $host ) === false ) {
			return $src;
		}

		$exclude_list = apply_filters( 'next3_rqs_exclude', array() );

		if (
			! empty( $exclude_list ) &&
			preg_match( '~' . implode( '|', $exclude_list ) . '~', $src )
		) {
			return $src;
		}

		return remove_query_arg(
			array(
				'ver',
				'version',
				'v',
				'mts',
				'nomtcache',
				'generated',
				'timestamp',
				'cache',
			),
			html_entity_decode( $src )
		);
	}

    public static function get_home_url() {
		$url = next3_home_url();

		$scheme = is_ssl() ? 'https' : parse_url( $url, PHP_URL_SCHEME );

		$url = set_url_scheme( $url, $scheme );

		return trailingslashit( $url );
	}

    public static function instance(){
		if (!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
	}
}