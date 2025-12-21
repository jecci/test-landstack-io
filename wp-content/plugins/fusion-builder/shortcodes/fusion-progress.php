<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 1.0
 */

if ( fusion_is_element_enabled( 'fusion_progress' ) ) {

	if ( ! class_exists( 'FusionSC_Progressbar' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 1.0
		 */
		class FusionSC_Progressbar extends Fusion_Element {

			/**
			 * The counter.
			 *
			 * @access private
			 * @since 3.6.1
			 * @var int
			 */
			private $element_counter = 1;

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 1.0
			 */
			public function __construct() {
				parent::__construct();
				add_filter( 'fusion_attr_progressbar-shortcode', [ $this, 'attr' ] );
				add_filter( 'fusion_attr_progressbar-shortcode-bar', [ $this, 'bar_attr' ] );
				add_filter( 'fusion_attr_progressbar-shortcode-content', [ $this, 'content_attr' ] );
				add_filter( 'fusion_attr_fusion-progressbar-text', [ $this, 'text_attr' ] );
				add_filter( 'fusion_attr_progressbar-shortcode-active-value-wrap', [ $this, 'active_value_wrap' ] );

				add_filter( 'fusion_attr_progressbar-shortcode-texts-wrap', [ $this, 'texts_wrap' ] );
				add_filter( 'fusion_attr_progressbar-shortcode-max-value-wrap', [ $this, 'max_value_wrap' ] );

				add_shortcode( 'fusion_progress', [ $this, 'render' ] );
			}

			/**
			 * Gets the default values.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @return array
			 */
			public static function get_element_defaults() {
				$fusion_settings = awb_get_fusion_settings();

				return [
					'margin_top'                             => '',
					'margin_right'                           => '',
					'margin_bottom'                          => '',
					'margin_left'                            => '',
					'hide_on_mobile'                         => fusion_builder_default_visibility( 'string' ),
					'class'                                  => '',
					'id'                                     => '',
					'animated_stripes'                       => 'no',
					'filledcolor'                            => '',
					'fully_filled_color'                     => '',
					'height'                                 => '',
					'percentage'                             => '70',
					'show_percentage'                        => 'yes',
					'unit'                                   => '',
					'active_value_unit_position'             => 'after',
					'value_display_type'                     => 'percentage',
					'stock_mode'                             => 'no',
					'maximum_value'                          => '100',
					'maximum_value_text'                     => '',
					'display_maximum_value'                  => 'no',
					'maximum_value_text_position'            => $fusion_settings->get( 'progressbar_maximum_value_text_position' ),
					'maximum_value_text_align'               => '',
					'maximum_value_unit'                     => '',
					'maximum_value_unit_position'            => 'after',
					'fusion_font_family_maximum_value_font'  => '',
					'fusion_font_variant_maximum_value_font' => '',
					'maximum_value_font_size'                => '',
					'maximum_value_line_height'              => '',
					'maximum_value_letter_spacing'           => '',
					'maximum_value_text_transform'           => '',
					'maximum_value_color'                    => '',
					'filling_speed'                          => '600',
					'striped'                                => 'no',
					'text_position'                          => $fusion_settings->get( 'progressbar_text_position' ),
					'text_align'                             => '',
					'unfilledcolor'                          => '',
					'fusion_font_family_text_font'           => '',
					'fusion_font_variant_text_font'          => '',
					'textcolor'                              => '',
					'text_font_size'                         => '',
					'text_line_height'                       => '',
					'text_letter_spacing'                    => '',
					'text_text_transform'                    => '',
					'filledbordercolor'                      => '',
					'filledbordersize'                       => '',
					'border_radius_top_left'                 => '',
					'border_radius_top_right'                => '',
					'border_radius_bottom_right'             => '',
					'border_radius_bottom_left'              => '',

					// Used to force text align on form navigation progress bar.
					'force_text_align'                       => 'false',
				];
			}

			/**
			 * Maps settings to param variables.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @return array
			 */
			public static function settings_to_params() {
				return [
					'progressbar_filled_color'                => 'filledcolor',
					'progressbar_fully_filled_color'          => 'fully_filled_color',
					'progressbar_height'                      => 'height',
					'progressbar_text_color'                  => 'textcolor',
					'progressbar_text_position'               => 'text_position',
					'progressbar_maximum_value_text_position' => 'maximum_value_text_position',
					'progressbar_maximum_value_color'         => 'maximum_value_color',
					'progressbar_unfilled_color'              => 'unfilledcolor',
					'progressbar_filled_border_color'         => 'filledbordercolor',
					'progressbar_filled_border_size'          => 'filledbordersize',
				];
			}

			/**
			 * Used to set any other variables for use on front-end editor template.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @return array
			 */
			public static function get_element_extras() {
				return [
					'woo_decimal_separator'  => function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : '.',
					'woo_thousand_separator' => function_exists( 'wc_get_price_thousand_separator' ) ? wc_get_price_thousand_separator() : ',',
					'is_rtl'                 => is_rtl(),

				];
			}

			/**
			 * Render the shortcode
			 *
			 * @access public
			 * @since 1.0
			 * @param  array  $args    Shortcode parameters.
			 * @param  string $content Content between shortcode.
			 * @return string          HTML output.
			 */
			public function render( $args, $content = '' ) {
				$this->defaults = self::get_element_defaults();
				$defaults       = FusionBuilder::set_shortcode_defaults( $this->defaults, $args, 'fusion_progress' );
				$content        = apply_filters( 'fusion_shortcode_content', $content, 'fusion_progress', $args );

				$defaults['filledbordersize'] = FusionBuilder::validate_shortcode_attr_value( $defaults['filledbordersize'], 'px' );

				$this->args = $defaults;

				$this->args['margin_bottom'] = FusionBuilder::validate_shortcode_attr_value( $this->args['margin_bottom'], 'px' );
				$this->args['margin_left']   = FusionBuilder::validate_shortcode_attr_value( $this->args['margin_left'], 'px' );
				$this->args['margin_right']  = FusionBuilder::validate_shortcode_attr_value( $this->args['margin_right'], 'px' );
				$this->args['margin_top']    = FusionBuilder::validate_shortcode_attr_value( $this->args['margin_top'], 'px' );

				// Active value.
				$text           = '<span ' . FusionBuilder::attributes( 'fusion-progressbar-text' ) . '>' . wp_kses_post( $content ). '</span>';
				$value          = 'percentage' === $this->args['value_display_type'] ? $this->sanitize_percentage( $this->args['percentage'] ) : $this->convert_html_string_to_float( $this->args['percentage'] );
				$value_unit     = 'before' === $this->args['active_value_unit_position'] ? $this->args['unit'] . $value : $value . $this->args['unit'];
				$value_wrap     = 'yes' === $this->args['show_percentage'] ? ' <span ' . FusionBuilder::attributes( 'fusion-progressbar-value' ) . '>' . $value_unit . '</span>' : '';
				$text_wrap_node = 'on_bar' === $this->args['text_position'] ? 'span' : 'div'; // Div is needed for not on bar layouts, span for str_replace on bar.
				$text_wrapper   = '<' . esc_attr( $text_wrap_node ) . ' ' . FusionBuilder::attributes( 'progressbar-shortcode-active-value-wrap' ) . '>' . $text . $value_wrap . '</' . esc_attr( $text_wrap_node ) . '>';

				// Maximum value.
				$maximum_value_text         = '<span class="awb-pb-max-value-text">' . wp_kses_post( $this->args['maximum_value_text'] ) . '</span>';
				$maximum_value              = 'percentage' === $this->args['value_display_type'] ? '100' : $this->convert_html_string_to_float( $this->args['maximum_value'] );
				$maximum_value_unit         = 'before' === $this->args['maximum_value_unit_position'] ? esc_html( $this->args['maximum_value_unit'] ) . esc_html( $maximum_value ) : esc_html( $maximum_value ) . esc_html( $this->args['maximum_value_unit'] );
				$maximum_value_wrap         = 'yes' === $this->args['display_maximum_value'] ? ' <span class="awb-pb-max-value">' . $maximum_value_unit . '</span>' : '';
				$maximum_value_wrapper_node = 'on_bar' === $this->args['maximum_value_text_position'] ? 'span' : 'div'; // Div is needed for not on bar layouts, span for str_replace on bar.
				$maximum_value_text_wrapper = '<' . esc_attr( $maximum_value_wrapper_node ) . ' ' . FusionBuilder::attributes( 'progressbar-shortcode-max-value-wrap' ) . '>' . $maximum_value_text . $maximum_value_wrap . '</' . esc_attr( $maximum_value_wrapper_node ) . '>';

				// Progress bar.
				$bar = '<div ' . FusionBuilder::attributes( 'progressbar-shortcode-bar' ) . '><div ' . FusionBuilder::attributes( 'progressbar-shortcode-content' ) . '></div></div>';

				// If activae value and maximum value are both either above or below the bar, put them in a wrapper together.
				if ( $this->args['maximum_value_text_position'] === $this->args['text_position'] && 'on_bar' !== $this->args['text_position'] ) {
					$text_wrapper               = '<span ' . FusionBuilder::attributes( 'progressbar-shortcode-texts-wrap' ) . '>' . $text_wrapper . $maximum_value_text_wrapper . '</span>';
					$maximum_value_text_wrapper = '';
				}

				// Active value is above bar.
				if ( 'above_bar' === $this->args['text_position'] ) {
					$html = $text_wrapper;
					
					// Maximum value is above bar.
					if ( 'above_bar' === $this->args['maximum_value_text_position'] ) {
						$html .= $maximum_value_text_wrapper . $bar;
					} else if ( 'on_bar' === $this->args['maximum_value_text_position'] ) {

						// Maximum value is on bar.
						$html .= str_replace( '</div></div>', '</div>' . $maximum_value_text_wrapper . '</div>', $bar );
					} else {

						// Maximum value is below bar.
						$html .= $bar . $maximum_value_text_wrapper;
					}
				} else {

					// Active value is on bar.
					if ( 'on_bar' === $this->args['text_position'] ) {
						$html = str_replace( '</div></div>', $text_wrapper . '</div></div>', $bar );
					} else {

						// Active value is below bar.
						$html = $bar . $text_wrapper;
					}

					// Maximum value is above bar.
					if ( 'above_bar' === $this->args['maximum_value_text_position'] ) {
						$html = $maximum_value_text_wrapper . $html;
					} else if ( 'on_bar' === $this->args['maximum_value_text_position'] ) {

						// Maximum value is on bar.
						$html = str_replace( '</div></div>', '</div>' . $maximum_value_text_wrapper . '</div>', $html );
					} else {

						// Maximum value is below bar.
						$html .= $maximum_value_text_wrapper;
					}
				}

				$html = '<div ' . FusionBuilder::attributes( 'progressbar-shortcode' ) . '>' . $html . '</div>';

				$this->on_render();

				$this->element_counter++;

				return apply_filters( 'fusion_element_progress_content', $html, $args );
			}

			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function attr() {

				$attr = [
					'class' => 'fusion-progressbar',
					'style' => $this->get_inline_style(),
				];

				// Dynamic data is being used to retrieve the free shipping minimum amount percentage. Set a class to live update.
				// Data attributes are set to determine which value needs to be updated how.
				if ( false !== strpos( $this->args['percentage'], 'awb-dynamic-free-shipping-min-amount' ) || false !== strpos( $this->args['percentage'], 'fusion-dynamic-cart-total-wrapper' ) ) {
					$attr['class']            .= ' awb-dynamic-free-shipping-min-amount-progress';
					if ( false !== strpos( $this->args['percentage'], 'awb-dynamic-free-shipping-min-amount' ) ) {
						$attr['data-value-type'] = 'min-amount-percentage';
					} else if ( false !== strpos( $this->args['percentage'], 'fusion-dynamic-cart-sub-totals-discounts' ) ) {
						$attr['data-value-type'] = 'sub-totals-discounts';
					} else {
						$attr['data-value-type'] = 'sub-totals';
					}
					$attr['data-display-type'] = $this->args['value_display_type'];
				}

				$attr = fusion_builder_visibility_atts( $this->args['hide_on_mobile'], $attr );

				if ( $this->args['class'] ) {
					$attr['class'] .= ' ' . $this->args['class'];
				}

				if ( $this->args['id'] ) {
					$attr['id'] = $this->args['id'];
				}

				return $attr;
			}

			/**
			 * Builds the bar attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function bar_attr() {

				$attr = [
					'style' => '',
					'class' => 'fusion-progressbar-bar progress-bar',
				];

				if ( 'yes' === $this->args['striped'] ) {
					$attr['class'] .= ' progress-striped';
				}

				if ( 'yes' === $this->args['animated_stripes'] ) {
					$attr['class'] .= ' active';
				}

				return $attr;
			}

			/**
			 * Builds the content attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function content_attr() {
				$attr = [
					'class' => 'progress progress-bar-content not-animated',
				];

				$attr['role']               = 'progressbar';
				$attr['aria-labelledby']    = 'awb-progressbar-label-' . $this->element_counter;
				$attr['aria-valuemin']      = '0';
				$attr['aria-valuemax']      = '100';
				$attr['aria-valuenow']      = $this->sanitize_percentage( $this->args['percentage'] );
				$attr['data-filling-speed'] = $this->args['filling_speed'];

				return $attr;
			}

			/**
			 * Builds the active value wrap attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function active_value_wrap() {
				$attr = [
					'class' => 'progress-title',
				];

				$attr['class'] .= ' awb-' . str_replace( '_', '-', $this->args['text_position'] );

				if ( 'on_bar' !== $this->args['text_position'] ) {
					switch ( $this->args['text_align'] ) {
						case 'center':
							$attr['class'] .= ' awb-align-center';
							break;
						case 'left':
						case '':
							$attr['class'] .= is_rtl() ? ' awb-align-end' : ' awb-align-start';
							break;
						case 'right':
							$attr['class'] .= is_rtl() ? ' awb-align-start' : ' awb-align-end';
							break;
					}
				}

				return $attr;
			}

			/**
			 * Builds the text attributes array.
			 *
			 * @access public
			 * @since 3.6.1
			 * @return array
			 */
			public function text_attr() {
				$attr = [
					'class' => 'fusion-progressbar-text',
					'id'    => 'awb-progressbar-label-' . $this->element_counter,
				];

				return $attr;
			}

			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 3.14.1
			 * @return array
			 */
			public function texts_wrap() {
				$attr = [
					'class' => 'awb-pb-texts-wrap',
				];

				// Make sure the active value alignment is correct, if it is set to text flow.
				if ( '' === $this->args['text_align'] ) {
					if ( is_rtl() ) {
						$active_value_align = 'right';
					} else {
						$active_value_align = 'left';
					}
				} else {
					$active_value_align = $this->args['text_align'];
				}

				// Make sure the maximum value alignment is correct, if it is set to text flow.
				if ( '' === $this->args['maximum_value_text_align'] ) {
					if ( is_rtl() ) {
						$maximum_value_align = 'right';
					} else {
						$maximum_value_align = 'left';
					}
				} else {
					$maximum_value_align = $this->args['maximum_value_text_align'];
				}
				
				// If active and maximum value are displayed on opposite ends, we can stay within one row.
				if ( ( 'left' === $active_value_align && 'right' === $maximum_value_align ) || ( 'right' === $active_value_align && 'left' === $maximum_value_align ) ) {
					$attr['class'] .= ' awb-space-between';

					// Make sure we cover the left/right aspect correctly.
					if ( ( 'left' === $active_value_align && 'right' === $maximum_value_align && is_rtl() ) || ( 'right' === $active_value_align && 'left' === $maximum_value_align && ! is_rtl() ) ) {
						$attr['class'] .= ' awb-flow-row-reverse';
					}
				} else {
					$attr['class'] .= ' awb-direction-column';
				}

				return $attr;
			}

			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 3.14.1
			 * @return array
			 */
			public function max_value_wrap() {
				$attr = [
					'class' => 'awb-pb-max-value-wrap',
				];

				$attr['class'] .= ' awb-' . str_replace( '_', '-', $this->args['maximum_value_text_position'] );

				if ( 'on_bar' !== $this->args['maximum_value_text_position'] ) {
					switch ( $this->args['maximum_value_text_align'] ) {
						case 'center':
							$attr['class'] .= ' awb-align-center';
							break;
						case 'left':
						case '':
							$attr['class'] .= is_rtl() ? ' awb-align-end' : ' awb-align-start';
							break;
						case 'right':
							$attr['class'] .= is_rtl() ? ' awb-align-start' : ' awb-align-end';
							break;
					}
				}

				return $attr;
			}

			/**
			 * Get the inline style for element.
			 *
			 * @since 3.9
			 * @return string
			 */
			public function get_inline_style() {
				$sanitize         = fusion_library()->sanitize;
				$css_vars_options = [
					'margin_top'                    => [ 'callback' => [ $sanitize, 'get_value_with_unit' ] ],
					'margin_right'                  => [ 'callback' => [ $sanitize, 'get_value_with_unit' ] ],
					'margin_bottom'                 => [ 'callback' => [ $sanitize, 'get_value_with_unit' ] ],
					'margin_left'                   => [ 'callback' => [ $sanitize, 'get_value_with_unit' ] ],

					'text_font_size'                => [ 'callback' => [ $sanitize, 'get_value_with_unit' ] ],
					'text_letter_spacing'           => [ 'callback' => [ $sanitize, 'get_value_with_unit' ] ],
					'text_line_height',
					'text_text_transform',
					'textcolor',
					'text_align',

					'maximum_value_font_size'       => [ 'callback' => [ $sanitize, 'get_value_with_unit' ] ],
					'maximum_value_line_height',
					'maximum_value_letter_spacing'  => [ 'callback' => [ $sanitize, 'get_value_with_unit' ] ],
					'maximum_value_text_transform',
					'maximum_value_color',
					'maximum_value_text_align',

					'filling_speed'                 => [ 'callback' => [ $this, 'sanitize_filling_speed' ] ],

					'height',
					'filledcolor',
					'fully_filled_color',
					'unfilledcolor',

					'border_radius_top_left',
					'border_radius_top_right',
					'border_radius_bottom_left',
					'border_radius_bottom_right',

					'filledbordersize',
					'filledbordercolor',
				];

				// Need to set the remaining width percentage on the maximum value wrapper, if it is on bar.
				$custom_vars['maximum_value_wrapper_width'] = ( 100 - $this->sanitize_percentage( $this->args['percentage'] ) ) . '%';

				// Active value font.
				$typography_active_value = Fusion_Builder_Element_Helper::get_font_styling( $this->args, 'text_font', 'array' );
				foreach ( $typography_active_value as $rule => $value ) {
					$custom_vars[ $rule ] = $value;
				}

				// Maximum value font.
				$typography_maximum_value = Fusion_Builder_Element_Helper::get_font_styling( $this->args, 'maximum_value_font', 'array' );			
				foreach ( $typography_maximum_value as $rule => $value ) {
					$custom_vars[ 'maximum-value-' . $rule ] = $value;
				}

				return $this->get_css_vars_for_options( $css_vars_options ) . $this->get_custom_css_vars( $custom_vars );
			}

			/**
			 * Helper method to sanitize filling_speed arg.
			 *
			 * @since 3.14.1
			 * @param string $value The value to sanitize.
			 * @return string
			 */
			public function sanitize_filling_speed( $value ) {
				return '' !== $value ? $value . 'ms' : $value;
			}

			/**
			 * Sanitize the percentage value, because this can come also from a
			 * dynamic data which can be a string or a float.
			 *
			 * @since 3.6
			 * @param float|string $active_value The active value we want to change into percentage.
			 * @return float
			 */
			protected function sanitize_percentage( $active_value ) {
				$active_value  = $this->convert_html_string_to_float( $active_value );
				$maximum_value = $this->convert_html_string_to_float( $this->args['maximum_value'] );

				if ( ! $active_value ) {
					return 0;
				}

				// No maximum value is set, so this is 100%.
				if ( ! $maximum_value ) {
					return 100;
				}

				// Stock management calc.
				$maximum_value = 'yes' === $this->args['stock_mode'] ? $maximum_value + $active_value : $maximum_value;

				$percentage = $active_value / $maximum_value * 100;
				$percentage = round( floatval( $percentage ), 0 );

				if ( 0 > $percentage ) {
					$percentage = 0;
				}

				if ( 100 < $percentage ) {
					$percentage = 100;
				}

				return $percentage;
			}

			/**
			 * Convert an HTML string value to a float.
			 *
			 * @since 3.14.1
			 * @param float|string $value The value that is wrapped in an HTML string.
			 * @return float
			 */
			protected function convert_html_string_to_float( $value ) {
				if ( ! is_numeric( $value ) ) {
					$is_woocommerce_price = false !== strpos( $value, 'woocommerce-Price' );
					$value                = html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES, 'UTF-8' );
					
					if ( $is_woocommerce_price ) {
						$decimal_separator  = function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : '.';
						$thousand_separator = function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : '.';
						$pattern            = sprintf( '/\d{1,3}(?:%s\d{3})*(?:%s\d+)?/', preg_quote( $thousand_separator, '/' ), preg_quote( $decimal_separator, '/' ) );

						if ( preg_match( $pattern, $value, $matches ) ) {
							$value = $matches[0];
							$value = '' !== $thousand_separator ? str_replace( $thousand_separator, '', $value ) : $value;
							$value = '.' !== $decimal_separator ? str_replace( $decimal_separator, '.', $value ) : $value;
						}
					} else {
						$pattern = '/\d+(?:\.\d+)?/';
						
						if ( preg_match( $pattern, $value, $matches ) ) {
							$value = $matches[0];
						}
					}
				}
		
				$value = (float) $value;

				if ( ! $value ) {
					return 0;
				}

				return $value;
			}

			/**
			 * Builds the dynamic styling.
			 *
			 * @access protected
			 * @since 1.1
			 * @return array
			 */
			protected function add_styling() {
				global $content_media_query, $six_fourty_media_query, $three_twenty_six_fourty_media_query, $ipad_portrait_media_query, $dynamic_css_helpers;

				$css[ $content_media_query ]['.fusion-progressbar']['margin-bottom']                 = '10px !important';
				$css[ $six_fourty_media_query ]['.fusion-progressbar']['margin-bottom']              = '10px !important';
				$css[ $three_twenty_six_fourty_media_query ]['.fusion-progressbar']['margin-bottom'] = '10px !important';
				$css[ $ipad_portrait_media_query ]['.fusion-progressbar']['margin-bottom']           = '10px !important';

				return $css;
			}

			/**
			 * Adds settings to element options panel.
			 *
			 * @access protected
			 * @since 1.1
			 * @return array $sections Progress Bar settings.
			 */
			protected function add_options() {

				return [
					'progress_shortcode_section' => [
						'label'       => esc_html__( 'Progress Bar', 'fusion-builder' ),
						'description' => '',
						'id'          => 'progress_shortcode_section',
						'type'        => 'accordion',
						'icon'        => 'fusiona-tasks',
						'fields'      => [
							'progressbar_text_position'  => [
								'label'       => esc_html__( 'Progress Bar Active Value Position', 'fusion-builder' ),
								'description' => esc_html__( 'Select the position of the progress bar active value text.', 'fusion-builder' ),
								'id'          => 'progressbar_text_position',
								'default'     => 'on_bar',
								'type'        => 'radio-buttonset',
								'transport'   => 'postMessage',
								'choices'     => [
									'on_bar'    => esc_html__( 'On Bar', 'fusion-builder' ),
									'above_bar' => esc_html__( 'Above Bar', 'fusion-builder' ),
									'below_bar' => esc_html__( 'Below Bar', 'fusion-builder' ),
								],
							],
							'progressbar_text_color'     => [
								'label'       => esc_html__( 'Progress Bar Active Value Color', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the color of the progress bar active value text.', 'fusion-builder' ),
								'id'          => 'progressbar_text_color',
								'default'     => 'var(--awb-color1)',
								'type'        => 'color-alpha',
								'transport'   => 'postMessage',
								'css_vars'    => [
									[
										'name'     => '--progressbar_text_color',
										'callback' => [ 'sanitize_color' ],
									],
								],
							],
							'progressbar_maximum_value_text_position'  => [
								'label'       => esc_html__( 'Progress Bar Maximum Value Position', 'fusion-builder' ),
								'description' => esc_html__( 'Select the position of the progress bar maximum value text.', 'fusion-builder' ),
								'id'          => 'progressbar_maximum_value_text_position',
								'default'     => 'on_bar',
								'type'        => 'radio-buttonset',
								'transport'   => 'postMessage',
								'choices'     => [
									'on_bar'    => esc_html__( 'On Bar', 'fusion-builder' ),
									'above_bar' => esc_html__( 'Above Bar', 'fusion-builder' ),
									'below_bar' => esc_html__( 'Below Bar', 'fusion-builder' ),
								],
							],
							'progressbar_maximum_value_color'     => [
								'label'       => esc_html__( 'Progress Bar Maximum Value Color', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the color of the progress bar maximum value text.', 'fusion-builder' ),
								'id'          => 'progressbar_maximum_value_color',
								'default'     => 'var(--awb-color7)',
								'type'        => 'color-alpha',
								'transport'   => 'postMessage',
								'css_vars'    => [
									[
										'name'     => '--progressbar_maximum_value_color',
										'callback' => [ 'sanitize_color' ],
									],
								],
							],
							'progressbar_height'         => [
								'label'       => esc_html__( 'Progress Bar Height', 'fusion-builder' ),
								'description' => esc_html__( 'Insert a height for the progress bar.', 'fusion-builder' ),
								'id'          => 'progressbar_height',
								'default'     => '48px',
								'type'        => 'dimension',
								'transport'   => 'postMessage',
								'css_vars'    => [
									[
										'name' => '--progressbar_height',
									],
								],
							],
							'progressbar_filled_color'   => [
								'label'       => esc_html__( 'Progress Bar Filled Color', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the color of the progress bar filled area.', 'fusion-builder' ),
								'id'          => 'progressbar_filled_color',
								'default'     => 'var(--awb-color5)',
								'type'        => 'color-alpha',
								'transport'   => 'postMessage',
								'css_vars'    => [
									[
										'name'     => '--progressbar_filled_color',
										'callback' => [ 'sanitize_color' ],
									],
								],
							],
							'progressbar_fully_filled_color'   => [
								'label'       => esc_html__( 'Progress Bar 100% Filled Color', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the color of the progress bar when it is fully filled. Leave empty if you want to keep the normal filled color.', 'fusion-builder' ),
								'id'          => 'progressbar_fully_filled_color',
								'default'     => '',
								'type'        => 'color-alpha',
								'transport'   => 'postMessage',
								'css_vars'    => [
									[
										'name'     => '--progressbar_fully_filled_color',
									],
								],
							],
							'progressbar_unfilled_color' => [
								'label'       => esc_html__( 'Progress Bar Unfilled Color', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the color of the progress bar unfilled area.', 'fusion-builder' ),
								'id'          => 'progressbar_unfilled_color',
								'default'     => 'var(--awb-color2)',
								'type'        => 'color-alpha',
								'transport'   => 'postMessage',
								'css_vars'    => [
									[
										'name'     => '--progressbar_unfilled_color',
										'callback' => [ 'sanitize_color' ],
									],
								],
							],
							'progressbar_filled_border_size' => [
								'label'       => esc_html__( 'Progress Bar Filled Border Size', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the border size of the progress bar filled area.', 'fusion-builder' ),
								'id'          => 'progressbar_filled_border_size',
								'default'     => '0',
								'type'        => 'slider',
								'transport'   => 'postMessage',
								'choices'     => [
									'min'  => '0',
									'max'  => '20',
									'step' => '1',
								],
								'css_vars'    => [
									[
										'name'     => '--progressbar_filled_border_size',
										'callback' => [ 'maybe_append_px' ],
									],
								],
							],
							'progressbar_filled_border_color' => [
								'label'       => esc_html__( 'Progress Bar Filled Border Color', 'fusion-builder' ),
								'description' => esc_html__( 'Controls the border color of the progress bar filled area.', 'fusion-builder' ),
								'id'          => 'progressbar_filled_border_color',
								'default'     => 'var(--awb-color1)',
								'type'        => 'color-alpha',
								'transport'   => 'postMessage',
								'css_vars'    => [
									[
										'name'     => '--progressbar_filled_border_color',
										'callback' => [ 'sanitize_color' ],
									],
								],
							],
						],
					],
				];
			}

			/**
			 * Sets the necessary scripts.
			 *
			 * @access protected
			 * @since 3.2
			 * @return void
			 */
			protected function on_first_render() {
				Fusion_Dynamic_JS::enqueue_script(
					'fusion-progress',
					FusionBuilder::$js_folder_url . '/general/fusion-progress.js',
					FusionBuilder::$js_folder_path . '/general/fusion-progress.js',
					[ 'jquery' ],
					FUSION_BUILDER_VERSION,
					true
				);
			}

			/**
			 * Load base CSS.
			 *
			 * @access public
			 * @since 3.0
			 * @return void
			 */
			public function add_css_files() {
				FusionBuilder()->add_element_css( FUSION_BUILDER_PLUGIN_DIR . 'assets/css/shortcodes/progressbar.min.css' );
			}
		}
	}

	new FusionSC_Progressbar();

}

/**
 * Map shortcode to Avada Builder.
 *
 * @since 1.0
 */
function fusion_element_progress() {
	$fusion_settings = awb_get_fusion_settings();

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_Progressbar',
			[
				'name'          => esc_attr__( 'Progress Bar', 'fusion-builder' ),
				'shortcode'     => 'fusion_progress',
				'icon'          => 'fusiona-tasks',
				'preview'       => FUSION_BUILDER_PLUGIN_DIR . 'inc/templates/previews/fusion-progress-preview.php',
				'preview_id'    => 'fusion-builder-block-module-progress-preview-template',
				'help_url'      => 'https://avada.com/documentation/progress-bar-element/',
				'subparam_map'  => [
					'fusion_font_family_text_font'           => 'main_typography',
					'fusion_font_variant_text_font'          => 'main_typography',
					'text_font_size'                         => 'main_typography',
					'text_line_height'                       => 'main_typography',
					'text_letter_spacing'                    => 'main_typography',
					'text_text_transform'                    => 'main_typography',
					'text_color'                             => 'main_typography',

					'fusion_font_family_maximum_value_font'  => 'maximum_value_typography',
					'fusion_font_variant_maximum_value_font' => 'maximum_value_typography',
					'maximum_value_font_size'                => 'maximum_value_typography',
					'maximum_value_line_height'              => 'maximum_value_typography',
					'maximum_value_letter_spacing'           => 'maximum_value_typography',
					'maximum_value_text_transform'           => 'maximum_value_typography',
					'maximum_value_color'                    => 'maximum_value_typography',
				],
				'inline_editor' => true,
				'params'        => [
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Active Value', 'fusion-builder' ),
						'description'  => esc_attr__( 'Use an integer or float here to denote the active value of the progress bar.', 'fusion-builder' ),
						'dynamic_data' => true,
						'param_name'   => 'percentage',
						'value'        => '70',
					],
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Maximum Value', 'fusion-builder' ),
						'description'  => esc_attr__( 'Use an integer or float here to denote the maximum value of the progress bar. The value set here will serve as the 100% measure.', 'fusion-builder' ),
						'dynamic_data' => true,
						'param_name'   => 'maximum_value',
						'value'        => '100',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Value Display Type', 'fusion-builder' ),
						'description' => esc_attr__( 'Select if you want to display the set values or their corresponding percentage value.', 'fusion-builder' ),
						'param_name'  => 'value_display_type',
						'value'       => [
							'percentage' => esc_attr__( 'Percentage', 'fusion-builder' ),
							'value'      => esc_attr__( 'Value', 'fusion-builder' ),
						],
						'default'     => 'percentage',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Stock Mode', 'fusion-builder' ),
						'description' => esc_attr__( 'If set to "yes", the bar will handle active and maximum values as "already sold" and "still available, so the numbers will be added up before the percentage is calculated.', 'fusion-builder' ),
						'param_name'  => 'stock_mode',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
					],					
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Filling Speed', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the fill animation speed. In milliseconds.', 'fusion-builder' ),
						'param_name'  => 'filling_speed',
						'value'       => '600',
						'min'         => '0',
						'max'         => '10000',
						'step'        => '100',
					],						
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Active Value Text', 'fusion-builder' ),
						'description'  => esc_attr__( 'Additional text for the active value.', 'fusion-builder' ),
						'dynamic_data' => true,
						'param_name'   => 'element_content',
						'value'        => esc_attr__( 'Your Content Goes Here', 'fusion-builder' ),
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Display Active Value', 'fusion-builder' ),
						'description' => esc_attr__( 'Select if you want to display the active value.', 'fusion-builder' ),
						'param_name'  => 'show_percentage',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'yes',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Active Value Unit', 'fusion-builder' ),
						'description' => esc_attr__( 'Insert a unit for the active value. ex %.', 'fusion-builder' ),
						'param_name'  => 'unit',
						'value'       => '',
						'dependency'  => [
							[
								'element'  => 'show_percentage',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Active Value Unit Position', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the positioning of the active value unit.', 'fusion-builder' ),
						'param_name'  => 'active_value_unit_position',
						'value'       => [
							'before' => esc_attr__( 'Before Value', 'fusion-builder' ),
							'after'  => esc_attr__( 'After Value', 'fusion-builder' ),
						],
						'default'     => 'after',
						'dependency'  => [
							[
								'element'  => 'show_percentage',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'unit',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Maximum Value Text', 'fusion-builder' ),
						'description'  => esc_attr__( 'Additional text for the maximum value.', 'fusion-builder' ),
						'dynamic_data' => true,
						'param_name'   => 'maximum_value_text',
						'value'        => '',
						'placeholder'  => true,
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Display Maximum Value', 'fusion-builder' ),
						'description' => esc_attr__( 'Select if you want to display the maximum value.', 'fusion-builder' ),
						'param_name'  => 'display_maximum_value',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),

						],
						'default'     => 'no',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Maximum Value Unit', 'fusion-builder' ),
						'description' => esc_attr__( 'Insert a unit for the maximum value. ex %.', 'fusion-builder' ),
						'param_name'  => 'maximum_value_unit',
						'value'       => '',
						'dependency'  => [
							[
								'element'  => 'display_maximum_value',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Maximum Value Unit Position', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the positioning of the maximum value unit.', 'fusion-builder' ),
						'param_name'  => 'maximum_value_unit_position',
						'value'       => [
							'before' => esc_attr__( 'Before Value', 'fusion-builder' ),
							'after'  => esc_attr__( 'After Value', 'fusion-builder' ),
						],
						'default'     => 'after',
						'dependency'  => [
							[
								'element'  => 'display_maximum_value',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'maximum_value_unit',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					'fusion_margin_placeholder' => [
						'param_name' => 'margin',
						'group'      => esc_attr__( 'General', 'fusion-builder' ),
						'value'      => [
							'margin_top'    => '',
							'margin_right'  => '',
							'margin_bottom' => '',
							'margin_left'   => '',
						],
					],
					[
						'type'        => 'checkbox_button_set',
						'heading'     => esc_attr__( 'Element Visibility', 'fusion-builder' ),
						'param_name'  => 'hide_on_mobile',
						'value'       => fusion_builder_visibility_options( 'full' ),
						'default'     => fusion_builder_default_visibility( 'array' ),
						'description' => esc_attr__( 'Choose to show or hide the element on small, medium or large screens. You can choose more than one at a time.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
						'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'class',
						'value'       => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Add an ID to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'id',
						'value'       => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Active Value Text Position', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the position of the active value text. Choose "Default" for Global Options selection.', 'fusion-builder' ),
						'param_name'  => 'text_position',
						'value'       => [
							''          => esc_attr__( 'Default', 'fusion-builder' ),
							'on_bar'    => esc_attr__( 'On Bar', 'fusion-builder' ),
							'above_bar' => esc_attr__( 'Above Bar', 'fusion-builder' ),
							'below_bar' => esc_attr__( 'Below Bar', 'fusion-builder' ),
						],
						'default'     => '',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Active Value Text Align', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the alignment of the active value text.', 'fusion-builder' ),
						'param_name'  => 'text_align',
						'value'       => [
							''       => esc_attr__( 'Text Flow', 'fusion-builder' ),
							'left'   => esc_attr__( 'Left', 'fusion-builder' ),
							'center' => esc_attr__( 'Center', 'fusion-builder' ),
							'right'  => esc_attr__( 'Right', 'fusion-builder' ),
						],
						'default'     => '',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Maximum Value Text Position', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the position of the maximum value text. Choose "Default" for Global Options selection.', 'fusion-builder' ),
						'param_name'  => 'maximum_value_text_position',
						'value'       => [
							''          => esc_attr__( 'Default', 'fusion-builder' ),
							'on_bar'    => esc_attr__( 'On Bar', 'fusion-builder' ),
							'above_bar' => esc_attr__( 'Above Bar', 'fusion-builder' ),
							'below_bar' => esc_attr__( 'Below Bar', 'fusion-builder' ),
						],
						'default'     => '',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Maximum Value Text Align', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the alignment of the maximum value text.', 'fusion-builder' ),
						'param_name'  => 'maximum_value_text_align',
						'value'       => [
							''       => esc_attr__( 'Text Flow', 'fusion-builder' ),
							'left'   => esc_attr__( 'Left', 'fusion-builder' ),
							'center' => esc_attr__( 'Center', 'fusion-builder' ),
							'right'  => esc_attr__( 'Right', 'fusion-builder' ),
						],
						'default'     => '',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
					],
					[
						'type'             => 'typography',
						'heading'          => esc_attr__( 'Active Value Typography', 'fusion-builder' ),
						'description'      => esc_html__( 'Controls the active value typography.', 'fusion-builder' ),
						'param_name'       => 'main_typography',
						'choices'          => [
							'font-family'    => 'text_font',
							'font-size'      => 'text_font_size',
							'line-height'    => 'text_line_height',
							'letter-spacing' => 'text_letter_spacing',
							'text-transform' => 'text_text_transform',
							'color'          => 'textcolor',
						],
						'default'          => [
							'font-family'    => '',
							'variant'        => '400',
							'font-size'      => '',
							'line-height'    => '',
							'letter-spacing' => '',
							'text-transform' => '',
							'color'          => $fusion_settings->get( 'progressbar_text_color' ),
						],
						'remove_from_atts' => true,
						'global'           => true,
						'group'            => esc_attr__( 'Design', 'fusion-builder' ),
					],
					[
						'type'             => 'typography',
						'heading'          => esc_attr__( 'Maximum Value Typography', 'fusion-builder' ),
						'description'      => esc_html__( 'Controls the maximum value typography.', 'fusion-builder' ),
						'param_name'       => 'maximum_value_typography',
						'choices'          => [
							'font-family'    => 'maximum_value_font',
							'font-size'      => 'maximum_value_font_size',
							'line-height'    => 'maximum_value_line_height',
							'letter-spacing' => 'maximum_value_letter_spacing',
							'text-transform' => 'maximum_value_text_transform',
							'color'          => 'maximum_value_color',
						],
						'default'          => [
							'font-family'    => '',
							'variant'        => '400',
							'font-size'      => '',
							'line-height'    => '',
							'letter-spacing' => '',
							'text-transform' => '',
							'color'          => $fusion_settings->get( 'progressbar_maximum_value_text_color' ),
						],
						'remove_from_atts' => true,
						'global'           => true,
						'group'            => esc_attr__( 'Design', 'fusion-builder' ),
					],					
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Striped Filling', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to get the filled area striped.', 'fusion-builder' ),
						'param_name'  => 'striped',
						'value'       => [
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
						],
						'default'     => 'no',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
					],				
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Animated Stripes', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to get the the stripes animated.', 'fusion-builder' ),
						'param_name'  => 'animated_stripes',
						'value'       => [
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
						],
						'default'     => 'no',
						'dependency'  => [
							[
								'element'  => 'striped',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Progress Bar Height', 'fusion-builder' ),
						'description'      => esc_attr__( 'Insert a height for the progress bar. Enter value including any valid CSS unit, ex: 10px.', 'fusion-builder' ),
						'param_name'       => 'dimensions',
						'value'            => [
							'height' => '',
						],
						'group'            => esc_attr__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Filled Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the filled in area. ', 'fusion-builder' ),
						'param_name'  => 'filledcolor',
						'value'       => '',
						'default'     => $fusion_settings->get( 'progressbar_filled_color' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( '100% Filled Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the progress bar when fully filled.', 'fusion-builder' ),
						'param_name'  => 'fully_filled_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'progressbar_fully_filled_color' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Unfilled Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the unfilled in area. ', 'fusion-builder' ),
						'param_name'  => 'unfilledcolor',
						'value'       => '',
						'default'     => $fusion_settings->get( 'progressbar_unfilled_color' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Border Radius', 'fusion-builder' ),
						'description'      => esc_attr__( 'Enter values including any valid CSS unit, ex: 10px or 10%.', 'fusion-builder' ),
						'param_name'       => 'border_radius',
						'value'            => [
							'border_radius_top_left'     => '',
							'border_radius_top_right'    => '',
							'border_radius_bottom_right' => '',
							'border_radius_bottom_left'  => '',
						],
						'group'            => esc_attr__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Filled Border Size', 'fusion-builder' ),
						'description' => esc_attr__( 'In pixels.', 'fusion-builder' ),
						'param_name'  => 'filledbordersize',
						'value'       => '',
						'min'         => '0',
						'max'         => '20',
						'step'        => '1',
						'default'     => $fusion_settings->get( 'progressbar_filled_border_size' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Filled Border Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border color of the filled in area. ', 'fusion-builder' ),
						'param_name'  => 'filledbordercolor',
						'value'       => '',
						'default'     => $fusion_settings->get( 'progressbar_filled_border_color' ),
						'dependency'  => [
							[
								'element'  => 'filledbordersize',
								'value'    => '0',
								'operator' => '!=',
							],
						],
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
					],
					'fusion_conditional_render_placeholder' => [],
				],
			]
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_progress' );
