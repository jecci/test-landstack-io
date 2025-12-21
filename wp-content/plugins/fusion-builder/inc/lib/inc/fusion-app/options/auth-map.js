/* globals FusionPageBuilderApp, FusionApp, fusionSanitize */
var FusionPageBuilder = FusionPageBuilder || {};
FusionPageBuilder.options = FusionPageBuilder.options || {};

function awbAuthMapOption( $element ) {
	var self = this;

	if ( 'object' !== typeof FusionApp.data.authmap || ! $element.find( '.auth_map' ).length ) {
		return;
	}

	// Set reusable vars.
	self.$optionWrap = $element.find( '.auth_map' );
	self.type        = self.$optionWrap.data( 'option-id' ).replace( '_map', '' );
	self.$el         = self.$optionWrap.find( '.auth-map-holder .fusion-mapping' );
	self.$input      = self.$optionWrap.find( '.auth-map-holder' ).children( 'input' );
	self.values      = {};

	try {
		self.values = JSON.parse( self.$input.val() );
	} catch ( e ) {
		console.warn( 'Error triggered - ' + e );
	}

	// Initial build.
	self.updateMap();

	// Add listeners.
	FusionPageBuilderApp.collection.on( 'change reset add remove', function() {
		self.updateMap();
	} );

	self.$el.on( 'change', 'select', function() {
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

	switch( self.type ) {
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
		const label = 'user_login' === fieldName ? FusionApp.data.authmap['label_' + userLoginLabel ] : FusionApp.data.authmap[ 'label_' + fieldName ];

		fields += '<div class="form-input-entry"><label for="fusionmap-' + fieldName + '">' + label + '</label><div class="fusion-select-wrapper"><select class="fusion-dont-update" name="' + fieldName + '" id="fusionmap-fusionmap-' + fieldName + '">' + options + '</select><span class="fusiona-arrow-down"></span></div></div>';
	} );

	return fields;
};

awbAuthMapOption.prototype.getOptions = function() {
	var formElements = false,
		self         = this,
		options      = '<option value="placeholder" disabled selected hidden>' + FusionApp.data.authmap.label_placeholder + '</option>';
		
	if ( 'object' !== typeof FusionPageBuilderApp.collection ) {
		self.$el.empty();
		return;
	}

	// Filter map to only get form elements.
	formElements = _.filter( FusionPageBuilderApp.collection.models, function( element ) {
		var params = element.get( 'params' );
		if ( 'object' !== typeof params ) {
			return false;
		}
		return element.get( 'element_type' ).includes( 'fusion_form' ) && 'fusion_form_submit' !== element.get( 'element_type' ) && 'fusion_form_consent' !== element.get( 'element_type' ) && ( 'string' === typeof params.label || 'string' === typeof params.name );
	} );

	_.each( formElements, function( formElement ) {
		var params      = formElement.get( 'params' ),
			inputLabel  = 'string' === typeof params.label && '' !== params.label ? params.label : params.name,
			elementType = formElement.get( 'element_type' ),
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

FusionPageBuilder.options.awbAuthMap = {

	/**
	 * Run actions on load.
	 *
	 * @since 3.1
	 *
	 * @return {void}
	 */
	optionAuthMap: function( $element ) {
		if ( 'undefined' === typeof this.authMap ) {
			this.authMap = new awbAuthMapOption( $element );
		}
	}
};
