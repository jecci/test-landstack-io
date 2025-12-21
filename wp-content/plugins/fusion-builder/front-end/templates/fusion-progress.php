<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 2.0
 */

?>
<script type="text/html" id="tmpl-fusion_progress-shortcode">
<#
// Active value.
const text                    = '<span ' + _.fusionGetAttributes( attrEditor ) + '>' + FusionPageBuilderApp.renderContent( values.element_content, cid, false ) + '</span>';
const value                   = 'percentage' === values.value_display_type ? percentage : sanitizedActiveValue;
const valueUnit               = 'before' === values.active_value_unit_position ? values.unit + value : value + values.unit;
const valueWrap               = 'yes' === values.show_percentage ? ' <span ' + _.fusionGetAttributes( 'fusion-progressbar-value' ) + '>' + valueUnit + '</span>' : '';
const textWrapNode            = 'on_bar' === values.text_position ? 'span' : 'div';
let textWrapper               = '<' + textWrapNode + ' ' + _.fusionGetAttributes( attrActiveValueWrap ) + '>' + text + valueWrap + '</' + textWrapNode + '>';

// Maximum value.
const maximumValueText        = '<span class="awb-pb-max-value-text">' + values.maximum_value_text + '</span>';
const maximumValue            = 'percentage' === values.value_display_type ? '100' : sanitizedMaximumValue;
const maximumValueUnit        = 'before' === values.maximum_value_unit_position ? values.maximum_value_unit + maximumValue : maximumValue + values.maximum_value_unit;
const maximumValueWrap        = 'yes' === values.display_maximum_value ? ' <span class="awb-pb-max-value">' + maximumValueUnit + '</span>' : '';
const maximumValueWrapperNode = 'on_bar' === values.maximum_value_text_position ? 'span' : 'div';
let maximumValueTextWrapper   = '<' + maximumValueWrapperNode + ' ' + _.fusionGetAttributes( attrMaximumValueWrap ) + '>' + maximumValueText + maximumValueWrap + '</' + maximumValueWrapperNode + '>';

// Progress bar.
const bar = '<div ' + _.fusionGetAttributes( attrBar ) + '><div ' + _.fusionGetAttributes( attrContent ) + '></div></div>';

// If activae value and maximum value are both either above or below the bar, put them in a wrapper together.
if ( values.maximum_value_text_position === values.text_position && 'on_bar' !== values.text_position ) {
	textWrapper             = '<span ' + _.fusionGetAttributes( attrTextsWrap ) + '>' + textWrapper + maximumValueTextWrapper + '</span>';
	maximumValueTextWrapper = '';
}

let html = '';

// Active value is above bar.
if ( 'above_bar' === values.text_position ) {
	html = textWrapper;
	
	// Maximum value is above bar.
	if ( 'above_bar' === values.maximum_value_text_position ) {
		html += maximumValueTextWrapper + bar;
	} else if ( 'on_bar' === values.maximum_value_text_position ) {

		// Maximum value is on bar.
		html += bar.replace( '</div></div>', '</div>' + maximumValueTextWrapper + '</div>' );
	} else {

		// Maximum value is below bar.
		html += bar + maximumValueTextWrapper;
	}
} else {

	// Active value is on bar.
	if ( 'on_bar' === values.text_position ) {
		html = bar.replace( '</div></div>', textWrapper + '</div></div>' );
	} else {

		// Active value is below bar.
		html = bar + textWrapper;
	}

	// Maximum value is above bar.
	if ( 'above_bar' === values.maximum_value_text_position ) {
		html = maximumValueTextWrapper + html;
	} else if ( 'on_bar' === values.maximum_value_text_position ) {

		// Maximum value is on bar.
		html = html.replace( '</div></div>', '</div>' + maximumValueTextWrapper + '</div>' );

	} else {

		// Maximum value is below bar.
		html += maximumValueTextWrapper;
	}
}
#>

<div {{{ _.fusionGetAttributes( attr ) }}}>{{{ html }}}</div>
</script>
