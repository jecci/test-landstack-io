<?php
/**
 * Handles view counters.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      7.11.10
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Handles AWB Views Count.
 */
class AWB_View_Count {

	/**
	 * The one, true instance of this object.
	 *
	 * @static
	 * @access private
	 * @since 7.14.1
	 * @var object
	 */
	private static $instance;   

	/**
	 * The constructor.
	 *
	 * @access public
	 * @since 7.14.1
	 * @return void
	 */
	public function __construct() {
		add_filter( 'avada_options_sections', [ $this, 'add_view_count_options_to_extras_tab' ] );
		
		if ( 'ajax' === Avada()->settings->get( 'current_post_visitors_count' ) || '1' === Avada()->settings->get( 'current_post_visitors_count_live' ) ) {
			add_action( 'wp_ajax_awb_update_current_post_visitors_count', [ $this, 'update_current_post_visitors_count_ajax' ] );
			add_action( 'wp_ajax_nopriv_update_current_post_visitors_count', [ $this, 'update_current_post_visitors_count_ajax' ] );
		}

		if ( 'page_load' === Avada()->settings->get( 'post_views' ) ) {
			add_action( 'wp_head', [ $this, 'set_post_views' ] );
			add_action( 'wp_head', [ $this, 'set_post_views_today' ] );
		} else if ( 'ajax' === Avada()->settings->get( 'post_views' ) ) {
			add_action( 'wp_ajax_avada_set_ajax_post_views', [ $this, 'set_post_views_ajax' ] );
			add_action( 'wp_ajax_nopriv_avada_set_ajax_post_views', [ $this, 'set_post_views_ajax' ] );
		}

		add_filter( 'awb_theme_scripts', [ $this, 'add_scripts' ] );
		add_filter( 'awb_localize_theme_scripts', [ $this, 'localize_scripts' ] );

	}

	/**
	 * Add the GO related to views counting to the Extras tab.
	 *
	 * @since 7.14.1
	 * @access public
	 * @param array $sections The sections.
	 * @return array The adjusted options sections.
	 */
	public function add_view_count_options_to_extras_tab( $sections ) {
		if ( isset( $sections['extras']['fields']['misc_options_section']['fields'] ) ) {
			$sections['extras']['fields']['post_views_count_section'] = $this->get_options();
		}

		return $sections;
	}

	/**
	 * Define the Global Options.
	 *
	 * @since 7.14.1
	 * @access public
	 * @return array The needed global options.
	 */
	public function get_options() {
		$options = [
			'label'       => esc_html__( 'Post Views / Visits Counting', 'Avada' ),
			'description' => '',
			'id'          => 'post_views_count_section',
			'type'        => 'sub-section',
			'fields'      => [			
				'post_views'                 => [
					'label'       => esc_html__( 'Post Views Counter', 'Avada' ),
					'description' => esc_html__( 'Set the method to update the post views, or disable them entirely. "Page Load" option will update using PHP when a page is retrieved. "Ajax" will send an additional request after the page loads.', 'Avada' ),
					'id'          => 'post_views',
					'default'     => 'page_load',
					'type'        => 'radio-buttonset',
					// This option doesn't require updating the preview.
					'transport'   => 'postMessage',					
					'choices'     => [
						'page_load' => esc_html__( 'Page Load', 'Avada' ),
						'ajax'      => esc_html__( 'Ajax', 'Avada' ),
						'disabled'  => esc_html__( 'Disabled', 'Avada' ),
					],
				],
				'post_views_counting'        => [
					'label'       => esc_html__( 'Post Views User Type Counting', 'Avada' ),
					'description' => esc_html__( 'Select which types of users will increase post views on visit.', 'Avada' ),
					'id'          => 'post_views_counting',
					'default'     => 'all',
					'type'        => 'select',
					// This option doesn't require updating the preview.
					'transport'   => 'postMessage',					
					'choices'     => [
						'all'        => esc_html__( 'All', 'Avada' ),
						'logged_out' => esc_html__( 'Logged Out', 'Avada' ),
						'non_admins' => esc_html__( 'Non-Admins', 'Avada' ),
					],
				],
				'current_post_visitors_count' => [
					'label'       => esc_html__( 'Current Post Visitor Counter', 'Avada' ),
					'description' => esc_html__( 'Set how the current post visiotr counter should get updated, or disable the counter. "Page Load" option will update using PHP when a page is retrieved. "Ajax" will send an additional request after the page loads.', 'Avada' ),
					'id'          => 'current_post_visitors_count',
					'default'     => 'disabled',
					'type'        => 'radio-buttonset',
					// This option doesn't require updating the preview.
					'transport'   => 'postMessage',					
					'choices'     => [
						'page_load' => esc_html__( 'Page Load', 'Avada' ),
						'ajax'      => esc_html__( 'Ajax', 'Avada' ),
						'disabled'  => esc_html__( 'Disabled', 'Avada' ),
					],
				],
				'current_post_visitors_count_mode' => [
					'label'       => esc_html__( 'Current Post Visitor Counter Mode', 'Avada' ),
					'description' => esc_html__( 'Set the post visitors counter mode. "Real" will count the real visitors by IP, "Fuzzy" will use random numbers.', 'Avada' ),
					'id'          => 'current_post_visitors_count_mode',
					'default'     => 'real',
					'type'        => 'radio-buttonset',
					'choices'     => [
						'real'  => esc_html__( 'Real', 'Avada' ),
						'fuzzy' => esc_html__( 'Fuzzy', 'Avada' ),
					],
					// This option doesn't require updating the preview.
					'transport'   => 'postMessage',					
					'required'    => [
						[
							'setting'  => 'current_post_visitors_count',
							'operator' => '!=',
							'value'    => 'disabled',
						],
					],
				],
				'current_post_visitors_count_real_hash' => [
					'label'       => esc_html__( 'Anonymize IPs', 'Avada' ),
					'description' => esc_html__( 'When enabled, visitor IP addresses are converted into anonymous hashed IDs.', 'Avada' ),
					'id'          => 'current_post_visitors_count_real_hash',
					'default'     => '0',
					'type'        => 'switch',
					// This option doesn't require updating the preview.
					'transport'   => 'postMessage',					
					'required'    => [
						[
							'setting'  => 'current_post_visitors_count',
							'operator' => '!=',
							'value'    => 'disabled',
						],
						[
							'setting'  => 'current_post_visitors_count_mode',
							'operator' => '==',
							'value'    => 'real',
						],						
					],
				],				
				'current_post_visitors_count_fuzzy_values' => [
					'label'       => esc_html__( 'Current Post Visitor Counter Fuzzy Values', 'Avada' ),
					'description' => esc_html__( 'Set the maximum and the minimum values for the fuzzy post visitor counter.', 'Avada' ),
					'id'          => 'current_post_visitors_count_fuzzy_values',
					'choices'     => [
						'top'    => true,
						'bottom' => true,
					],
					'default'     => [
						'top'    => '25',
						'bottom' => '10',
					],
					'type'        => 'spacing',
					// This option doesn't require updating the preview.
					'transport'   => 'postMessage',					
					'required'    => [
						[
							'setting'  => 'current_post_visitors_count',
							'operator' => '!=',
							'value'    => 'disabled',
						],
						[
							'setting'  => 'current_post_visitors_count_mode',
							'operator' => '==',
							'value'    => 'fuzzy',
						],
					],
				],	
				'current_post_visitors_count_live' => [
					'label'       => esc_html__( 'Current Post Visitor Live Counter', 'Avada' ),
					'description' => esc_html__( 'Turn on to update the counter while users are on the post in set intervals.', 'Avada' ),
					'id'          => 'current_post_visitors_count_live',
					'default'         => '0',
					'type'            => 'switch',
					// This option doesn't require updating the preview.
					'transport'   => 'postMessage',					
					'required'    => [
						[
							'setting'  => 'current_post_visitors_count',
							'operator' => '!=',
							'value'    => 'disabled',
						],
					],
				],
				'current_post_visitors_count_live_interval' => [
					'label'           => esc_html__( 'Current Post Visitor Live Counter Update Interval', 'Avada' ),
					'description'     => esc_html__( 'Set the interval length between two consecutive AJAX updates of the counter. In seconds.', 'Avada' ),
					'id'              => 'current_post_visitors_count_live_interval',
					'default'         => '60',
					'type'            => 'slider',
					'choices'         => [
						'min'  => '15',
						'max'  => '300',
						'step' => '1',
					],
					// This option doesn't require updating the preview.
					'transport'   => 'postMessage',
					'required'    => [
						[
							'setting'  => 'current_post_visitors_count',
							'operator' => '!=',
							'value'    => 'disabled',
						],
						[
							'setting'  => 'current_post_visitors_count_live',
							'operator' => '==',
							'value'    => '1',
						],
					],
				],
			],
		];

		return $options;
	}

	/**
	 * Add needed scripts.
	 *
	 * @since 7.14.1
	 * @access public
	 * @param array $scripts The scripts.
	 * @return array The adjusted scripts array.
	 */
	public function add_scripts( $scripts ) {
		if ( ( 'ajax' === Avada()->settings->get( 'post_views' ) || 'ajax' === Avada()->settings->get( 'current_post_visitors_count' ) || '1' === Avada()->settings->get( 'current_post_visitors_count_live' ) ) && ! $this->is_live_editor() && is_singular() && ! is_preview() ) {
			$js_folder_suffix = AVADA_DEV_MODE ? '/assets/js' : '/assets/min/js';
			$js_folder_url    = Avada::$template_dir_url . $js_folder_suffix;
			$js_folder_path   = Avada::$template_dir_path . $js_folder_suffix;
			
			$scripts[] = [
				'awb-view-count',
				$js_folder_url . '/general/avada-views-counter.js',
				$js_folder_path . '/general/avada-views-counter.js',
				[ 'jquery' ],
				Avada::get_theme_version(),
				true,
			];
		}

		return $scripts;
	}

	/**
	 * Localize needed scripts.
	 *
	 * @since 7.14.1
	 * @access public
	 * @param array $scripts The scripts.
	 * @return array The adjusted scripts array.
	 */
	public function localize_scripts( $scripts ) {
		if ( ( 'ajax' === Avada()->settings->get( 'post_views' ) || 'ajax' === Avada()->settings->get( 'current_post_visitors_count' ) || '1' === Avada()->settings->get( 'current_post_visitors_count_live' ) ) && ! $this->is_live_editor() && is_singular() && ! is_preview() ) {
			$scripts[] = [
				'awb-view-count',
				'awbViewCountVars',
				[
					'ajaxUrl'                              => admin_url( 'admin-ajax.php' ),
					'currentPostVisitorsCount'             => Avada()->settings->get( 'current_post_visitors_count' ),
					'currentPostVisitorsCountLive'         => Avada()->settings->get( 'current_post_visitors_count_live' ),
					'currentPostVisitorsCountLiveInterval' => Avada()->settings->get( 'current_post_visitors_count_live_interval' ),
					'postViews'                            => Avada()->settings->get( 'post_views' )
				],
			];
		}

		return $scripts;
	}	

	/**
	 * Update current visitors.
	 *
	 * @access public
	 * @since 7.14.1
	 * @param string|int $post_id The current post ID.
	 * @return void
	 */
	public static function update_current_post_visitors_count( $post_id = 0 ) {
		$post_id = ! $post_id ? fusion_library()->get_page_id() : $post_id;

		// Only count if active.
		if ( ! self::is_current_post_visitors_count_active() ) {
			return;
		}

		$active_visitors = get_post_meta( $post_id, 'awb_current_post_visitors', true );
		$active_visitors = ! is_array( $active_visitors ) ? [] : $active_visitors;
		$threshold       = apply_filters( 'awb_current_post_visitors_time_threshold', 120 );
		$now             = time();

		// Remove stale visitors.
		if ( ! empty( $active_visitors ) ) {
			foreach ( $active_visitors as $user_ip => $last_visit_time ) {
				if ( $now > $last_visit_time + $threshold ) {
					unset( $active_visitors[ $user_ip ] );
				}
			}
		}

		$active_visitors[ self::get_visitor_ip() ] = $now;

		update_post_meta( $post_id, 'awb_current_post_visitors', $active_visitors );
	}

	/**
	 * Get current post visitor count.
	 *
	 * @access public
	 * @since 7.14.1
	 * @param integer $post_id Product ID.
	 * @param integer $live_visits Current visit.
	 * @return int
	 */
	public static function get_current_visitors_count( $post_id ) {
		if ( 'fuzzy' === Avada()->settings->get( 'current_post_visitors_count_mode' ) ) {
			$count = self::update_and_get_fuzzy_visitor_value( $post_id );
		} else {		
			$active_visitors = get_post_meta( $post_id, 'awb_current_post_visitors', true );
			$active_visitors = ! is_array( $active_visitors ) ? [] : $active_visitors;
			$count           = count( $active_visitors );
		}

		return $count;
	}	

	/**
	 * Update and return the fuzzy visitor value.
	 *
	 * @access public
	 * @since 7.14.1
	 * @param string|int $post_id The current post ID.
	 * @return int The new fuzzy value.
	 */
	public static function update_and_get_fuzzy_visitor_value( $post_id ) {

		// Only count if active.
		if ( ! self::is_current_post_visitors_count_active() ) {
			return 0;
		}
		
		$min_max = Avada()->settings->get( 'current_post_visitors_count_fuzzy_values' );
		$min     = isset( $min_max['bottom'] ) ? intval( $min_max['bottom'] ) : 10;
		$max     = isset( $min_max['top'] ) ? intval( $min_max['top'] ) : 25;
		$range   = apply_filters( 'awb_current_post_visitors_fuzzy_range', 5 );
		$last    = get_post_meta( $post_id, 'awb_current_post_visitors_fuzzy', true );

		// First time: choose random inside full range.
		if ( '' === $last || false === $last ) {
			$value = wp_rand( $min, $max );
			update_post_meta( $post_id, 'awb_current_post_visitors_fuzzy', $value );
			return $value;
		}

		// Generate new candidate.
		$candidate = wp_rand( $min, $max );

		// Restrict jump.
		$lower_bound = max( $min, $last - $range );
		$upper_bound = min( $max, $last + $range );

		// Clamp candidate inside the allowed movement window.
		$value = max( $lower_bound, min( $candidate, $upper_bound ) );

		update_post_meta( $post_id, 'awb_current_post_visitors_fuzzy', $value );

		return $value;
	}

	/**
	 * Update current post visitor count via AJAX.
	 *
	 * @access public
	 * @since 7.14.1
	 * @return void
	 */
    public function update_current_post_visitors_count_ajax() {
		if ( ! isset( $_POST['post_id'] ) || ! $_POST['post_id'] ) {
			return;
		}
		
		$post_id = intval( sanitize_text_field( $_POST['post_id'] ) );

		if ( 'fuzzy' === Avada()->settings->get( 'current_post_visitors_count_mode' ) ) {
			$count = self::update_and_get_fuzzy_visitor_value( $post_id );
		} else {
			$this->update_current_post_visitors_count( $post_id );
			$count = $this->get_current_visitors_count( $post_id );
		}

        wp_send_json( [ 'count' => $count ] );
    }

	/**
	 * Get IP of the current visitor.
	 * 
	 * @access public
	 * @since 7.14.1
	 * @return string The IP of the current visitor.
	 */
	public static function get_visitor_ip() {
		$keys = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR'
		];

		foreach ( $keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip_list = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) );
				$ip = trim( $ip_list[0] );

				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					$ip = '1' === Avada()->settings->get( 'current_post_visitors_count_real_hash' ) ? substr ( hash('sha256', $ip . AUTH_SALT ) , 0, 15 ) : $ip;
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Increase the post vies count.
	 * @since 7.5
	 * @access public
	 * @return void	 
	 */
	public function set_post_views() {
		global $post;

		if ( ! is_singular() || is_preview() || $this->is_live_editor() ) {
			return;
		}

		if ( empty( $post->ID ) ) {
			return;
		}

		$this->increase_post_views( $post->ID );
	}

	/**
	 * Function that triggers at action to increase today views.
	 *
	 * @since 7.5
	 * @access public
	 * @return void
	 */
	public function set_post_views_today() {
		global $post;

		if ( ! is_singular() || is_preview() || $this->is_live_editor() ) {
			return;
		}

		if ( empty( $post->ID ) ) {
			return;
		}

		$this->increase_post_views_today( $post->ID );
	}

	/**
	 * Function that triggers at action to increase post views.
	 * @since 7.5
	 * @access public
	 * @return void
	 */
	public function set_post_views_ajax() {
		if ( ! isset( $_POST['postId'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$post_id = intval( wp_unslash( $_POST['postId'] ) ); //phpcs:ignore WordPress.Security.NonceVerification
		if ( ! ( $post_id > 0 ) ) {
			return;
		}

		$this->increase_post_views( $post_id );
		$this->increase_post_views_today( $post_id );
	}

	/**
	 * For a given post_id, increase the views.
	 *
	 * @since 7.5
	 * @access public
	 * @param int $post_id Post id.
	 * @return void
	 */
	public function increase_post_views( $post_id ) {
		if ( ! $post_id || ! $this->can_increase_post_views() ) {
			return;
		}

		$count = self::get_post_views( $post_id );
		$count++;
		update_post_meta( $post_id, 'avada_post_views_count', $count );
	}

	/**
	 * For a given post_id, increase the today views.
	 *
	 * @since 7.5
	 * @access public
	 * @param int $post_id Post id.
	 * @return void
	 */
	public function increase_post_views_today( $post_id ) {
		if ( ! $post_id || ! $this->can_increase_post_views() ) {
			return;
		}

		$today_views = self::get_post_views_today( $post_id );
		$today_views++;
		update_post_meta( $post_id, 'avada_today_post_views_count', $today_views );

		// Check if also it's needed to update the today date, and update if necessary.
		if ( ! self::are_post_views_stored_from_today( $post_id ) ) {
			update_post_meta( $post_id, 'avada_post_views_count_today_date', date( 'd-m-Y' ) );
		}
	}

	/**
	 * Check if post views can be increased.
	 *
	 * @since 7.5
	 * @access public
	 * @return bool
	 */
	public function can_increase_post_views() {
		$fusion_settings        = awb_get_fusion_settings();
		$increase_views_setting = $fusion_settings->get( 'post_views_counting' );

		$can_increase_views = false;

		if ( 'all' === $increase_views_setting ) {
			$can_increase_views = true;
		}

		if ( 'logged_out' === $increase_views_setting && ! is_user_logged_in() ) {
			$can_increase_views = true;
		}

		if ( 'non_admins' === $increase_views_setting && ! current_user_can( 'manage_options' ) ) {
			$can_increase_views = true;
		}

		$can_increase_views = apply_filters( 'avada_can_increase_post_views', $can_increase_views );

		return $can_increase_views;
	}

	/**
	 * Get the post views of a post.
	 *
	 * @since 7.5
	 * @access public
	 * @param WP_Post|int|null $post The post object, id or null. Defaults to query post.
	 * @return int
	 */
	public static function get_post_views( $post = null ) {
		if ( -99 === intval( $post ) ) {
			return 100;
		}

		$post = get_post( $post );
		if ( ! $post ) {
			return 0;
		}
		$post_id = $post->ID;

		$count_key = 'avada_post_views_count';
		$count     = get_post_meta( $post_id, $count_key, true );

		if ( ! is_numeric( $count ) ) {
			return 0;
		}

		return (int) $count;
	}

	/**
	 * Get the today post views of a post.
	 *
	 * @since 7.5
	 * @access public
	 * @param WP_Post|int|null $post The post object, id or null. Defaults to query post.
	 * @return int
	 */
	public static function get_post_views_today( $post = null ) {
		if ( -99 === intval( $post ) ) {
			return 20;
		}

		$post = get_post( $post );
		if ( ! $post ) {
			return 0;
		}
		$post_id = $post->ID;

		if ( ! self::are_post_views_stored_from_today( $post ) ) {
			return 0;
		}

		$count_key = 'avada_today_post_views_count';
		$count     = get_post_meta( $post_id, $count_key, true );

		if ( ! is_numeric( $count ) ) {
			return 0;
		}

		return (int) $count;
	}

	/**
	 * Check if the today date is set correctly in meta, or in another words,
	 * check if the views counted are from today.
	 *
	 * @since 7.5
	 * @access public
	 * @param WP_Post|int|null $post The post object, id or null. Defaults to query post.
	 * @return bool
	 */
	public static function are_post_views_stored_from_today( $post = null ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return 0;
		}
		$post_id = $post->ID;

		$post_meta_today = get_post_meta( $post_id, 'avada_post_views_count_today_date', true );
		$today           = date( 'd-m-Y' );

		if ( $today === $post_meta_today ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the current post visitors counter is active.
	 *
	 * @since 7.14.1
	 * @access public
	 * @return bool
	 */	
	public static function is_current_post_visitors_count_active() {
        return 'disabled' !== Avada()->settings->get( 'current_post_visitors_count' );
	}
			

	/**
	 * Checks if we are loading the Live Editor.
	 *
	 * @since 7.14.1
	 * @access public
	 * @return bool
	 */	
	public function is_live_editor() {
		return ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) || ( function_exists( 'fusion_is_builder_frame' ) && fusion_is_builder_frame() );
	}	

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @static
	 * @access public
	 * @since 7.14.1
	 * @return object AWB_View_Count
	 */
	public static function get_instance() {

		// If an instance hasn't been created and set to $instance create an instance and set it to $instance.
		if ( null === self::$instance ) {
			self::$instance = new AWB_View_Count();
		}
		return self::$instance;
	}
}

/**
 * Instantiates the AWB_View_Count class.
 * Make sure the class is properly set-up.
 *
 * @since 7.14.1
 * @return object AWB_View_Count
 */
function AWB_View_Count() { // phpcs:ignore WordPress.NamingConventions
	return AWB_View_Count::get_instance();
}
