/**
 * Define this data attribute.
 * @attr data-track-category Event category, required
 * @attr data-track-label  Event label, required
 * @attr data-track-action  Event action, optional, defaults to "click"
 * @attr data-track-value  Event value, optional, must be a string
 * @event ofw:track:trigger  A callback function called when the event is triggered. Will pass category, action, label, value in this order.
 **/
define('system/js/data/track', ["../ofw-jquery"], function() {

    /** Private properties **/
    var _objects = [];			// An array of autopagination objects on this page

    /** Private API **/
    var defaultOptions = {
        // Timeout of the delayed scroll event handling
        addScrollCheckTimeout: 10000,

        // Interval for checking scroll position
        scrollCheckInterval: 1000

    };

    var postOptions = {};
    var options = {};

    var dimensions = {};

    var scrollCheckIntervalTimer;
    var previousScrollTop;

    // scroll events
    var events = {
        postScrollPercents: [
            25,
            50,
            75,
            100
        ]
    };

    /**
     * Object init
     */
    var init = function(){
        // Merge default options
        postOptions = $.extend(true, {}, defaultOptions, options);
        // Set post height, position, and height of a post segment

        $(window).on('load resize', function() {
            setDimensions();
        });

        window.addEventListener('orientationchange', function(){
            setDimensions();
        });
    };

    /** Public API **/
    var api = {

        /**
         * Activate all the data attributes in this context.
         * @param {jQuery|Array} $elements An array of jQuery objects that have the data attribute.
         * @param {jQuery} [$context=$(document)] The current jQuery object context in which the handlers are searched for.
         */
        activate: function($elements, $context) {
            $elements.each(function(){
                var $el =  $(this);
                var category = $el.attr('data-track-category');
                if(typeof(category) == 'undefined' || category == '') {
                    ofw.log('track.js: data-track-category attribute is required to send events.');
                    return;
                }

                var label = $el.attr('data-track-label');
                if(typeof(label) == 'undefined' || label == '') {
                    ofw.log('track.js: data-track-label attribute is required to send events.');
                    return;
                }

                var action = $el.attr('data-track-action');
                if(typeof(action) == 'undefined' || action == '') {
                    action = 'click';
                }

                var value = $el.attr('data-track-value');
                // track read events
                if(action == 'read'){
                    setDimensions();
                    // Delayed call of addScrollCheck
                    setTimeout(function(){
                        addScrollCheck($el, category, action, label, value);
                    }, postOptions.addScrollCheckTimeout);
                }
                // track click events
                else{
                    $el.click(function(){
                        ofw.track(category, action, label, value);
                        $el.trigger('ofw:track:trigger', [category, action, label, value]);
                    });
                }
            });
        }
    };

    /**
     * Set dimensions based on div.
     */
    var setDimensions = function() {
        var $post = $('[data-track-action=read]');
        if(typeof($post) == 'undefined' || $post.length == 0) return;
      	if($post.length > 1){
        	ofw.error("You can only track one read action per page.");
          	return;
        }
        dimensions.postHeight = $post.height();
        dimensions.postTop = $post.offset().top;
        console.log("Setting ofw-track dimensions", dimensions);
    };

	/**
     * Run scroll check.
     * @return boolean Returns true if any event was triggered, false if none.
	 */
    var processScrollCheck = function($el, category, action, label, value) {

        // Check if our scroll top is different from previous
        var scrollTop = $(window).scrollTop();
        if(previousScrollTop == scrollTop) return false;
        else previousScrollTop = scrollTop;

        // Set variable defaults
        var somethingTriggered = false;
        var percentsToCheckAreRemaining = false;
        var percent;

        // Check to see if we are within range of the reading zone
        if (scrollTop > dimensions.postTop && scrollTop < dimensions.postTop + dimensions.postHeight){

            // Look through all the percents (we need to clone so that splice does not affect me)
            console.log("Checking events", events.postScrollPercents);
            for (var perc_idx = 0; perc_idx < events.postScrollPercents.length; perc_idx++) {
                percent = events.postScrollPercents[perc_idx];
                if (percent !== false) {
                    console.log(scrollTop, ">=", dimensions.postHeight, "*", percent, "/", 100);
                    if (scrollTop >= dimensions.postHeight * percent / 100){
                        // Send GA event
                        ofw.track(category, action, label, percent);
                        // Trigger event
                        $el.trigger('ofw:track:trigger', [category, action, label, value]);
                        // Mark as visited (set element to boolean false)
                        events.postScrollPercents[perc_idx] = false;
                        somethingTriggered = true;
                    }
                    percentsToCheckAreRemaining = true;
                }
            }
        }

        // If no more events left to check then clear interval and return false
        if(!percentsToCheckAreRemaining){
            clearInterval(scrollCheckIntervalTimer);
		}

        return somethingTriggered;
     };

	/**
     * Add scroll event check.
     */
    var addScrollCheck = function($el, category, action, label, value){
        scrollCheckIntervalTimer = setInterval(function(){
            processScrollCheck($el, category, action, label, value);
        }, postOptions.scrollCheckInterval);
    };

    /** Perform private initialization **/
    init();

    // Return my external API
    return api;

});