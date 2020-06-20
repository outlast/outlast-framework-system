/**
 * Provides a general class for sending and processing events related to UI changes, field updates, etc.
 **/
define('system/js/data/event', ["../ofw-jquery"], function (ofw) {

	/** Private properties **/
	let _dataAttributeName = 'event';

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


	let addEvent = function () {

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
		 * Add triggers to an element.
		 * @attr data-event-trigger-minor A comma-separated list of minor events to trigger. Minor events happen very often (on keyup for example) and should not have expensive callbacks.
		 * @attr data-event-trigger-major A comma-separated list of major events to trigger. Major events happen infrequently and can have expensive callbacks (API save for example).
		 */
		trigger: function (dataset, $el) {
			// This would be triggered when the element is loaded
			// - it would receive dataset.eventTriggerMinor, dataset.eventTriggerMajor (assuming these attr are set)
			// - $el is the element where the data attr was found
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
		 * Trigger an event manually.
		 * @param {OfwType.Event} eventType The name of the event.
		 * @param {jQuery} $el The element.
		 */
		trigger: function (eventType, $el) {

		},

		/**
		 * Subscribes to an event.
		 * @param {OfwType.Event} eventType The name of the event.
		 * @param {function} callback A function that is called when the event fires. See below for parameters sent to the callback.
		 */
		subscribe: function(eventType, callback) {
			$("[data-event-trigger-"+eventType+"]").each(function(index, el) {
				let $sender = $(el)
				let eventName = $sender.data("event-trigger-"+eventType)
				$sender.on(eventName, function() {
					/**
					 * Calls the callback with parameters...
					 * @param {jquery} $sender The element that triggered the event.
					 * @param {OfwType.Event} eventType The type of event (major or minor).
					 */
					callback($sender, eventType)
				})
			})
		},

	};

	/** Perform initialization **/
	init();

	// Return my external API
	return api;

});
