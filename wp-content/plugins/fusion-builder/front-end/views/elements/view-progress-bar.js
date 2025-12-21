var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Progress Bar Element View.
		FusionPageBuilder.fusion_progress = FusionPageBuilder.ElementView.extend( {

			/**
			 * Modify template attributes.
			 *
			 * @since 2.0
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {};

				// Validate values.
				this.validateValues( atts.values );

				this.extras                      = atts.extras;

				// Create attribute objects
				attributes.attr                  = this.buildAttr( atts.values );
				attributes.attrBar               = this.buildBarAttr( atts.values );
				attributes.attrActiveValueWrap   = this.buildActiveValueWrapAttr( atts.values, atts.extras );
				attributes.attrEditor            = this.buildInlineEditorAttr( atts.values );
				attributes.attrContent           = this.buildContentAttr( atts.values );

				attributes.attrMaximumValueWrap  = this.buildMaxValueWrapAttr( atts.values, atts.extras );
				attributes.attrTextsWrap         = this.buildTextsWrapAttr( atts.values, atts.extras );


				// Any extras that need passed on.
				attributes.cid                   = this.model.get( 'cid' );
				attributes.values                = atts.values;
				attributes.sanitizedActiveValue  = this.convertHtmlStringToFloat( atts.values.percentage );
				attributes.sanitizedMaximumValue = this.convertHtmlStringToFloat( atts.values.maximum_value );
				attributes.percentage            = this.sanitizePercentage( atts.values.percentage, atts.values.maximum_value, atts.values.stock_mode );

				return attributes;
			},

			/**
			 * Modifies the values.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {void}
			 */
			validateValues: function( values ) {
				values.filledbordersize = _.fusionValidateAttrValue( values.filledbordersize, 'px' );
				values.margin_bottom    = _.fusionValidateAttrValue( values.margin_bottom, 'px' );
				values.margin_left      = _.fusionValidateAttrValue( values.margin_left, 'px' );
				values.margin_right     = _.fusionValidateAttrValue( values.margin_right, 'px' );
				values.margin_top       = _.fusionValidateAttrValue( values.margin_top, 'px' );
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildAttr: function( values ) {
				var attr = _.fusionVisibilityAtts( values.hide_on_mobile, {
					class: 'fusion-progressbar',
					style: this.getInlineStyle( values )
				} );

				if ( '' !== values[ 'class' ] ) {
					attr[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( '' !== values.id ) {
					attr.id = values.id;
				}

				return attr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @return {Object}
			 */
			buildInlineEditorAttr: function() {
				var attr = {
					class: 'fusion-progressbar-text',
					id: 'awb-progressbar-label-' + this.model.get( 'cid' )
				};

				attr = _.fusionInlineEditor( {
					cid: this.model.get( 'cid' ),
					'disable-return': true,
					'disable-extra-spaces': true,
					toolbar: 'simple'
				}, attr );

				return attr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildContentAttr: function( values ) {
				var attr = {
					class: 'progress progress-bar-content not-animated',
					role: 'progressbar'
				};

				attr[ 'aria-labelledby' ]  = 'awb-progressbar-label-' + this.model.get( 'cid' );
				attr[ 'aria-valuemin' ]    = '0';
				attr[ 'aria-valuemax' ]    = '100';
				attr[ 'aria-valuenow' ]    = this.sanitizePercentage( values.percentage, values.maximum_value, values.stock_mode );
				attr['data-filling-speed'] = values.filling_speed;

				return attr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildBarAttr: function( values ) {
				var attr = {
					class: 'fusion-progressbar-bar progress-bar',
					style: ''
				};

				if ( 'yes' === values.striped ) {
					attr[ 'class' ] += ' progress-striped';
				}

				if ( 'yes' === values.animated_stripes ) {
					attr[ 'class' ] += ' active';
				}

				if ( '' !== values[ 'class' ] ) {
					attr[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( '' !== values.id ) {
					attr.id = values.id;
				}

				return attr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildActiveValueWrapAttr: function( values, extras ) {
				var attr = {
					class: 'progress-title',
				};

				attr[ 'class' ] += ' awb-' + values.text_position.replace( '_', '-' );

				if ( 'on_bar' !== values.text_position ) {
					switch ( values.text_align ) {
						case 'center':
							attr[ 'class' ] += ' awb-align-center';
							break;
						case 'left':
						case '':
							attr[ 'class' ] += extras.is_rtl ? ' awb-align-end' : ' awb-align-start';
							break;
						case 'right':
							attr[ 'class' ] += extras.is_rtl ? ' awb-align-start' : ' awb-align-end';
							break;
					}
				}

				return attr;
			},

			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 3.14.1
			 * @return array
			 */
			buildMaxValueWrapAttr: function( values, extras ) {
				var attr = {
					class: 'awb-pb-max-value-wrap',
				};

				attr[ 'class' ] += ' awb-' + values.maximum_value_text_position.replace( '_', '-' );

				if ( 'on_bar' !== values.maximum_value_text_position ) {
					switch ( values.maximum_value_text_align ) {
						case 'center':
							attr[ 'class' ] += ' awb-align-center';
							break;
						case 'left':
						case '':
							attr[ 'class' ] += extras.is_rtl ? ' awb-align-end' : ' awb-align-start';
							break;
						case 'right':
							attr[ 'class' ] += extras.is_rtl ? ' awb-align-start' : ' awb-align-end';
							break;
					}
				}

				return attr;
			},

			buildTextsWrapAttr: function( values, extras ) {
				var attr = {
					class: 'awb-pb-texts-wrap',
				},
				activeValueAlign,
				maximumValueAlign;

				// Make sure the active value alignment is correct, if it is set to text flow.
				if ( '' === values.text_align ) {
					if ( extras.is_rtl ) {
						activeValueAlign = 'right';
					} else {
						activeValueAlign = 'left';
					}
				} else {
					activeValueAlign = values.text_align;
				}

				// Make sure the maximum value alignment is correct, if it is set to text flow.
				if ( '' === values.maximum_value_text_align ) {
					if ( extras.is_rtl ) {
						maximumValueAlign = 'right';
					} else {
						maximumValueAlign = 'left';
					}
				} else {
					maximumValueAlign = values.maximum_value_text_align;
				}

				// If active and maximum value are displayed on opposite ends, we can stay within one row.
				if ( ( 'left' === activeValueAlign && 'right' === maximumValueAlign ) || ( 'right' === activeValueAlign && 'left' === maximumValueAlign ) ) {
					attr[ 'class' ] += ' awb-space-between';

					// Make sure we cover the left/right aspect correctly.
					if ( ( 'left' === activeValueAlign && 'right' === maximumValueAlign && extras.is_rtl ) || ( 'right' === activeValueAlign && 'left' === maximumValueAlign && ! extras.is_rtl ) ) {
						attr[ 'class' ] += ' awb-flow-row-reverse';
					}
				} else {
					attr[ 'class' ] += ' awb-direction-column';
				}

				return attr;
			},

			getInlineStyle: function( values ) {
				var cssVarsOptions,
					customVars = {};
				this.values = values;

				cssVarsOptions = [
					'text_align',
					'text_line_height',
					'text_text_transform',
					'textcolor',

					'maximum_value_line_height',
					'maximum_value_text_transform',
					'maximum_value_color',
					'maximum_value_text_align',

					'height',
					'filledcolor',
					'fully_filled_color',
					'unfilledcolor',

					'border_radius_top_left',
					'border_radius_top_right',
					'border_radius_bottom_left',
					'border_radius_bottom_right',

					'filledbordersize',
					'filledbordercolor'
				];

				cssVarsOptions.margin_top = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.margin_right = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.margin_bottom = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.margin_left = { 'callback': _.fusionGetValueWithUnit };

				cssVarsOptions.text_font_size = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.text_letter_spacing = { 'callback': _.fusionGetValueWithUnit };

				cssVarsOptions.maximum_value_font_size = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.maximum_value_letter_spacing = { 'callback': _.fusionGetValueWithUnit };

				cssVarsOptions.filling_speed = { 'callback': this.sanitizeFillingSpeed };

				// Need to set the remaining width percentage on the maximum value wrapper, if it is on bar.
				customVars.maximum_value_wrapper_width = ( 100 - this.sanitizePercentage( values.percentage, values.maximum_value, values.stock_mode ) ) + '%';

				// Active value font.
				jQuery.each( _.fusionGetFontStyle( 'text_font', values, 'object' ), function( rule, value ) {
					customVars[ rule ] = value;
				} );

				// Maximum value font.
				jQuery.each( _.fusionGetFontStyle( 'maximum_value_font', values, 'object' ), function( rule, value ) {
					customVars[ 'maximum-value-' + rule ] = value;
				} );

				return this.getCssVarsForOptions( cssVarsOptions ) + this.getCustomCssVars( customVars );
			},

			/**
			 * Helper method to sanitize filling_speed arg.
			 *
			 * @since 3.14.1
			 * @param {String} value The value to sanitize.
			 * @return {String}
			 */
			sanitizeFillingSpeed: function( value ) {
				return '' !== value ? value + 'ms' : value;
			},

			/**
			 * Sanitize the percentage value, because this can come also from a
			 * dynamic data which can be a string or a float.
			 *
			 * @since 3.6
			 * @param {String} activeValue - The active value.
			 * @param {String} maximumValue - The maximum value.
			 * @param {String} stockMode - Flag to decide if stock mode should be used..
			 * @return {Float}
			 */
			sanitizePercentage: function( activeValue, maximumValue, stockMode ) {
				let percentage = 0;

				activeValue  = this.convertHtmlStringToFloat( activeValue );
				maximumValue = this.convertHtmlStringToFloat( maximumValue );

				if ( ! activeValue ) {
					activeValue = 0;
				}

				// No maximum value is set, so this is 100%.
				if ( ! maximumValue ) {
					return 100;
				}

				// Stock management calc.
				maximumValue = 'yes' === stockMode ? maximumValue + activeValue : maximumValue;

				percentage = activeValue / maximumValue * 100;
				percentage = Math.round( percentage );

				if ( 0 > percentage ) {
					percentage = 0;
				}

				if ( 100 < percentage ) {
					percentage = 100;
				}

				return percentage;
			},

			/**
			 * Convert an HTML string value to a float.
			 *
			 * @since 3.14.1
			 * @param {String} value - The value that is wrapped in an HTML string.
			 * @return {Float}
			 */
			convertHtmlStringToFloat: function( value ) {
				if ( jQuery.isNumeric( value ) ) {
					return parseFloat(value);
				}

				const isWooCommercePrice = -1 !== value.indexOf( 'woocommerce-Price' );

				// Strip HTML and decode entities
				const div = document.createElement( 'div' );
				div.innerHTML = value;
				value         = div.textContent || div.innerText || '';

				let match;

				if ( isWooCommercePrice ) {

					// Escape separators for regex.
					const escapedDecimal  = this.extras.woo_decimal_separator.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
					const escapedThousand = this.extras.woo_thousand_separator.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
					const pattern         = new RegExp( '\\d{1,3}(?:' + escapedThousand + '\\d{3})*(?:' + escapedDecimal + '\\d+)?' );

       			 	match = value.match(pattern);

					if ( match ) {
						value = match[0];
						value = '' !== this.extras.woo_thousand_separator ? value.split( this.extras.woo_thousand_separator ).join( '' ) : value;
						value = '.' !== this.extras.woo_decimal_separator ? value.replace( this.extras.woo_decimal_separator, '.' ) : value;
					}
				} else {
					match = value.match( /\d+(?:\.\d+)?/ );

					if ( match ) {
						value = match[0];
					}
				}

				value = parseFloat( value );

				return value ? value : 0;
			}
		} );
	} );
}( jQuery ) );
