/* global FusionPageBuilderApp */

function awbAuthMapOption( $element ) {

	var self = this;

	// Cut off check.
	if ( 'undefined' === typeof window.awbAuthMap ) {
		return;
	}

	// Set reusable vars.
	self.$optionWrap = $element;
	self.type        = self.$optionWrap.data( 'option-id' ).replace( '_map', '' );
	self.$el         = self.$optionWrap.find( '.auth-map-holder .fusion-mapping' );
	self.$input      = self.$optionWrap.find( '.auth-map-holder' ).children( 'input' );
	self.values      = {};

	try {
		self.values = JSON.parse( self.$input.val() );
	} catch ( e ) {
		console.warn( 'Error triggered - ' + e );
	}

	// Add listeners.
	jQuery( document ).on( 'fusion-builder-content-updated', function() {
		self.updateMap();
	} );

	// Listen to Submission Actions change.
	jQuery( document.body ).on( 'change', '#pyre_form_actions', function() {
		self.updateMap();
	} );

	this.$el.on( 'change', 'select', function() {
		self.updateValues();
	} );
}

awbAuthMapOption.prototype.updateValues = function() {
	var values = {};

	this.$el.find( 'select' ).each( function() {
		values[ jQuery( this ).attr( 'name' ) ] = jQuery( this ).val();
	} );

	this.values = values;

	this.$input.val( JSON.stringify( values ) );
	setTimeout( () => {
		this.$input.trigger( 'change' );
	}, 10 );
};

awbAuthMapOption.prototype.updateMap = function() {
	const self = this;

	self.$el.children().remove();

	if ( ! self.$el.children().length ) {
		const $fields = self.getFields();

		self.$el.append( $fields );
	}

	self.$el.find( '.form-input-entry select' ).each( function() {
		if ( 'string' === typeof self.values[ jQuery( this ).attr( 'name' ) ] ) {
			jQuery( this ).val( self.values[ jQuery( this ).attr( 'name' ) ] );
		} else {
			jQuery( this ).val( 'placeholder' );
		}
	} );
};

awbAuthMapOption.prototype.getFields = function() {
	const self  = this,
		options = this.getOptions();

	let fieldNames     = [],
		userLoginLabel = 'user_login',
		fields         = '';

	switch ( self.type ) {
		case 'login':
			fieldNames     = [ 'user_login', 'user_pass', 'rememberme' ];
			break;
		case 'register':
			fieldNames     = [ 'user_login', 'user_email', 'user_pass', 'first_name', 'last_name' ];
			userLoginLabel = 'username';
			break;
		case 'lost_password':
			fieldNames     = [ 'user_login' ];
			userLoginLabel = 'lost_password';
			break;
		case 'reset_password':
			fieldNames     = [ 'user_pass' ];
			break;
		default:
			fieldNames     = [ 'user_login', 'user_pass', 'rememberme' ];
			break;
	}

	fieldNames.forEach( function( fieldName ) {
		const label = 'user_login' === fieldName ? window.awbAuthMap['label_' + userLoginLabel ] : window.awbAuthMap[ 'label_' + fieldName ];

		fields += '<div class="form-input-entry"><label for="fusionmap-' + fieldName + '">' + label + '</label><select class="fusion-dont-update" name="' + fieldName + '" id="fusionmap-fusionmap-' + fieldName + '">' + options + '</select></div>';
	} );

	return fields;
};

awbAuthMapOption.prototype.getOptions = function() {
	var formElements = false,
		self         = this,
		options      = '<option value="placeholder" disabled selected hidden>' + window.awbAuthMap.label_placeholder + '</option>';

	if ( 'object' !== typeof FusionPageBuilderApp.simplifiedMap ) {
		self.$el.empty();
		return;
	}

	// Filter map to only get form elements.
	formElements = _.filter( FusionPageBuilderApp.simplifiedMap, function( element ) {
		return element.type.includes( 'fusion_form' ) && 'fusion_form_consent' !== element.type && 'fusion_form_submit' !== element.type && ( 'string' === typeof element.params.label || 'string' === typeof element.params.name );
	} );

	_.each( formElements, function( formElement ) {
		var params      = formElement.params,
			inputLabel  = 'string' === typeof params.label && '' !== params.label ? params.label : params.name,
			elementType = formElement.type,
			arrayType   = 'fusion_form_checkbox' === elementType || 'fusion_form_image_select' === elementType ? '[]' : '';

		if ( ( 'undefined' === typeof atts || ( 'undefined' !== typeof atts && atts.cid !== formElement.get( 'cid' ) ) ) && ( '' !== params.name || '' !== inputLabel ) ) {
			const optionName  = 'object' === typeof inputLabel ? inputLabel[0] : inputLabel,
				optionValue   = Number.isInteger( params.name + arrayType ) ? parseInt( params.name + arrayType ) : params.name + arrayType,
				isPlaceholder = 'placeholder' === params.name + arrayType ? ' disabled selected hidden ' : '';

			options += '<option value="' + optionValue + '"' + isPlaceholder + '>' + optionName + '</option>';
		}
	} );

	return options;
};

( function( jQuery ) {
	'use strict';

	// Trigger actions on ready event.
	jQuery( document ).ready( function() {
		// Listen to Submission Actions change.

		jQuery( '.auth_map' ).each( function() {
			new awbAuthMapOption( jQuery( this ) );
		} );
	} );
}( jQuery ) );
