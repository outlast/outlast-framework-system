/**
 * Define this data attribute.
 * @attr data-track-category Event category, required
 * @attr data-track-label  Event label, required
 * @attr data-track-action  Event action, optional, defaults to "click"
 * @attr data-track-value  Event value, optional, must be a string
 **/
define('system/js/data/track', ["../ofw-jquery"], function() {

    /** Private properties **/
    var _objects = [];			// An array of autopagination objects on this page

    /** Private API **/
    var defaultOptions = {
        // Timeout of the delayed scroll event handling
        addScrollCheckTimeout: 10000
    };

    var postOptions = {};
    var options = {};

    var dimensions = {};

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
                        addScrollCheck(category, action, label, value)
                    }, postOptions.addScrollCheckTimeout);
                }
                // track click events
                else{
                    $el.click(function(){
                        ofw.track(category, action, label, value);
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
    };

    /**
     * Add scroll event.
     */
    var addScrollCheck = function(category, action, label, value) {
        $(window).on('scroll', function() {
            var scroll_top = $(window).scrollTop();
            if (events.postScrollPercents.length && scroll_top > dimensions.postTop && scroll_top < dimensions.postTop + dimensions.postHeight) {
                // Initial post scroll percent
                var percent = 0;

                // Look through all the percents
                for (var perc_idx in events.postScrollPercents) {
                    percent = events.postScrollPercents[perc_idx];
                    if (scroll_top >= dimensions.postHeight * percent / 100) {
                        // Send GA event
                        ofw.track(category, action, label, percent);
                        // Mark as visited (remove from array)
                        delete events.postScrollPercents[perc_idx];
                    }
                }
            } else {
                $(window).unbind('scroll');
            }
        });
    };

    /** Perform private initialization **/
    init();

    // Return my external API
    return api;

});