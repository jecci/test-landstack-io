<?php
/**
 * Handles custom auth pages.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      7.14.1
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Handles custom auth pages.
 */
class AWB_Custom_Auth_Pages {

	/**
	 * The one, true instance of this object.
	 *
	 * @static
	 * @since 7.14.1
	 * @access private
	 * @var object
	 */
	private static $instance;

	/**
	 * The bypass cookie name.
	 *
	 * @since 7.14.1
	 * @access protected
	 * @var string
	 */
	protected $bypass_cookie_name = 'awb_bypass_wp_login';

	/**
	 * The constructor.
	 *
	 * @since 7.14.1
	 * @access private
	 * @return void
	 */
	private function __construct() {
		add_action( 'wp_loaded', [ $this, 'do_auth_redirects' ], 20 );
		add_action( 'wp_login', [ $this, 'clear_bypass_cookie' ] );
		add_filter( 'logout_redirect', [ $this, 'do_logout_redirect' ], 10, 3 );

		add_filter( 'retrieve_password_message', [ $this, 'ajust_reset_password_email' ], 10, 4 );
	}	

	public function do_auth_redirects() {

		// We don't interfere with WooCommerce authentication pages.
		if ( $this->is_woocommerce_auth_page() ) {
			return;
		}

		$bypass_param = Avada()->settings->get( 'auth_pages_bypass_param' );

		// Clear the bypass cookie, if we no longer have a bypass query arg set in GO.
		if ( empty( $bypass_param ) ) {
			$this->clear_bypass_cookie();
		}

		// If we have a bypass query are set in GO and it is used in the current request, set the cookie.
		if ( ! empty( $bypass_param ) && isset( $_GET[ $bypass_param ] ) ) {
			$this->set_bypass_cookie();

			// UIf the cookie isn't already set, bypass on first  request.
			if ( ! isset( $_COOKIE['awb_bypass_wp_login'] ) ) {
				return;
			}
		}

		// If the cookie is set, bypass redirects.
		if ( isset( $_COOKIE['awb_bypass_wp_login'] ) && 1 === (int) $_COOKIE['awb_bypass_wp_login'] ) {
			return;
		}

		$current_request_uri       = $_SERVER['REQUEST_URI'] ? esc_url_raw( $_SERVER['REQUEST_URI'] ) : '';
		$current_url_path          = rtrim( wp_parse_url( $current_request_uri, PHP_URL_PATH ), '/' );
		$action                    = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : false;
		$wp_auth_page_match        = false;

		$wp_login_url_path         = rtrim( wp_parse_url( wp_login_url(), PHP_URL_PATH ), '/'  );
		$wp_registration_url_path  = rtrim( wp_parse_url( wp_registration_url(), PHP_URL_PATH ), '/'  );
		$wp_lost_password_url_path = rtrim( wp_parse_url( wp_lostpassword_url(), PHP_URL_PATH ), '/'  );

		// Check if we have a match.
		if ( $current_url_path === $wp_login_url_path ) {
			$wp_auth_page_match = 'wp_login';
		} else if ( $current_url_path === $wp_registration_url_path ) {
			$wp_auth_page_match = 'wp_registration';
		} else if ( $current_url_path === $wp_lost_password_url_path ) {
			$wp_auth_page_match = 'wp_lost_password';
		}

		// We need to redirect.
		if ( $wp_auth_page_match ) {
			$redirect_action = Avada()->settings->get( 'auth_pages_custom_redirect' );

			// A custom action was set.
			if ( 'auth_pages' !== $redirect_action ) {
				$this->do_custom_redirect( $redirect_action );
			} else {

				// Redirect to the corresponding set auth page.
				if ( 'wp_login' === $wp_auth_page_match ) {
					switch ( $action ) {
						case 'login':
						case false:
						default:
							$redirection_page = apply_filters( 'awb_auth_pages_login_redirect', Avada()->settings->get( 'auth_pages_login_page' ) );
							break;
						case 'register':
							$redirection_page = apply_filters( 'awb_auth_pages_registratio_redirect', Avada()->settings->get( 'auth_pages_registration_page' ) );
							break;
						case 'lostpassword':
							$redirection_page = apply_filters( 'awb_auth_pages_lost_password_redirect', Avada()->settings->get( 'auth_pages_lost_password_page' ) );
							break;
						case 'rp': // Reset password
							$redirection_page = apply_filters( 'awb_auth_pages_reset_password_redirect', Avada()->settings->get( 'auth_pages_reset_password_page' ) );
							break;
						case 'logout':

							// Just to be sure that we do not run into a redirct loop if a custom action is set.
							if ( is_user_logged_in() ) {
								return;
							}
							break;
					}

					$this->do_redirect( $redirection_page );
				}
			}
		}
	}

	/**
	 * Checks if the page request matches a WooCommerce authentication page.
	 *
	 * @since 7.14.1
	 * @access private
	 * @return bool
	 */
	private function is_woocommerce_auth_page() {
		if ( class_exists( 'WooCommerce' ) ) {
			$current_request_uri = $_SERVER['REQUEST_URI'] ? esc_url_raw( $_SERVER['REQUEST_URI'] ) : '';
			$current_url_path    = rtrim( wp_parse_url( $current_request_uri, PHP_URL_PATH ), '/' );
			$woo_endpoints       = [
				wc_get_account_endpoint_url( 'lost-password' ),
				add_query_arg( [ 'show-reset-form' => 'true' ], wc_get_account_endpoint_url( 'lost-password' ) ),
				wc_get_account_endpoint_url( 'customer-logout' )
			];

			foreach ( $woo_endpoints as $woo_endpoint ) {
				$woo_endpoint_path = rtrim( wp_parse_url( $woo_endpoint, PHP_URL_PATH ), '/' );
				if ( $woo_endpoint_path && 0 === strpos( $current_url_path, $woo_endpoint_path ) ) {
					return true;
				}
			}
		}

		return false;
	}	

	/**
	 * Sets the bypass cookie.
	 *
	 * @since 7.14.1
	 * @access private
	 * @return void
	 */
	private function set_bypass_cookie() {
		setcookie(
			$this->bypass_cookie_name,
			'1',
			$this->get_bypass_cookie_args( time() + 5 * 60 ) // Valid for 5mins.
		);
	}

	/**
	 * Clears the bypass cookie.
	 *
	 * @since 7.14.1
	 * @access public
	 * @return void
	 */
	public function clear_bypass_cookie() {
		if ( isset( $_COOKIE ) && isset( $_COOKIE[ $this->bypass_cookie_name ] ) ) {
			setcookie(
				$this->bypass_cookie_name,
				'',
				$this->get_bypass_cookie_args( time() - 3600 )
			);

			unset( $_COOKIE[ $this->bypass_cookie_name ] );
		}
	}

	/**
	 * Returns the cookie args.
	 *
	 * @since 7.14.1
	 * @access private
	 * @param int $expires The expiry timestamp.
	 * @return array The cookie args.
	 */
	private function get_bypass_cookie_args( $expires ) {
		return [
			'expires'  => $expires,
			'path'     => COOKIEPATH,
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Strict',
		];
	}

	/**
	 * Performs the redirects to the customarily chosen endpoints.
	 *
	 * @since 7.14.1
	 * @access private
	 * @param string $redirect_action The desired redirection action.
	 * @return void
	 */
	private function do_custom_redirect( $redirect_action ) {
		switch ( $redirect_action ) {
			case 'homepage':
				wp_safe_redirect( home_url() );
				exit;
			case '404':

				// Need a full redirect here because of the layout builder.
				wp_safe_redirect( home_url( '/error-404' ) );
				exit;
			case 'custom_page':
				$custom_redirect_page = Avada()->settings->get( 'auth_pages_custom_redirect_page' );

				if ( ! $this->do_redirect( $custom_redirect_page ) ) {
					wp_safe_redirect( home_url() );
					exit;
				}
		}
	}	

	/**
	 * Performs the actual redirects.
	 *
	 * @since 7.14.1
	 * @access private
	 * @param string|int $page_id The ID of the page to redirct to.
	 * @param bool $add_query_string A flag deciding if the query string should be appended.
	 * @return void|bool Only returns false, if a redirect can't be performed.
	 */
	private function do_redirect( $page_id, $add_query_string = true ) {
		if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
			$redirect_url = get_permalink( $page_id );
			$query_string = $_GET;

			if ( $redirect_url ) {
				if ( $add_query_string && is_array( $query_string ) ) {
					foreach ( $query_string as $key => $value ) {
						$redirect_url = add_query_arg( $key, rawurlencode( sanitize_text_field( $value ) ), $redirect_url );
					}
				}

				wp_safe_redirect( $redirect_url );
				exit;
			}
		}

		return false;
	}

	/**
	 * Performs redirect after successful logout to custom login page.
	 *
	 * @since 7.14.1
	 * @access public
	 * @param string  $redirect_to           The redirect destination URL.
	 * @param string  $requested_redirect_to The requested redirect destination URL passed as a parameter.
	 * @param WP_User $user                  The WP_User object for the user that's logging out.
	 * @return string
	 */
	public function do_logout_redirect( $redirect_to, $requested_redirect_to, $user ) {
		
		// By default WordPress tries to rediret to the login URL, which means we must cover that.
		$wp_login_url = wp_login_url();
		if ( 0 === strpos( $redirect_to, $wp_login_url ) ) {
			$redirection_page = apply_filters( 'awb_auth_pages_login_redirect', Avada()->settings->get( 'auth_pages_login_page' ) );

			if ( $redirection_page && 'publish' === get_post_status( $redirection_page ) ) {
				$redirect_to = get_permalink( $redirection_page ) . '?logged_out=true';
			}
		}

		return $redirect_to;
	}

	/**
	 * Make sure that also the reset password email uses rge custom reset password page URL.
	 *
	 * @since 7.14.1
	 * @access public
	 * @param string  $message    Email message.
	 * @param string  $key        The activation key.
	 * @param string  $user_login The username for the user.
	 * @param WP_User $user_data  WP_User object.
	 * @return string The modified email message.
	 */
	public function ajust_reset_password_email( $message, $key, $user_login, $user_data ) {
		$redirection_page = apply_filters( 'awb_auth_pages_reset_password_redirect', Avada()->settings->get( 'auth_pages_reset_password_page' ) );

		if ( $redirection_page && 'publish' === get_post_status( $redirection_page ) ) {
			$redirect_url = get_permalink( $redirection_page );
			$network_url  = network_site_url( 'wp-login.php' );

			$message      = str_replace( $network_url, $redirect_url, $message );
		}

		return $message;
	}	

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @static
	 * @since 7.14.1
	 * @access public
	 * @return object AWB_Custom_Auth_Pages
	 */
	public static function get_instance() {

		// If an instance hasn't been created and set to $instance create an instance and set it to $instance.
		if ( null === self::$instance ) {
			self::$instance = new AWB_Custom_Auth_Pages();
		}
		return self::$instance;
	}
}

/**
 * Instantiates the AWB_Custom_Auth_Pages class.
 * Make sure the class is properly set-up.
 *
 * @since 7.14.1
 * @return object AWB_Custom_Auth_Pages
 */
function AWB_Custom_Auth_Pages() { // phpcs:ignore WordPress.NamingConventions
	return AWB_Custom_Auth_Pages::get_instance();
}
AWB_Custom_Auth_Pages();
