/**
 * Provides a general class for sending and processing events related to UI changes, field updates, etc.
 **/
define('system/js/data/event', ["../ofw-jquery"], function (ofw) {

	/** Private properties **/
	let _dataAttributeName = 'event';

	// A list of subscriptions to minor events
	var _minorSubscriptions = [];

	// A list of subscriptions to major events
	var _majorSubscriptions = [];

	/** Type definitions **/

	/** Defines the type of event **/
	OfwType["Event"] = {
		// Minor events are like keyup, they fire very frequently. You should really consider what you subscribe this to.
		MINOR: "minor",
		// Major events are like onchange, they fire less frequently and can be tied to costly transactions like API save.
		MAJOR: "major"
	};

	/** Private API **/

	/** Object init */
	let init = function () { };

	/**
	 * Call an action.
	 */
	let actionHandler = function () {
		let $element = $(this);
		let actionName = $element.attr('data-' + _dataAttributeName);
		if (typeof actions[actionName] == 'function') actions[actionName](this.dataset, $element);
	};

	/**
	 * Activation handler.
	 */
	let activationHandler = function () {
		let $element = $(this);
		let actionName = $element.attr('data-' + _dataAttributeName);
		let activationTriggered = $element.attr('data-' + _dataAttributeName + '-activation-was-triggered');
		if (typeof activations[actionName] == 'function' && activationTriggered !== 'yes') {
			$element.attr('data-' + _dataAttributeName + '-activation-was-triggered', 'yes');
			activations[actionName](this.dataset, $element);
		}
	};

	/**
	 * Adds a callback for the event type.
	 * @param {OfwType.Event} eventType The name of the event.
	 * @param {function} callback A function that is called when the event fires. See below for parameters sent to the callback.
	 */
	let addEventCallbackToElements = function(eventType, callback) {
		$("[data-event-trigger-"+eventType+"]:not([data-event-trigger-"+eventType+"-activated='yes'])").each(function(index, el) {
			addEventCallbackToElement(el, eventType, callback);
		})
	}

	/**
	 * Adds a callback for the event type to a specific element.
	 * @param {jquery|dom} el A dom object or jquery object pointing to the element.
	 * @param {OfwType.Event} eventType The name of the event.
	 * @param {function} callback A function that is called when the event fires. See below for parameters sent to the callback.
	 */
	let addEventCallbackToElement = function(el, eventType, callback) {
		let $sender = $(el)
		let eventName = $sender.data("event-trigger-"+eventType)
		$sender.data("event-trigger-"+eventType+"-activated", "yes")
		$sender.on(eventName, function() {
			/**
			 * Calls the callback with parameters...
			 * @param {jquery} $sender The element that triggered the event.
			 * @param {OfwType.Event} eventType The type of event (major or minor).
			 */
			callback($sender, eventType)
		})
	}

	/** Actions **/
	let actions = {

		/**
		 * Respond to clicks
		 * @attr data-event-trigger-minor A comma-separated list of minor events to trigger. Minor events happen very often (on keyup for example) and should not have expensive callbacks.
		 * @attr data-event-trigger-major A comma-separated list of major events to trigger. Major events happen infrequently and can have expensive callbacks (API save for example).
		 */
		trigger: function (dataset, $el) {
			// This would be triggered when the element is clicked
			// - it would receive dataset.eventTriggerMinor, dataset.eventTriggerMajor (assuming these attr are set)
			// - $el is the element where the data attr was found
		},

	};

	/** Activations **/
	let activations = {

		/**
		 * Add existing subscriptions to new elements.
		 * @attr data-event-trigger-minor A comma-separated list of minor events to trigger. Minor events happen very often (on keyup for example) and should not have expensive callbacks.
		 * @attr data-event-trigger-major A comma-separated list of major events to trigger. Major events happen infrequently and can have expensive callbacks (API save for example).
		 */
		trigger: function (dataset, $el) {
			// Run through all existing subscriptions, and add callback to this element (if not already added)
			if(dataset.eventTriggerMinorActivated !== "yes") {
				_minorSubscriptions.forEach(function(subCallback){
					addEventCallbackToElement($el, OfwType.Event.MINOR, subCallback);
				})
			}
			// ...also for major
			if(dataset.eventTriggerMajorActivated !== "yes") {
				_majorSubscriptions.forEach(function (subCallback) {
					addEventCallbackToElement($el, OfwType.Event.MAJOR, subCallback);
				})
			}
		},

	};

	/** API **/
	let api = {
		/**
		 * Activate all the data attributes in this context.
		 * @param {jQuery|Array} $elements An array of jQuery objects that have the data attribute.
		 * @param {jQuery} [$context=$(document)] The current jQuery object context in which the handlers are searched for.
		 */
		activate: function ($elements, $context) {
			$elements.each(activationHandler);
			$elements.off('click', actionHandler).on('click', actionHandler);
		},

		/**
		 * Subscribes to an event.
		 * @param {OfwType.Event} eventType The name of the event.
		 * @param {function} callback A function that is called when the event fires. See below for parameters sent to the callback.
		 */
		subscribe: function(eventType, callback) {
			// Add it now to elements
			addEventCallbackToElements(eventType, callback)
			// Add it to subscriptions (so we can readd them when a new element is added to the DOM)
			if (eventType === OfwType.Event.MAJOR) {
				_majorSubscriptions.push(callback)
			} else if (eventType === OfwType.Event.MINOR) {
				_minorSubscriptions.push(callback)
			}
		},

	};

	/** Perform initialization **/
	init();

	// Return my external API
	return api;

});
