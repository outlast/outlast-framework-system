/**
 * Define this data attribute.
 **/
define('system/js/data/block', ["../ofw-jquery"], function() {

    /** Options **/
    var defaultOptions = {

    };
    var _myOptions = {};

    /** Private properties **/
    var _dataAttributeName = 'block';
    var _initialFullRequest;

    /** Private API **/

    /**
     * Object init
     */
    var init = function(){
    	// Options can be set below
    	_initialFullRequest = ofw.fullrequest;
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
	 * Reload contents of a block.
	 * @param {string} blockName The block name.
	 * @param {function} callback A callback function after the reload finished.
	 */
	var reloadBlock = function(blockName, callback){
		loadBlock(_initialFullRequest, blockName, callback);
	};

	/**
	 * Load contents into a block.
	 * @param {string} url The full url.
	 * @param {string} blockName The block name.
	 * @param {function} callback A callback function after the reload finished.
	 */
	var loadBlock = function(url, blockName, callback){
		var myUrl = ofw.queryMode(url)+'&zaj_pushstate_block='+blockName;
		ofw.ajax.get(myUrl, function(r){
			var $block = $('[data-block="'+blockName+'"]');
			$block.html(r);
			ofw.activateDataAttributeHandlers($block);
			if(typeof callback === 'function') callback(blockName);
		});
	};

    /** Actions **/
    var actions = {

	};

    /** Activations **/
    var activations = {

	};

	/** API **/
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
		 * Enable public methods.
		 */
		reload: reloadBlock,
		reloadBlock: reloadBlock,
		load: loadBlock,
		loadBlock: loadBlock
	};

	/** Perform initialization **/
    init();

    // Return my external API
    return api;

});
