/**
 * Define this data attribute.
 **/
define('system/js/data/category', ["../ofw-jquery", "../../../plugins/outlast/js/jquery.nestable"], function () {

	/** Private properties **/
	let _dataAttributeName = 'category';

	/** Private API **/

	/** Object init */
	let init = function () {
	};

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
		if (typeof activations[actionName] == 'function' && activationTriggered != 'yes') {
			$element.attr('data-' + _dataAttributeName + '-activation-was-triggered', 'yes');
			activations[actionName](this.dataset, $element);
		}
	};

	/**
	 * Toggle related checkboxes.
	 */
	let _toggleRelated = function(categoryId) {
		let newValue = $('input[value="'+categoryId+'"]')[0].checked;
		let $myCategory = $('[data-category="toggleCategory"][data-category-id="'+categoryId+'"]');
		if(newValue) {
			// If I am checked, also toggle my parent categories
			let $myParentCategory = $myCategory.parents('[data-category="toggleCategory"]').first();
			let $myParentCategoryInput = $myParentCategory.find('input').first();
			if($myParentCategoryInput.length > 0) {
				$myParentCategoryInput[0].checked = true;
				_toggleRelated($myParentCategoryInput.val());
			}
		} else {
			// If I am not checked, then also uncheck my child categories
			let $myChildCategory = $myCategory.find('[data-category="toggleCategory"]').first();
			let childCategoryId = $myChildCategory.attr('data-category-id');
			let $myChildCategoryInput = $myChildCategory.find('input[value="'+childCategoryId+'"]').first();
			if($myChildCategoryInput.length > 0) {
				$myChildCategoryInput[0].checked = false;
				_toggleRelated(childCategoryId);
			}
		}
	};


	/**
	 * Uncheck
	 */

	/** Actions **/
	let actions = {

	};

	/** Activations **/
	let activations = {

		/**
		 * Init nestable.
		 */
		nestable: function (dataset, $el) {
			$el.nestable({enableDragAndDrop: false});
			$el.nestable('collapseAll');
		},

		/**
		 * Stop propogation on the checkbox.
		 */
		toggleCategory: function (dataset, $el) {
			let categoryId = dataset.categoryId;
			let $input = $el.find('input[value="'+categoryId+'"]');
			$input.on("change", function (ev) {
				_toggleRelated(categoryId);
			});
		}


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
		 * Add the selected categories.
		 */
		addSelected: function(categoryId) {
			let $myCategory = $('[data-category="toggleCategory"][data-category-id="'+categoryId+'"]');
			let $myInput = $myCategory.find('input');
			$myInput[0].checked = true;
			_toggleRelated(categoryId);
		},

	};

	/** Perform initialization **/
	init();

	// Return my external API
	return api;

});
