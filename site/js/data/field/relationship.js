/**
 * Course editing API.
 **/
define('system/js/data/field/relationship', ["../../ext/select2/select2.min", "../../ofw-jquery"], function(select2) {

    /** Properties **/
    var _dataAttributeName = 'relationship';

	/** Field settings **/
	var _relationshipFieldOptions = {};		// countLimit = the number of photos this field allows (1 or 0 /unlimited/) is supported
	var _relationshipFieldSelectObjects = {};

    /** Private API **/

    /**
     * Object init
     */
    var init = function(){
    	// Nothing to init!
    };

	/**
	 * Call an action.
	 */
	var actionHandler = function(){
		var $element = $(this);
		var actionName = $element.attr('data-'+_dataAttributeName);
		if(typeof actions[actionName] == 'function') actions[actionName](this.dataset, $element);
	};

	/**
	 * Activation handler.
	 */
	var activationHandler = function(){
		var $element = $(this);
		var actionName = $element.attr('data-'+_dataAttributeName);
		var activationTriggered = $element.attr('data-'+_dataAttributeName+'-activation-was-triggered');
		if(typeof activations[actionName] == 'function' && activationTriggered != 'yes'){
			$element.attr('data-'+_dataAttributeName+'-activation-was-triggered', 'yes');
			activations[actionName](this.dataset, $element);
		}
	};

	/**
	 * Set the input field value.
	 * @param {string} fieldid The field identifier.
	 * @param {Array|values} values An array of ids or a single id. Depending on the type of relationship.
	 * @param {bool} [triggerOnChangeEvent=true] If set to true (the default), onChange will be triggered.
	 */
	var setFieldValues = function(fieldid, values, triggerOnChangeEvent) {
		let $field = getSelectElement(fieldid)
		$field.val(values)
		if (typeof triggerOnChangeEvent === 'undefined' || triggerOnChangeEvent) {
			$field.trigger('change');
		}
	};

	/**
	 * Set the input field value.
	 * @param {string} fieldid The field identifier.
	 * @return {Array|values} An array of ids or a single id. Depending on the type of relationship.
	 */
	var getFieldValues = function(fieldid) {
		return getSelectElement(fieldid).val();
	};

	/**
	 * Get my select element
	 * @param {string} fieldid The field identifier.
	 * @return {jQuery} The list jquery element.
	 */
	var getSelectElement = function(fieldid) {
		return $('[data-relationship="select"][data-relationship-field-id="'+fieldid+'"]');
	};

	/**
	 * Init the select javascript.
	 * @param {string} fieldid The field unique id.
	 * @param {boolean} tagMode If set to true, free-text will be allowed (to create new items).
	 * @param {boolean} ajaxMode If set to true, the select will initialize as an ajax search box.
	 * @param {string} [className=null] The class name (zajModel name) for this search field. Only required if ajaxMode is true.
	 * @param {string} [fieldName=null] The field name (zajModel field name) for this search field. Only required if ajaxMode is true.
	 */
	var selectInit = function(fieldid, tagMode, ajaxMode, className, fieldName) {
		var $mySelectElement = getSelectElement(fieldid);

		// Default options
		var mySelectOptions = {
			allowClear: true,
			width: '100%'
		};

		// Tagging mode?
		if (tagMode) {
			mySelectOptions['tags'] = true;
		}

		// Set up options if in ajaxMode
		if(ajaxMode){
			mySelectOptions['ajax'] = {
				url: ofw.baseurl+"system/search/relation/",
				dataType: 'json',
				delay: 250,
				data: function (params) {
				  return {
					'query': params.term, // search term
					//'page': params.page,
					'class': className,
					'field': fieldName
				  };
				},
				processResults: function (data, params) {
				  // parse the results into the format expected by Select2
				  // since we are using custom formatting functions we do not need to
				  // alter the remote JSON data, except to indicate that infinite
				  // scrolling can be used
				  //params.page = params.page || 1;
				  return {
					results: data.data
					/**pagination: {
					// @todo fix pagination!
					  more: (params.page * 30) < data.data.length
					}**/
				  };
				},
				cache: true
			};
			mySelectOptions['minimumInputLength'] = 1;
			mySelectOptions['templateSelection'] = mySelectOptions['templateResult'] = function(node){
				if(node.name) return node.name;
				if(node.text) return node.text;
			};
		}

		// Turn off loading
		$('[data-relationship="loader"][data-relationship-field-id="'+fieldid+'"]').addClass('hide');

		// Finally, initialize and set
		_relationshipFieldSelectObjects[fieldid] = $mySelectElement.select2(mySelectOptions);
	};


    /** Actions **/
    var actions = {



	};

    /** Activations **/
    var activations = {

		/**
		 * Initialize the select.
		 */
		select: function(dataset, $el){
			selectInit(dataset.relationshipFieldId, dataset.relationshipFieldAllowNew === "true", dataset.relationshipAjaxMode, dataset.relationshipClassName, dataset.relationshipFieldName);
		}

	};

	/** Public API **/

    var api = {

        /**
         * Activate all the data attributes in this context.
		 * @param {jQuery|Array} $elements An array of jQuery objects that have the data attribute.
		 * @param {jQuery} [$context=$(document)] The current jQuery object context in which the handlers are searched for.
         */
        activate: function($elements, $context) {
			$elements.each(activationHandler);
        	$elements.off('click', actionHandler).on('click', actionHandler);
        },

		/**
		 * Set field value and then set the input value.
		 * @param {string} fieldid The field unique id.
		 * @param {Array|string} values An array of ids.
		 * @param {bool} [triggerOnChangeEvent=true] If set to true (the default), onChange will be triggered.
		 */
		setFieldValues: function(fieldid, values, triggerOnChangeEvent){
			// Set input
			setFieldValues(fieldid, values, triggerOnChangeEvent);
        },
		setFieldValue: function(fieldid, values, triggerOnChangeEvent){ api.setFieldValues(fieldid, values, triggerOnChangeEvent); },

		/**
		 * Get field value for a specific field and type.
		 * @param {string} fieldid The field unique id.
		 * @return {Array} Returns an array or string (depending on type of relationship).
		 */
		getFieldValues: function(fieldid){
			return getFieldValues(fieldid);
		},
		getFieldValue: function(fieldid){ return api.getFieldValues(fieldid); },


		/**
		 * Set field option.
		 * @param {string} fieldid The field unique id.
		 * @param {string} key The option key.
		 * @param {string|Number|Object|null} value The option value.
		 */
		setFieldOption: function(fieldid, key, value){
			if(typeof _relationshipFieldOptions[fieldid] === 'undefined') _relationshipFieldOptions[fieldid] = {};
			_relationshipFieldOptions[fieldid][key] = value;
		},

		/**
		 * Set field option.
		 * @param {string} fieldid The field unique id.
		 * @param {string} key The option key.
		 * @return {string|Number|Object|null} The option value or null if the key was not set.
		 */
		getFieldOption: function(fieldid, key){
			if(typeof _relationshipFieldOptions[fieldid] === 'undefined') return null;
			return _relationshipFieldOptions[fieldid][key];
		}
	};

	/** Perform initialization **/
    init();

    // Return my external API
    return api;

});