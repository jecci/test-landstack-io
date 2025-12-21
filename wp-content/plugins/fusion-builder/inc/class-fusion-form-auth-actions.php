<?php
/**
 * Fusion Form Auth Actions.
 *
 * @package Fusion-Builder
 * @since 3.14.1
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Fusion Mailchimp class.
 *
 * @since 3.14.1
 */
class Fusion_Form_Auth_Actions {
	/**
	 * The one, true instance of this object.
	 *
	 * @static
	 * @access private
	 * @since 3.14.1
	 * @var object
	 */
	private static $instance;

	/**
	 * Fields.
	 *
	 * @static
	 * @access private
	 * @since 3.14.1
	 * @var mixed
	 */
	private $fields = null;

	/**
	 * Lists.
	 *
	 * @static
	 * @access private
	 * @since 3.14.1
	 * @var mixed
	 */
	private $lists = null;

	/**
	 * Class constructor.
	 *
	 * @since 3.14.1
	 * @access private
	 */
	private function __construct() {

		// Add the PO options to the form CPT.
		add_filter( 'avada_form_submission_sections', [ $this, 'add_options' ] );

		// Enqueue the JS script for the PO mapping option.
		add_action( 'avada_page_option_scripts', [ $this, 'option_script' ] );

		// Add fields list to live editor.
		add_filter( 'fusion_app_preview_data', [ $this, 'add_preview_data' ], 10, 3 );
	}

	/**
	 * Process login.
	 *
	 * @access public
	 * @since 3.14.1
	 * @param array $form_data Form submission data. Needs to be by reference, to update the data array.
	 * @param int $form_id Which form this is a submission for.
	 * @param array $form_meta Form meta.
	 * @param array $args Additional arguments.
	 * @return array
	 */
	public function process_login( &$form_data = [], $form_id = 0, $form_meta = [], $args = [] ) {
		$user             = false;
		$use_redirect_to  = isset( $form_meta['login_redirect_to'] ) && is_string( $form_meta['login_redirect_to'] ) ? 'yes' === $form_meta['login_redirect_to'] : true;
		$redirect_timeout = isset( $form_meta['login_redirect_timeout'] ) && is_string( $form_meta['login_redirect_timeout'] ) ? $form_meta['login_redirect_timeout'] : 0		;
		$encrypt_password = isset( $form_meta['encrypt_login_password_in_form_data'] ) && is_string( $form_meta['encrypt_login_password_in_form_data'] ) ? 'yes' === $form_meta['encrypt_login_password_in_form_data'] : false;		
		$display_notices  = isset( $form_meta['login_display_notices'] ) && is_string( $form_meta['login_display_notices'] ) ? 'yes' === $form_meta['login_display_notices'] : false;
		$mapping          = isset( $form_meta['login_map'] ) ? $form_meta['login_map'] : '';

		if ( ! isset( $form_data['data'] ) ) {
			return [
				'status'        => 'error',
				'info'          => esc_html__( 'Please ensure all fields are filled out.', 'fusion-builder' ),
				'force_display' => $display_notices,
			];
		}

		if ( empty( $mapping ) || ! is_string( $mapping ) ) {
			return [
				'status' => 'error',
				'info'   => esc_html__( 'Form fields were not mapped to WordPress login fields.', 'fusion-builder' ),
			];
		}

		$mapping = json_decode( $mapping, true );
		if ( ! empty( $mapping['user_login'] ) && ! empty( $mapping['user_pass'] ) ) {
			$credentials = [
				'user_login'    => isset( $form_data['data'][ $mapping['user_login'] ] ) ? $form_data['data'][ $mapping['user_login'] ] : '',
				'user_password' => isset( $form_data['data'][ $mapping['user_pass'] ] ) ? $form_data['data'][ $mapping['user_pass'] ] : '',
				'remember'      => ! empty( $mapping['rememberme'] ) && ! empty( $form_data['data'][ $mapping['rememberme'] ][0] ) ? true : false,
			];

			// Hash the password, if that is set in the options.
			if ( $encrypt_password ) {
				$form_data['data'][ $mapping['user_pass'] ] = wp_hash_password ( $form_data['data'][ $mapping['user_pass'] ] );
			}			

			$user = wp_signon( $credentials );
		}

		if ( ! $user || is_wp_error( $user ) ) {
			return [
				'status'        => 'error',
				'info'          => esc_html__( 'The login data was incorrect.', 'fusion-builder' ),
				'force_display' => $display_notices,
			];
		}

		// Create a save redirect.
		if ( $use_redirect_to && isset( $form_data['data']['redirect_to'] ) ) {
			$redirect_to = esc_url_raw( $form_data['data']['redirect_to'] );
			$redirect_to = wp_validate_redirect( $redirect_to, admin_url() );

			// Access check: Can the user access wp-admin?
			$is_admin_url = str_starts_with( $redirect_to, admin_url() );

			if ( $is_admin_url && ! user_can( $user, 'read' ) ) {

				// User cannot access wp-admin, use safe fallback.
				$redirect_to = home_url( '/' );
			}

			$form_data['data']['redirect_to'] = $redirect_to;
			$form_data['data']['redirect_timeout'] = $redirect_timeout;
			
		} else if ( ! $use_redirect_to && isset( $form_data['data']['redirect_to'] ) ) {
			unset( $form_data['data']['redirect_to'] );
		}

		return [
			'status'        => 'success',
			'info'          => sprintf( esc_html__( 'Thank you for logging in %s.', 'fusion-builder' ), $user->data->user_nicename ),
			'force_display' => $display_notices,
		];
	}

	/**
	 * Process registration.
	 *
	 * @access public
	 * @since 3.14.1
	 * @param array $form_data Form submission data. Needs to be by reference, to update the data array.
	 * @param int $form_id Which form this is a submission for.
	 * @param array $form_meta Form meta.
	 * @param array $args Additional arguments.
	 * @return array
	 */
	public function process_register( &$form_data = [], $form_id = 0, $form_meta = [], $args = [] ) {
		$user_role            = isset( $form_meta['user_role'] ) && ! in_array( $form_meta['user_role'], [ 'super_admin', 'administrator' ], true ) ? $form_meta['user_role'] : get_option( 'default_role' );
		$auto_create_username = isset( $form_meta['auto_create_username'] ) && is_string( $form_meta['auto_create_username'] ) ? 'yes' === $form_meta['auto_create_username'] : false;
		$encrypt_password     = isset( $form_meta['encrypt_register_password_in_form_data'] ) && is_string( $form_meta['encrypt_register_password_in_form_data'] ) ? 'yes' === $form_meta['encrypt_register_password_in_form_data'] : false;
		$min_password_length  = isset( $form_meta['min_password_length'] ) ? (int) $form_meta['min_password_length'] : 0;
		$auto_create_password = isset( $form_meta['auto_create_password'] ) && is_string( $form_meta['auto_create_password'] ) ? 'yes' === $form_meta['auto_create_password'] : false;
		$send_wp_notification = isset( $form_meta['send_wp_notification'] ) && is_string( $form_meta['send_wp_notification'] ) ? 'yes' === $form_meta['send_wp_notification'] : false;
		$auto_login           = isset( $form_meta['auto_login'] ) && is_string( $form_meta['auto_login'] ) ? 'yes' === $form_meta['auto_login'] : false;
		$display_notices      = isset( $form_meta['register_display_notices'] ) && is_string( $form_meta['register_display_notices'] ) ? 'yes' === $form_meta['register_display_notices'] : false;
		$mapping              = isset( $form_meta['register_map'] ) ? $form_meta['register_map'] : '';

		if ( ! isset( $form_data['data'] ) ) {
			return [
				'status'        => 'error',
				'info'          => esc_html__( 'Please ensure all fields are filled out.', 'fusion-builder' ),
				'force_display' => $display_notices,
			];
		}

		if ( empty( $mapping ) || ! is_string( $mapping ) ) {
			return [
				'status'        => 'error',
				'info'          => esc_html__( 'Form fields were not mapped to WordPress registration fields.', 'fusion-builder' ),
			];
		}		

		$mapping = json_decode( $mapping, true );

		if ( ! empty( $mapping['user_email'] ) ) {
			$user_email = isset( $form_data['data'][ $mapping['user_email'] ] ) ? $form_data['data'][ $mapping['user_email'] ] : '';
			$user_login = isset( $form_data['data'][ $mapping['user_login'] ] ) ? $form_data['data'][ $mapping['user_login'] ] : '';
			$user_pass  = isset( $form_data['data'][ $mapping['user_pass'] ] ) ? $form_data['data'][ $mapping['user_pass'] ] : '';
			$first_name = isset( $form_data['data'][ $mapping['first_name'] ] ) ? $form_data['data'][ $mapping['first_name'] ] : '';
			$last_name  = isset( $form_data['data'][ $mapping['last_name'] ] ) ? $form_data['data'][ $mapping['last_name'] ] : '';

			$email_error = $this->validate_email( $user_email, $display_notices );
			if ( $email_error ) {
				return $email_error;
			}

			// If we have a valid email but no username (user_login), generate one based on the email.
			if ( ! $user_login && $auto_create_username ) {
				$user_login = str_replace( '.', '', explode( '@', $user_email )[0] );

				// Make sure the username is at least 5 digits long.
				$user_login = 3 > strlen( $user_login ) ? $user_login . rand( 100, 999 ) : $user_login;

				// If the username already exists, append a number and create then.
				if ( username_exists( $user_login ) ) {
					$i = 1;
					while ( username_exists( $user_login . $i ) ) {
						$i++;
					}
					$user_login = $user_login . $i;
				}

				$form_data['data'][ $mapping['user_login'] ] = $user_login;
			} else {
				$user_login_error = $this->validate_user_login( $user_login, $display_notices );
				if ( $user_login_error ) {
					return $user_login_error;
				}
			}

			// If no password was set, generate a new one with at least $min_password_length chars.
			if ( ! $user_pass && $auto_create_password ) {
				$pass_length = 12 < $min_password_length ? $min_password_length : 12;			
				$user_pass   = wp_generate_password( $pass_length );

				$form_data['data'][ $mapping['user_pass'] ] = $user_pass;
			} else {
				$user_pass_error = $this->validate_user_pass( $user_pass, $min_password_length, $display_notices );
				if ( $user_pass_error ) {
					return $user_pass_error;
				}
			}

			// Hash the password, if that is set in the options.
			if ( $encrypt_password ) {
				$form_data['data'][ $mapping['user_pass'] ] = wp_hash_password ( $user_pass );
			}

			// Add new user
			$user_id = wp_insert_user( [
				'user_login'           => $user_login,
				'user_email'           => $user_email,
				'user_pass'            => $user_pass,
				'first_name'           => $first_name,
				'last_name'            => $last_name,
				'role'                 => $user_role,
			] );

			if ( is_wp_error( $user_id ) ) {
				return [
					'status'        => 'error',
					'info'          => $user_id->get_error_message(),
					'force_display' => $display_notices,
				];
			}

			// Send WP new user notification, if set to.
			if ( $send_wp_notification ) {
				do_action( 'register_new_user', $user_id );
			}


			// Log in user, if set to.
			if ( $auto_login ) {
				wp_set_current_user( $user_id );
				wp_set_auth_cookie( $user_id, false, is_ssl() );
			}

			return [
				'status' => 'success',
				'info'   => sprintf( esc_html__( 'The user with the ID %d was successfully created.', 'fusion-builder' ), $user_id ),
			];
		}

		return [
			'status'        => 'error',
			'info'          => esc_html__( 'User could not be created.', 'fusion-builder' ),
			'force_display' => $display_notices,
		];
	}

	/**
	 * Process lost password.
	 *
	 * @access public
	 * @since 3.14.1
	 * @param array $form_data Form submission data. Needs to be by reference, to update the data array.
	 * @param int $form_id Which form this is a submission for.
	 * @param array $form_meta Form meta.
	 * @param array $args Additional arguments.
	 * @return array
	 */
	public function process_lost_password( &$form_data = [], $form_id = 0, $form_meta = [], $args = [] ) {
		$display_notices      = isset( $form_meta['lost_password_display_notices'] ) && is_string( $form_meta['lost_password_display_notices'] ) ? 'yes' === $form_meta['lost_password_display_notices'] : false;
		$mapping              = isset( $form_meta['lost_password_map'] ) ? $form_meta['lost_password_map'] : '';

		if ( ! isset( $form_data['data'] ) ) {
			return [
				'status'        => 'error',
				'info'          => esc_html__( 'Please ensure all fields are filled out.', 'fusion-builder' ),
				'force_display' => $display_notices,
			];
		}

		if ( empty( $mapping ) || ! is_string( $mapping ) ) {
			return [
				'status'        => 'error',
				'info'          => esc_html__( 'Form fields were not mapped to the WordPress lost password field.', 'fusion-builder' ),
			];
		}		

		$mapping = json_decode( $mapping, true );

		if ( ! empty( $mapping['user_login'] ) ) {
			$user_login = isset( $form_data['data'][ $mapping['user_login'] ] ) ? $form_data['data'][ $mapping['user_login'] ] : '';
			$user       = is_email( $user_login ) ? get_user_by( 'email', $user_login ) : get_user_by( 'login', $user_login );

			if ( $user && ! is_wp_error( $user ) ) {
				retrieve_password( $user->user_login );

				return [
					'status'        => 'success',
					'info'          => esc_html__( 'If the account exists, a password reset link will be sent to its email address.', 'fusion-builder' ),
					'force_display' => $display_notices,
				];
			} else {
				return [
					'status'        => 'error',
					'info'          => esc_html__( 'There is no account with that username or email address.', 'fusion-builder' ),
					'force_display' => $display_notices,
				];			
			}	
		}

		return [
			'status'        => 'error',
			'info'          => esc_html__( 'User could not be identified.', 'fusion-builder' ),
			'force_display' => $display_notices,
		];		
	}

	/**
	 * Process reset password.
	 *
	 * @access public
	 * @since 3.14.1
	 * @param array $form_data Form submission data. Needs to be by reference, to update the data array.
	 * @param int $form_id Which form this is a submission for.
	 * @param array $form_meta Form meta.
	 * @param array $args Additional arguments.
	 * @return array
	 */
	public function process_reset_password( &$form_data = [], $form_id = 0, $form_meta = [], $args = [] ) {
		$encrypt_password     = isset( $form_meta['encrypt_reset_password_in_form_data'] ) && is_string( $form_meta['encrypt_reset_password_in_form_data'] ) ? 'yes' === $form_meta['encrypt_reset_password_in_form_data'] : false;
		$display_notices      = isset( $form_meta['reset_password_display_notices'] ) && is_string( $form_meta['reset_password_display_notices'] ) ? 'yes' === $form_meta['reset_password_display_notices'] : false;
		$mapping              = isset( $form_meta['reset_password_map'] ) ? $form_meta['reset_password_map'] : '';

		if ( ! isset( $form_data['data'] ) ) {
			return [
				'status'        => 'error',
				'info'          => esc_html__( 'Please ensure all fields are filled out.', 'fusion-builder' ),
				'force_display' => $display_notices,
			];
		}

		if ( empty( $mapping ) || ! is_string( $mapping ) ) {
			return [
				'status'        => 'error',
				'info'          => esc_html__( 'Form fields were not mapped to the WordPress reset password field.', 'fusion-builder' ),
			];
		}		

		$mapping = json_decode( $mapping, true );

		if ( ! empty( $mapping['user_pass'] ) ) {
			$user_login = $form_data['data']['awb_pw_reset_login'];
			$key        = $form_data['data']['awb_pw_reset_key'];
			$user_pass  = isset( $form_data['data'][ $mapping['user_pass'] ] ) ? $form_data['data'][ $mapping['user_pass'] ] : '';

			if ( empty( $user_pass ) ) {
				return [
					'status'        => 'error',
					'info'          => esc_html__( 'No password provided.', 'fusion-builder' ),
					'force_display' => $display_notices,
				];
			}	

			// Returns WP_User object or WP_Error.
			$user = check_password_reset_key( $key, $user_login );

			// Hash the password, if that is set in the options.
			if ( $encrypt_password ) {
				$form_data['data'][ $mapping['user_pass'] ] = wp_hash_password ( $user_pass );
			}			

			if ( is_wp_error( $user ) ) {
				return [
					'status'        => 'error',
					'info'          => esc_html__( 'The password reset key is invalid.', 'fusion-builder' ),
					'force_display' => $display_notices,
				];
			}

			reset_password( $user, $user_pass );

			return [
				'status'        => 'success',
				'info'          => esc_html__( 'The password was successfully reset.', 'fusion-builder' ),
				'force_display' => $display_notices,
			];
		}

		return [
			'status'        => 'error',
			'info'          => esc_html__( 'An unexpected error happened.', 'fusion-builder' ),
			'force_display' => $display_notices,
		];		
	}	

	/**
	 * Validates an email address for WP usage.
	 *
	 * @access private
	 * @since  3.14.1
	 * @param  string $email The email to validate.
	 * @param bool $display_notices Flag to decide if the error should buble up for every user role.
	 * @return bool|array Returns error array on failure.
	 */	
	private function validate_email( $email, $display_notices ) {
		if ( ! $email ) {
			return [
				'status'        => 'error',
				'info'          => esc_html__( 'An email address is required for registration.', 'fusion-builder' ),
				'force_display' => $display_notices,
			];
		}

		if ( ! is_email( $email ) ) {
			return [
				'status'        => 'error',
				'info'          => esc_html__( 'The email address is not valid.', 'fusion-builder' ),
				'force_display' => $display_notices,
			];
		}

		if ( email_exists( $email ) ) {
			return [
				'status'        => 'error',
				'info'          => esc_html__( 'This email address is already registered.', 'fusion-builder' ),
				'force_display' => $display_notices,
			];
		}

		return false;
	}

	/**
	 * Validates a user login for WP usage.
	 *
	 * @access private
	 * @since  3.14.1
	 * @param  string $user_login The user login to validate.
	 * @param bool $display_notices Flag to decide if the error should buble up for every user role.
	 * @return bool|array Returns error array on failure.
	 */	
	private function validate_user_login( $user_login, $display_notices ) {
		if ( ! $user_login ) {
			return [
				'status'        => 'error',
				'info'          => esc_html__( 'A username is required.', 'fusion-builder' ),
				'force_display' => $display_notices,
			];
		}

		if ( ! validate_username( $user_login ) ) {
			return [
				'status'        => 'error',
				'info'          => esc_html__( 'The username is not valid.', 'fusion-builder' ),
				'force_display' => $display_notices,
			];
		}

		if ( username_exists( $user_login ) ) {
			return [
				'status'        => 'error',
				'info'          => esc_html__( 'This username is already registered.', 'fusion-builder' ),
				'force_display' => $display_notices,
			];
		}

		return false;
	}

	/**
	 * Validates a user password for WP usage.
	 *
	 * @access private
	 * @since  3.14.1
	 * @param  string $user_pass The user password to validate.
	 * @param int $min_pass_length The minimum length of the password.
	 * @param bool $display_notices Flag to decide if the error should buble up for every user role.
	 * @return bool|array Returns error array on failure.
	 */	
	private function validate_user_pass( $user_pass, $min_pass_length, $display_notices ) {
		if ( ! $user_pass ) {
			return [
				'status'        => 'error',
				'info'          => esc_html__( 'A password is required.', 'fusion-builder' ),
				'force_display' => $display_notices,
			];
		}

		if ( strlen( $user_pass ) < $min_pass_length ) {
			return [
				'status'        => 'error',
				'info'          => sprintf( esc_html__( 'The password needs to be at least %s characters long.', 'fusion-builder' ), $min_pass_length ),
				'force_display' => $display_notices,
			];
		}

		return false;
	}	

	/**
	 * Add fields options to live editor.
	 *
	 * @access public
	 * @since  3.14.1
	 * @param  array  $data The data already added.
	 * @param  int    $page_id The post ID being edited.
	 * @param  string $post_type The post type being edited.
	 * @return array $data The data with panel data added.
	 */
	public function add_preview_data( $data, $page_id = 0, $post_type = 'page' ) {
		if ( 'fusion_form' === $post_type ) {
			$data['authmap'] = $this->get_strings();
		}
		return $data;
	}

	/**
	 * Add field data.
	 *
	 * @since 3.14.1
	 * @access public
	 * @param string $post_type Post type being added to.
	 * @return void
	 */
	public function option_script( $post_type ) {
		// Not editing a form then we don't need it.
		if ( 'fusion_form' !== $post_type ) {
			return;
		}

		wp_enqueue_script( 'awb_auth_map_option', FUSION_BUILDER_PLUGIN_URL . 'assets/admin/js/awb-authmap-option.js', [], FUSION_BUILDER_VERSION, true );

		// Add field data.
		wp_localize_script(
			'awb_auth_map_option',
			'awbAuthMap',
			$this->get_strings(),
		);
	}

	/**
	 * Return the translation strings.
	 *
	 * @since 3.14.1
	 * @access public
	 * @return array The array with the translation strings.
	 */
	public function get_strings() {
		return  [
			'label_placeholder'   => __( 'Select form field', 'fusion-builder' ),
			'label_user_login'    => __( 'Field: Login', 'fusion-builder' ),
			'label_user_pass'     => __( 'Field: Password', 'fusion-builder' ),
			'label_rememberme'    => __( 'Field: Remember Me', 'fusion-builder' ),
			'label_username'      => __( 'Field: Username', 'fusion-builder' ),
			'label_user_email'    => __( 'Field: Email', 'fusion-builder' ),
			'label_first_name'    => __( 'Field: First Name', 'fusion-builder' ),
			'label_last_name'     => __( 'Field: Last Name', 'fusion-builder' ),
			'label_user_role'     => __( 'User Role', 'fusion-builder' ),
			'label_lost_password' => __( 'Field: Username Or Email Address', 'fusion-builder' ),
		];
	}

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @static
	 * @access public
	 * @param array $sections Page options.
	 * @since 3.14.1
	 */
	public function add_options( $sections ) {
		global $wp_roles;
		$user_roles = wp_list_pluck( $wp_roles->roles, 'name' );
		unset( $user_roles['administrator'] );

		$sections['form_submission']['fields']['login_option'] = [
			'type'       => 'toggle',
			'row_title'  => esc_html__( 'Login', 'fusion-builder' ),
			'id'         => 'login_option',
			'dependency' => [
				[
					'field'      => 'form_type',
					'value'      => 'ajax',
					'comparison' => '==',
				],
				[
					'field'      => 'form_actions',
					'value'      => 'login',
					'comparison' => 'contains',
				],
			],
			'fields'     => [
				'login_map'           => [
					'type'        => 'auth_map',
					'label'       => esc_html__( 'Form Field Mapping', 'fusion-builder' ),
					'description' => esc_html__( 'Map fields from the form to the WordPress login fields.', 'fusion-builder' ),
					'id'          => 'login_map',
					'transport'   => 'postMessage',
				],
				'login_redirect_to' => [
					'type'        => 'radio-buttonset',
					'label'       => esc_html__( 'Use WP Redirect To', 'fusion-builder' ),
					'description' => esc_html__( 'Set to "yes" if you want to use the WordPress redirect to feature, to take the logged in user back to the protected page that was initially visited.', 'fusion-builder' ),
					'id'          => 'login_redirect_to',
					'default'     => 'yes',
					'choices'     => [
						'yes' => esc_html__( 'Yes', 'fusion-builder' ),
						'no'  => esc_html__( 'No', 'fusion-builder' ),
					],
				],
				'login_redirect_timeout' => [
					'type'        => 'slider',
					'label'       => esc_html__( 'Redirect After', 'fusion-builder' ),
					'description' => esc_html__( 'Set a timeout before the redirect takes place. In milliseconds.', 'fusion-builder' ),
					'id'          => 'login_redirect_timeout',
					'transport'   => 'postMessage',
					'default'     => '0',
					'choices'     => [
						'step' => '100',
						'min'  => '0',
						'max'  => '5000',
					],
					'dependency'  => [
						[
							'field'      => 'login_redirect_to',
							'value'      => 'yes',
							'comparison' => '=',
						],
					],
				],
				'encrypt_login_password_in_form_data' => [
					'type'        => 'radio-buttonset',
					'label'       => esc_html__( 'Encrypt Password', 'fusion-builder' ),
					'description' => esc_html__( 'If set to "yes", the password will be encrypted in any other form data transmission, like saving to database or a notification email.', 'fusion-builder' ),
					'id'          => 'encrypt_login_password_in_form_data',
					'default'     => 'no',
					'choices'     => [
						'yes' => esc_html__( 'Yes', 'fusion-builder' ),
						'no'  => esc_html__( 'No', 'fusion-builder' ),
					],
				],
				'login_display_notices' => [
					'type'        => 'radio-buttonset',
					'label'       => esc_html__( 'Display Notices', 'fusion-builder' ),
					'description' => esc_html__( 'Set to "yes" to add additional internal notices to the Notice element.', 'fusion-builder' ),
					'id'          => 'login_display_notices',
					'default'     => 'no',
					'choices'     => [
						'yes' => esc_html__( 'Yes', 'fusion-builder' ),
						'no'  => esc_html__( 'No', 'fusion-builder' ),
					],
				],
			],
		];

		$sections['form_submission']['fields']['register_option'] = [
			'type'       => 'toggle',
			'row_title'  => esc_html__( 'Register', 'fusion-builder' ),
			'id'         => 'register_option',
			'dependency' => [
				[
					'field'      => 'form_type',
					'value'      => 'ajax',
					'comparison' => '==',
				],
				[
					'field'      => 'form_actions',
					'value'      => 'register',
					'comparison' => 'contains',
				],
			],
			'fields'     => [
				'register_map'           => [
					'type'        => 'auth_map',
					'label'       => esc_html__( 'Form Field Mapping', 'fusion-builder' ),
					'description' => sprintf( esc_html__( 'Map fields from the form to the WordPress registration fields. To customize the emails sent to admin and the new user use the %1$s and %2$s hook respectively.', 'fusion-builder' ), '<a href="https://developer.wordpress.org/reference/hooks/wp_new_user_notification_email_admin/" target="_blank">wp_new_user_notification_email_admin</a>', '<a href="https://developer.wordpress.org/reference/hooks/wp_new_user_notification_email/" target="_blank">wp_new_user_notification_email</a>' ),
					'id'          => 'register_map',
					'transport'   => 'postMessage',
				],
				'user_role' => [
					'type'        => 'select',
					'label'       => esc_attr__( 'User Role', 'fusion-builder' ),
					'description' => esc_html__( 'Select the role for the new user. Administrator is disabled for security reasons.', 'fusion-builder' ),
					'id'          => 'user_role',
					'choices'     => $user_roles,
					'default'     => 'subscriber',
				],
				'min_password_length' => [
					'type'        => 'slider',
					'label'       => esc_html__( 'Password Minimum Length', 'fusion-builder' ),
					'description' => esc_html__( 'Set a minimum length for the password. Set to "0" to disable minimum length. If the password is auto-created, the minimum length will always be at least 12.', 'fusion-builder' ),
					'id'          => 'min_password_length',
					'default'     => '6',
					'transport'   => 'postMessage',
					'choices'     => [
						'step' => '1',
						'min'  => '0',
						'max'  => '30',
					],
				],
				'auto_create_username' => [
					'type'        => 'radio-buttonset',
					'label'       => esc_html__( 'Auto Create Username', 'fusion-builder' ),
					'description' => esc_html__( 'If set to "yes" and no username is specified in the form, the username will be auto-created based on the given email address.', 'fusion-builder' ),
					'id'          => 'auto_create_username',
					'default'     => 'no',
					'choices'     => [
						'yes' => esc_html__( 'Yes', 'fusion-builder' ),
						'no'  => esc_html__( 'No', 'fusion-builder' ),
					],
				],				
				'encrypt_register_password_in_form_data' => [
					'type'        => 'radio-buttonset',
					'label'       => esc_html__( 'Encrypt Password', 'fusion-builder' ),
					'description' => esc_html__( 'If set to "yes", the password will be encrypted in any other form data transmission, like saving to database or a notification email.', 'fusion-builder' ),
					'id'          => 'encrypt_register_password_in_form_data',
					'default'     => 'no',
					'choices'     => [
						'yes' => esc_html__( 'Yes', 'fusion-builder' ),
						'no'  => esc_html__( 'No', 'fusion-builder' ),
					],
				],
				'auto_create_password' => [
					'type'        => 'radio-buttonset',
					'label'       => esc_html__( 'Auto Create Password', 'fusion-builder' ),
					'description' => esc_html__( 'If set to "yes" and no password is specified in the form, the password will be auto-created with respect to the minimum length option.', 'fusion-builder' ),
					'id'          => 'auto_create_password',
					'default'     => 'no',
					'choices'     => [
						'yes' => esc_html__( 'Yes', 'fusion-builder' ),
						'no'  => esc_html__( 'No', 'fusion-builder' ),
					],
				],				
				'auto_login'     => [
					'type'        => 'radio-buttonset',
					'label'       => esc_html__( 'Log In User', 'fusion-builder' ),
					'description' => esc_html__( 'Set to "yes" to log in the user after successful registration.', 'fusion-builder' ),
					'id'          => 'auto_login',
					'default'     => 'no',
					'choices'     => [
						'yes' => esc_html__( 'Yes', 'fusion-builder' ),
						'no'  => esc_html__( 'No', 'fusion-builder' ),
					],
				],
				'send_wp_notification' => [
					'type'        => 'radio-buttonset',
					'label'       => esc_html__( 'Send WordPress Notification', 'fusion-builder' ),
					'description' => esc_html__( 'Send WordPress new user notification, by triggering the "register_new_user" action.', 'fusion-builder' ),
					'id'          => 'send_wp_notification',
					'default'     => 'yes',
					'choices'     => [
						'yes' => esc_html__( 'Yes', 'fusion-builder' ),
						'no'  => esc_html__( 'No', 'fusion-builder' ),
					],
				],				
				'register_display_notices'     => [
					'type'        => 'radio-buttonset',
					'label'       => esc_html__( 'Display Notices', 'fusion-builder' ),
					'description' => esc_html__( 'Set to "yes" to add additional internal notices to the Notice element.', 'fusion-builder' ),
					'id'          => 'register_display_notices',
					'default'     => 'no',
					'choices'     => [
						'yes' => esc_html__( 'Yes', 'fusion-builder' ),
						'no'  => esc_html__( 'No', 'fusion-builder' ),
					],
				],				
			],
		];

		$sections['form_submission']['fields']['lost_password_option'] = [
			'type'       => 'toggle',
			'row_title'  => esc_html__( 'Lost Password', 'fusion-builder' ),
			'id'         => 'lost_password_option',
			'dependency' => [
				[
					'field'      => 'form_type',
					'value'      => 'ajax',
					'comparison' => '==',
				],
				[
					'field'      => 'form_actions',
					'value'      => 'lost_password',
					'comparison' => 'contains',
				],
			],
			'fields'     => [
				'lost_password_map'           => [
					'type'        => 'auth_map',
					'label'       => esc_html__( 'Form Field Mapping', 'fusion-builder' ),
					'description' => sprintf( esc_html__( 'Map fields from the form to the WordPress lost password field. To customize the email with the password reset instructions, use the %s filter.', 'fusion-builder' ), '<a href="https://developer.wordpress.org/reference/hooks/retrieve_password_message/" target="_blank">retrieve_password_message</a>'
					 ),
					'id'          => 'lost_password_map',
					'transport'   => 'postMessage',
				],
				'lost_password_display_notices' => [
					'type'        => 'radio-buttonset',
					'label'       => esc_html__( 'Display Notices', 'fusion-builder' ),
					'description' => esc_html__( 'Set to "yes" to add additional internal notices to the Notice element.', 'fusion-builder' ),
					'id'          => 'lost_password_display_notices',
					'default'     => 'no',
					'choices'     => [
						'yes' => esc_html__( 'Yes', 'fusion-builder' ),
						'no'  => esc_html__( 'No', 'fusion-builder' ),
					],
				],
			],
		];

		$sections['form_submission']['fields']['reset_password_option'] = [
			'type'       => 'toggle',
			'row_title'  => esc_html__( 'Reset Password', 'fusion-builder' ),
			'id'         => 'reset_password_option',
			'dependency' => [
				[
					'field'      => 'form_type',
					'value'      => 'ajax',
					'comparison' => '==',
				],
				[
					'field'      => 'form_actions',
					'value'      => 'reset_password',
					'comparison' => 'contains',
				],
			],
			'fields'     => [
				'reset_password_map'           => [
					'type'        => 'auth_map',
					'label'       => esc_html__( 'Form Field Mapping', 'fusion-builder' ),
					'description' => esc_html__( 'Map fields from the form to the WordPress reset password field.', 'fusion-builder' ),
					'id'          => 'reset_password_map',
					'transport'   => 'postMessage',
				],
				'encrypt_reset_password_in_form_data' => [
					'type'        => 'radio-buttonset',
					'label'       => esc_html__( 'Encrypt Password', 'fusion-builder' ),
					'description' => esc_html__( 'If set to "yes", the password will be encrypted in any other form data transmission, like saving to database or a notification email.', 'fusion-builder' ),
					'id'          => 'encrypt_reset_password_in_form_data',
					'default'     => 'no',
					'choices'     => [
						'yes' => esc_html__( 'Yes', 'fusion-builder' ),
						'no'  => esc_html__( 'No', 'fusion-builder' ),
					],
				],
				'reset_password_display_notices' => [
					'type'        => 'radio-buttonset',
					'label'       => esc_html__( 'Display Notices', 'fusion-builder' ),
					'description' => esc_html__( 'Set to "yes" to add additional internal notices to the Notice element.', 'fusion-builder' ),
					'id'          => 'reset_password_display_notices',
					'default'     => 'no',
					'choices'     => [
						'yes' => esc_html__( 'Yes', 'fusion-builder' ),
						'no'  => esc_html__( 'No', 'fusion-builder' ),
					],
				],
			],
		];		

		return $sections;
	}

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @static
	 * @access public
	 * @since 3.14.1
	 */
	public static function get_instance() {

		// If an instance hasn't been created and set to $instance create an instance and set it to $instance.
		if ( null === self::$instance ) {
			self::$instance = new Fusion_Form_Auth_Actions();
		}
		return self::$instance;
	}
}

/**
 * Instantiates the Fusion_Form_Auth_Actions class.
 * Make sure the class is properly set-up.
 *
 * @since object 3.14.1
 * @return object Fusion_App
 */
function Fusion_Form_Auth_Actions() { // phpcs:ignore WordPress.NamingConventions
	return Fusion_Form_Auth_Actions::get_instance();
}
Fusion_Form_Auth_Actions();
