/**
 * Define this data attribute.
 * @attr data-action-type Action type (can be: toggle|add|remove, default: toggle)
 * @attr data-action-value String value which will affect the attribute value of the destination DOM element(s) (required)
 * @attr data-action-event jQuery/custom event fires up action (default: 'click')
 * @attr data-action-event-threshold Custom event (swipe) threshold value (default: 10)
 * @attr data-action-source-selector A selector which determines the source DOM element(s) (default: this)
 * @attr data-action-destination-selector A selector which determines the destination DOM element(s) (default: this)
 * @attr data-action-attribute The attribute of the destination DOM element (default: 'class')
 * @attr data-action-interval-time Time of the function calling interval (in milliseconds). Used at custom scroll event checking (default: 100).
 * @attr data-action-extra-condition Function call which determines extra condition for the execution of the action (default: true)
 **/
define('system/js/data/action-value', ["../ofw-jquery"], function() {

    /** Properties **/
	var scrollInterval = null;
	var scrollElements = [];
	var touchPositions = {
		startX: null,
		startY: null,
		currentX: null,
		currentY: null
	};

    /** Private API **/

    /**
     * Object init
     */
    var init = function(){
    	// Add any init here
    };

	/**
	 * Trigger custom swipe events
	 * @param {object} element Element data object
	 */
	function handleSwipeEvent(element) {

		var touchStarted = false;
		var _events = [];

		$(document).on('touchstart', element.sourceElm, function(e) {
			var pointer = getPointerEvent(e);

			// caching the current x
			touchPositions.startX = touchPositions.currentX = pointer.pageX;
			touchPositions.startY = touchPositions.currentY = pointer.pageY;

			// a touch event is detected
			touchStarted = true;

			// detecting if after 200ms the finger is still in the same position
			setTimeout(function (){
				if ((touchPositions.startX === touchPositions.currentX) && !touchStarted && (touchPositions.startY === touchPositions.currentY)) {
					touchStarted = false;
				}
			},200);
		});

		$(document).on('touchmove', element.sourceElm, function(e) {
			var pointer = getPointerEvent(e);

			_events = [];

			touchPositions.currentX = pointer.pageX;
			touchPositions.currentY = pointer.pageY;

			if (touchPositions.currentX + element.event_threshold < touchPositions.startX) {
				_events.push('swipeleft');
			} else if (touchPositions.currentX + element.event_threshold > touchPositions.startX) {
				_events.push('swiperight');
			}

			if (touchPositions.currentY + element.event_threshold < touchPositions.startY) {
				_events.push('swipeup');
			} else if (touchPositions.currentY + element.event_threshold > touchPositions.startY) {
				_events.push('swipedown');
			}

		});

		$(document).on('touchend touchcancel', element.sourceElm, function(e) {

			if (touchStarted && _events.length && element.extra_condition()) {

				var event_idx = _events.indexOf(element.event);

				if (event_idx > -1) {
					element.sourceElm.trigger(_events[event_idx]);
				}
			}

			// here we can consider finished the touch event
			touchStarted = false;
			_events = [];
		});
	}

	/**
	 * Custom scroll event checking
	 * @param {int} index The index of the current scroll_element (from scrollElements)
	 * @return {boolean} True if scroll event condition is fulfilled, false if not
	 */
	function checkScrollEvent(index) {

		var condition, direction;
		var element = scrollElements[index];

		// Browser's top position bouncing is not a scroll event
		if (element.sourceElm.scrollTop() < 0 || element.sourceElm.scrollTop() + element.sourceElm.outerHeight() > $(document).height()) {
			return false;
		}

		switch (element.event) {
			case 'scroll-start':
				condition = (null === element.lastY && element.sourceElm.scrollTop() > 0);
				break;
			case 'scroll-end':
				condition = (element.lastY == element.sourceElm.scrollTop());
				break;
			case 'scroll':
				condition = (null !== element.lastY && element.lastY != element.sourceElm.scrollTop());
				break;
			case 'scroll-up':
				condition = (null !== element.lastY && element.lastY < element.sourceElm.scrollTop());
				break;
			case 'scroll-down':
				condition = (null !== element.lastY && element.lastY > element.sourceElm.scrollTop());
				break;
			case 'scroll-dir-change':
				condition = (null !== element.lastY && ((element.direction != 1 && element.lastY < element.sourceElm.scrollTop()) || (element.direction != -1 && element.lastY > element.sourceElm.scrollTop())));
				break;
			case 'scroll-dir-change-up':
				condition = (null !== element.lastY && element.direction != 1 && element.lastY < element.sourceElm.scrollTop());
				break;
			case 'scroll-dir-change-down':
				condition = (null !== element.lastY && element.direction != -1 && element.lastY > element.sourceElm.scrollTop());
				break;
			case 'scroll-reached-top':
				condition = (null !== element.lastY && element.direction != 1 && element.sourceElm.scrollTop() == 0);
				break;
			case 'scroll-reached-bottom':
				condition = (null !== element.lastY && element.direction != -1 && element.sourceElm.scrollTop() + element.scrollContainer.height() == element.scrollContent.height());
				break;

		}

		if (element.lastY < element.sourceElm.scrollTop()) {
			direction = 1;
		} else if (element.lastY > element.sourceElm.scrollTop()) {
			direction = -1;
		} else {
			direction = null;
		}

		scrollElements[index].direction = direction;
		scrollElements[index].lastY = (element.event == 'scroll-end')?null:element.sourceElm.scrollTop();

		return (element.extra_condition() && condition);
	}

	/**
	 * Trigger action
	 * @param {object|int} element The element object/index of the current scroll_element (from scrollElements)
	 * @param {object} _this The DOM element of the current source element
	 */
	function triggerAction(element, _this) {
		if (typeof element != 'object') {
			element = scrollElements[element];
		}

		var $this,
			attr,
			new_value,
			values,
			value,
			current_values,
			current_idx;

		values = element.value.split(",");

		for (var idx in values) {

			value = values[idx].trim();

			element.destElm.each(function() {

				$this = (element.destSelector === null)?_this:$(this);

				attr = $this.attr(element.attribute);

				if (undefined !== attr) {
					current_values = attr.split(" ");
					current_idx = current_values.indexOf(value);
				} else {
					current_values = null;
					current_idx = -1;
				}

				if (element.type != 'remove' && current_idx < 0) {
					// console.log('Nincs neki ' + value + ', csak ' + current_values.join(" "));
					new_value = ((attr !== undefined && attr.length > 0) ? attr + ' ' : '') + value;
					$this.attr(element.attribute, new_value);
				}
				else if (element.type != 'add' && current_idx > -1) {
					// console.log('Van neki ' + value + ', elvesszük');
					current_values.splice(current_idx, 1);
					$this.attr(element.attribute, current_values.join(" "));
				}

			});
		}
	}

	/**
	 * ???
	 * @param {Event} event
	 * @returns {Event}
	 */
	var getPointerEvent = function(event) {
		return event.originalEvent.targetTouches ? event.originalEvent.targetTouches[0] : event;
	};


    /** Public API **/

    var api = {

        /**
         * Activate all the data attributes in this context.
		 * @param {jQuery|Array} $elements An array of jQuery objects that have the data attribute.
		 * @param {jQuery} [$context=$(document)] The current jQuery object context in which the handlers are searched for.
         */
        activate: function($elements, $context) {
			// Cross-browser transition end event trigger
			if ($context.find('[data-action-event="trans-end"]').length) {
				$(document).on('transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd', '[data-action-event="trans-end"]', function () {
					$(this).trigger('trans-end');
				});
			}
			if ($context.find('[data-action-event="anim-end"]').length) {
				// Cross-browser animation end event trigger
				$(document).on('animationend webkitAnimationEnd oAnimationEnd MSAnimationEnd', '[data-action-event="anim-end"]', function () {
					$(this).trigger('anim-end');
				});
			}

			$elements.each(function(){
				var $el =  $(this);

				// Element data object
				var element = {
					type: ($el.data('action-type'))?($el.data('action-type')):'toggle',
					sourceSelector: ($el.data('action-source-selector'))?($el.data('action-source-selector')):null,
					destSelector: ($el.data('action-destination-selector'))?($el.data('action-destination-selector')):null,
					event: ($el.data('action-event'))?($el.data('action-event')):'click',
					event_threshold: ($el.data('action-event-threshold'))?parseInt($el.data('action-event-threshold')):10,
					attribute: ($el.data('action-attribute'))?($el.data('action-attribute')):'class',
					value: $el.data('action-value'),
					extra_condition: ($el.data('action-extra-condition'))?(window[$el.data('action-extra-condition')]):(function() {return true})
				};

				// Handle special selectors (window/document)
				switch (element.sourceSelector) {
					case 'window':
						element.sourceElm = $(window);
						break;
					case 'document':
						element.sourceElm = $(document);
						break;
					case null:
						element.sourceElm = $el;
						break;
					default:
						element.sourceElm = $(element.sourceSelector);
				}

				element.destElm = (element.destSelector !== null)?$(element.destSelector):element.sourceElm;

				// Has scroll event
				if (element.event.indexOf('scroll') > -1) {
					element.lastY = null;
					element.direction = null;
					element.scrollContent = (element.sourceSelector == 'window' || element.sourceSelector == 'document')?$(document):element.sourceElm;
					element.scrollContainer = (element.sourceSelector == 'window' || element.sourceSelector == 'document')?$(window):element.sourceElm.parent();

					scrollElements.push(element);

					if (null === scrollInterval) {
						var scrollIntervalTime = ($el.data('action-interval-time'))?($el.data('action-interval-time')):'100';
						scrollInterval = setInterval(function() {
							for (var index in scrollElements) {
								if (checkScrollEvent(index)) {
									triggerAction(index);
								}
							}
						}, scrollIntervalTime);
					}
				}
				else if (element.event.indexOf('swipe') > -1) {
						handleSwipeEvent(element);
						element.sourceElm.on(element.event, function() {
							triggerAction(element, $(this));
						});
				}
				else {
					element.sourceElm.on(element.event, function() {
						if (element.extra_condition()) {
							triggerAction(element, $(this));
						}
					});
				}
			});

        }

    };

    /** Perform initialization **/
    init();

    // Return my external API
    return api;

});