<?php
/**
 * AWB Attributes Helper class.
 *
 * @package AWB
 * @since 3.14.1
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * AWB Attributes Helper class.
 *
 * @since 3.14.1
 */
class AWB_Attributes_Helper {

	/**
	 * Class constructor.
	 *
	 * @since 3.14.1
	 * @access public
	 */
	public function __construct() {
	}

	/**
	 * Get attributes params.
	 *
	 * @since 3.14.1
	 * @access public
	 * @param array $args The placeholder arguments.
	 * @return array
	 */
	public static function get_params( $args ) {
		$node_toggle   = isset( $args['node_toggle'] ) ? $args['node_toggle'] : 'no';
		$toggle_option = [];

		if ( 'yes' === $node_toggle ) {
			$toggle_option = [
				'type'        => 'radio_button_set',
				'heading'     => esc_html__( 'Target HTML Node', 'fusion-builder' ),
				'description' => esc_html__( 'When set to "Main Node", the attribute will be added to the most relevant HTML node.', 'fusion-builder' ),
				'param_name'  => 'node_toggle',
				'default'     => 'wrapper',
				'value'       => [
					'wrapper'   => esc_attr__( 'Wrapper', 'fusion-builder' ),
					'main_node' => esc_attr__( 'Main Node', 'fusion-builder' ),
				],
			];
		}

		$params = [
			[
				'type'           => 'repeater',
				'heading'        => esc_html__( 'Attributes', 'fusion-builder' ),
				'description'    => esc_html__( 'Add custom HTML attributes to the element.', 'fusion-builder' ),
				'param_name'     => 'html_attributes',
				'group'          => esc_html__( 'General', 'fusion-builder' ),
				'row_add'        => esc_html__( 'Add Attribute', 'Avada' ),
				'row_title'      => esc_html__( 'Attribute', 'Avada' ),
				'bind_title'     => 'name',
				'skip_empty_row' => true,
				'fields'         => [
					[
						'type'        => 'textfield',
						'heading'     => esc_html__( 'Name', 'fusion-builder' ),
						'description' => esc_html__( 'The name of your attribute.', 'fusion-builder' ),
						'param_name'  => 'name',
						'value'       => '',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_html__( 'Value', 'fusion-builder' ),
						'description' => esc_html__( 'The attribute value.', 'fusion-builder' ),
						'param_name'  => 'value',
						'value'       => '',
					],
					$toggle_option,
				]
			]
		];

		return $params;
	}

	/**
	 * Add attributes.
	 *
	 * @since 3.14.1
	 * @param array   $args   Element arguments.
	 * @param array   $attr   HTML element attributes.
	 * @param string  $context The context flag. 'wrapper' adds the attribute to the outter wrapper, 'main_node' to the most relevant HTML node.
	 * @return array
	 */
	public static function add_attributes( $args = [], $attr = [], $context = 'wrapper' ) {
		if ( ! isset( $args['html_attributes'] ) || empty( $args['html_attributes'] ) ) {
			return $attr;
		}

		$reserved_attributes = [ 'class', 'style' ];
		$attributes          = json_decode( base64_decode( $args['html_attributes'] ), true );
		foreach ( $attributes as $attribute ) {
			if ( ! empty( $attribute['value'] ) ) {
				if ( 
					( 'wrapper' === $context && ( ! isset( $attribute['node_toggle'] ) || '' === $attribute['node_toggle'] || 'wrapper' === $attribute['node_toggle'] ) ) ||
					( 'main_node' === $context && isset( $attribute['node_toggle'] ) && 'main_node' === $attribute['node_toggle'] )
				) {
					if ( in_array( $attribute['name'], $reserved_attributes ) ) {
						$attr[ $attribute['name'] ] .= ' ' . $attribute['value'];
					} else {
						$attr[ $attribute['name'] ] = $attribute['value'];
					}
				}
			}
		}

		return $attr;
	}
}
