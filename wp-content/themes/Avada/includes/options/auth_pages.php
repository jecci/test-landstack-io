<?php
/**
 * Avada Options.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      7.9
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Auth Pages.
 *
 * @since 7.14.1
 * @param array $sections An array of our sections.
 * @return array
 */
function avada_options_section_auth_pages( $sections ) {
	$page_select = [
		'0' => 'WP Default',
	];

	$pages = get_posts( [
        'post_type'        => 'page',
        'post_status'      => 'publish',
        'fields'           => 'ids',
        'posts_per_page'   => -1,
        'orderby'          => 'title',
        'order'            => 'ASC',
		'lang'             => '',
		'suppress_filters' => true,
    ] );

	foreach ( $pages as $page_id ) {
		$page_select[ $page_id ] = get_the_title( $page_id );
	}	

	$sections['auth_pages'] = [
		'label'    => esc_html__( 'Custom Auth Pages', 'Avada' ),
		'id'       => 'heading_custom_auth_pages',
		'priority' => 26,
		'icon'     => 'el-icon-lock',
		'alt_icon' => 'fusiona-af-password',
		'fields'   => [
			'auth_pages_notice' => [
				'id'          => 'auth_pages_notice',
				'label'       => '',
				'description' => '<div class="fusion-redux-important-notice">' . __( '<strong>IMPORTANT NOTE:</strong> When creating custom auth pages, it is essential that you also use Avada Forms to add a working form with the matching authentication action set on the Submissions tab.', 'Avada' ) . '</div>',
				'type'        => 'custom',
			],
			'auth_pages_login_page'     => [
				'label'       => esc_html__( 'Login Page', 'Avada' ),
				'description' => esc_html__( 'Select which page you want as custom login page.', 'Avada' ),
				'id'          => 'auth_pages_login_page',
				'default'     => '0',
				'type'        => 'select',
				'choices'     => $page_select,
			],
			'auth_pages_registration_page' => [
				'label'       => esc_html__( 'Registration Page', 'Avada' ),
				'description' => esc_html__( 'Select which page you want as custom registration page.', 'Avada' ),
				'id'          => 'auth_pages_registration_page',
				'default'     => '0',
				'type'        => 'select',
				'choices'     => $page_select,
			],
			'auth_pages_lost_password_page' => [
				'label'       => esc_html__( 'Lost Password Page', 'Avada' ),
				'description' => esc_html__( 'Select which page you want as custom lost password page.', 'Avada' ),
				'id'          => 'auth_pages_lost_password_page',
				'default'     => '0',
				'type'        => 'select',
				'choices'     => $page_select,
			],
			'auth_pages_reset_password_page' => [
				'label'       => esc_html__( 'Reset Password Page', 'Avada' ),
				'description' => esc_html__( 'Select which page you want as custom reset password page.', 'Avada' ),
				'id'          => 'auth_pages_reset_password_page',
				'default'     => '0',
				'type'        => 'select',
				'choices'     => $page_select,
			],
			'auth_pages_custom_redirect' => [
				'label'       => esc_html__( 'WordPress Authentication Pages Redirect', 'Avada' ),
				'description' => esc_html__( 'Choose what should happen if a site user visits a default WordPress authentication page/URL (wp-login.php).', 'Avada' ),
				'id'          => 'auth_pages_custom_redirect',
				'default'     => 'auth_pages',
				'type'        => 'select',
				'choices'     => [
					'auth_pages'  => esc_html__( 'Auth Pages', 'Avada' ),
					'homepage'    => esc_html__( 'Homepage', 'Avada' ),
					'404'         => esc_html__( '404 Page', 'Avada' ),
					'custom_page' => esc_html__( 'Custom Page', 'Avada' ),
				],
			],
			'auth_pages_custom_redirect_page' => [
				'label'       => esc_html__( 'Custom Redirect Page', 'Avada' ),
				'description' => esc_html__( 'Select to which page you want to redirect site users visiting a default WordPress authentication page/URL (wp-login.php).', 'Avada' ),
				'id'          => 'auth_pages_custom_redirect_page',
				'default'     => '0',
				'type'        => 'select',
				'choices'     => $page_select,
				'required'    => [
					[
						'setting'  => 'auth_pages_custom_redirect',
						'operator' => '=',
						'value'    => 'custom_page',
					],
				],
			],
			'auth_pages_bypass_param' => [
				'label'           => esc_html__( 'Custom Authentication Page Bypass', 'Avada' ),
				'description'     => esc_html__( 'Set a value here, if you want to use that as a query var for bypassing the custom authentication pages. E.g. my-secret-login (https://example.com/wp-login.php?my-secret-login=1). The default login will be valid for 5 minutes, or until successful log in.', 'Avada' ),
				'id'              => 'auth_pages_bypass_param',
				'default'         => '',
				'type'            => 'text',
			],
		],
	];

	return $sections;

}
