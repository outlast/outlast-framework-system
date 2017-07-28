/**
 * Course editing API.
 **/
define('system/js/data/field/photo', ["../../plupload/plupload.full.min.js", "../../jquery/jquery-ui-1.9.2/js/jquery-ui-1.9.2.custom.min.js", "../../ofw-jquery"], function(plUpload, jQueryUI) {

    /** Properties **/
    var _dataAttributeName = 'photo';
	var _popoverMarkup = "<a class='btn btn-primary' target='_blank'><span class='fa fa-search-plus'></span></a> <a class='btn btn-danger'><span class='fa fa-trash'></span></a> <a style='margin-left: 10px;'><span class='fa fa-remove'></span></a>";

	/** Field settings **/
	var _photoFieldTemplate = {};
	var _photoFieldOptions = {};		// countLimit = the number of photos this field allows (1 or 0 /unlimited/) is supported
	var _photoFieldValues = {};
	var _photoFieldUploaderObjects = {};

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
	 */
	var setFieldInput = function(fieldid) {
		if(typeof _photoFieldValues[fieldid] !== 'undefined'){
			// Set stringified array to input
			$('#'+fieldid).val(JSON.stringify(_photoFieldValues[fieldid]));
		}
	};

	/**
	 * Create photo field from template.
	 * @param {string} fieldid The field identifier.
	 * @param {string} photoid The photo identifier.
	 * @param {boolean} preview Set this to true if the photo file needs to be a preview.
	 * @return {jQuery} Returns the item's jQuery element.
	 */
	var createPhotoMarkupFromTemplate = function(fieldid, photoid, preview) {
		var $newPhotoMarkup = $(_photoFieldTemplate[fieldid]).clone();

		// Set image url
		var $myImage = $newPhotoMarkup;
		if($myImage.tagName !== 'img') $myImage = $newPhotoMarkup.find('img');
		$myImage.attr('src', getPhotoUrl(photoid, preview));

		// Add photo id and remove default markups
		$newPhotoMarkup.attr('data-photo-id', photoid);
		$newPhotoMarkup.removeAttr('data-photo');

		// Add events
		$myImage.click(function(){
			api.showOptions(fieldid, photoid, preview);
		});

		// Remove hidden aspect
		$newPhotoMarkup.removeClass('hide');

		// Now return it!
		return $newPhotoMarkup;
	};

	/**
	 * Get my list element
	 * @param {string} fieldid The field identifier.
	 * @return {jQuery} The list jquery element.
	 */
	var getListElement = function(fieldid) {
		return $('[data-photo="list"]').filter('[data-photo-field-id="'+fieldid+'"]');
	};

	/**
	 * Get a specific foto within a field.
	 * @param {string} fieldid The field identifier.
	 * @param {string} photoid The photo identifier.
	 * @return {jQuery} The list jquery element.
	 */
	var getPhotoElement = function(fieldid, photoid) {
		return getListElement(fieldid).find("[data-photo-id='"+photoid+"']");
	};

	/**
	 * Get my browse button
	 * @param {string} fieldid The field identifier.
	 * @returns {jQuery} The browse button jquery element.
	 */
	var getBrowseButton = function(fieldid) {
		return $('[data-photo="uploadButton"]').filter('[data-photo-field-id="'+fieldid+'"');
	};

	/**
	 * Get my progress bar.
	 * @param {string} fieldid The field identifier.
	 * @returns {jQuery} The progress bar jquery element.
	 */
	var getProgressBar = function(fieldid) {
		return $('[data-photo="uploadProgressBar"]').filter('[data-photo-field-id="'+fieldid+'"');
	};

	/**
	 * Get photo url for photo id.
	 * @param {string} photoid The photo id.
	 * @param {boolean} preview Set to true if preview.
	 * @returns {string} Returns the full url.
	 */
	var getPhotoUrl = function(photoid, preview) {
		if(preview) return ofw.baseurl+'system/api/photo/preview/?id='+photoid;
		else return ofw.baseurl+'system/api/photo/show/?id='+photoid;
	};

	/**
	 * Activate sortable for id.
	 * @param {string} fieldid The field identifier.
	 * @return {jQuery} The jQuery sortable object.
	 */
	var turnOnSortableForList = function(fieldid) {
		var $el = getListElement(fieldid);
		return $el.sortable({
			start: function(event, ui) {
				ui.item.addClass('draginprogress');
			},
			stop: function(event, ui) {
				ui.item.removeClass('draginprogress');

				// Build array
				var photoReorderArray = [];
				$el.find('[data-photo-id]').each(function(){
					photoReorderArray.push($(this).attr('data-photo-id'));
				});

				// Set reorder array
				api.setFieldValues(fieldid, 'order', photoReorderArray);
			}
		});
	};

	/**
	 * Init the uploader javascript.
	 * @param {string} fieldid The field unique id.
	 * @param {Object} browseButton The DOM object of the browse button.
	 * @param {Object} dropElement The DOM object of the drop element.
	 */
	var pluploadInit = function(fieldid, browseButton, dropElement) {
		// Initialize
		var myPlupload = new plupload.Uploader({
			runtimes : 'html5,flash,html4',
			browse_button : browseButton,
			drop_element : dropElement,
			//max_file_size : 100, //'{{field.options.max_file_size|escapejs}}',
			url : ofw.baseurl+'system/plupload/upload/photo/',
			flash_swf_url : ofw.baseurl+'system/js/plupload/plupload.flash.swf'
		});
		myPlupload.init();
		alert(api.getFieldOption(fieldid, 'countLimit'));

		// Add callbacks
		myPlupload.bind('Init', function(up, params){ pluploadOnInit(fieldid, up, params) });
		myPlupload.bind('FilesAdded', function(up, files){ pluploadOnFilesAdded(fieldid, up, files) });
		myPlupload.bind('UploadProgress', function(up, file){ pluploadOnUploadProgress(fieldid, up, file) });
		myPlupload.bind('Error', function(up, error){ pluploadOnError(fieldid, up, error) });
		myPlupload.bind('FileUploaded', function(up, file, result){ pluploadOnFileUploaded(fieldid, up, file, result) });

		// Set to global var
		_photoFieldUploaderObjects[fieldid] = myPlupload;
	};

	/**
	 * Called after the plupload was initialized.
	 */
	var pluploadOnInit = function(fieldid, up, params){
		var $browseButton = getBrowseButton(fieldid);
		$browseButton.removeClass('hide');
	};

	/**
	 * Called when files were added.
	 */
	var pluploadOnFilesAdded = function(fieldid, up, files){
		setTimeout(function(){
			up.start();
			up.refresh();	// Reposition Flash/Silverlight
		}, 800);
	};

	/**
	 * Called when upload progress is updated.
	 */
	var pluploadOnUploadProgress = function(fieldid, up, file) {
		var $progressBar = getProgressBar(fieldid);
		$progressBar.find('div').css('width', file.percent + "%");
		$progressBar.removeClass('hide');
	};

	/**
	 * Called when the upload has finished.
	 */
	var pluploadOnFileUploaded = function(fieldid, up, file, result) {
		// Hide the progress bar after a bit of delay
		setTimeout(function() {
			getProgressBar(fieldid).addClass('hide');
		}, 1000);

		// Parse results and update accordingly
		var rJson = JSON.parse(result.response);
		if(rJson.status === 'error'){
			ofw.alert(rJson.message);
		}
		else{
			// @todo check width and height
			api.add(fieldid, rJson.id, true);
		}
		up.refresh();	// Reposition Flash/Silverlight
	};


	/**
	 * Called when upload ends in error.
	 */
	var pluploadOnError = function(fieldid, up, error) {
		ofw.alert("An error has occurred.");
		console.log(error);
		up.refresh();	// Reposition Flash/Silverlight
	};


    /** Actions **/
    var actions = {



	};

    /** Activations **/
    var activations = {

		/**
		 * Activate the template for uploading.
		 * @param dataset
		 * @param $el
		 */
		template: function(dataset, $el){
			// Set my element
			_photoFieldTemplate[dataset.photoFieldId] = $el;
    	},

		/**
		 * Activate a list of photos for reordering and for edit popups.
		 * @param dataset
		 * @param $el
		 */
    	list: function(dataset, $el){
			turnOnSortableForList(dataset.photoFieldId);
    	},

		/**
		 * Loader deactivation.
		 */
		loader: function(dataset, $el){
			$el.addClass('hide');
		},

		/**
		 * Upload button activation.
		 */
		uploadButton: function(dataset, $el){
			// Set countLimit
			if(dataset.photoFieldOptionCountLimit){
				api.setFieldOption(dataset.photoFieldId, 'countLimit', dataset.photoFieldOptionCountLimit);
			}
			else api.setFieldOption(dataset.photoFieldId, 'countLimit', 0);

			// Init my uploader
			var $listElement = getListElement(dataset.photoFieldId);
			pluploadInit(dataset.photoFieldId, $el[0], $listElement[0]);
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
		 * Add a new photo.
		 * @param {string} fieldid The field unique id.
		 * @param {string} photoid The photo id.
		 * @param {boolean} [preview=true] If true, this is a new photo and should be in preview mode. Defaults to true.
		 * @return {boolean} Will return true if added, false if not (usually when it already exists).
		 */
		add: function(fieldid, photoid, preview){
			// Default for preview
			if(typeof preview === 'undefined') preview = true;

			// If we only have one, then remove @todo add support for countLimit > 1
			if(api.getFieldOption(fieldid, 'countLimit') === 1){
				api.removeAll(fieldid);
			}

			// Create the photo item in data (but only for new photos - where preview is on)
			if(preview === true) api.addFieldValue(fieldid, 'add', photoid);

			// Create the photo item in ui
			var $photoElement = createPhotoMarkupFromTemplate(fieldid, photoid, preview);
			if(preview === true) getListElement(fieldid).prepend($photoElement);
			else getListElement(fieldid).append($photoElement);

			// Activate sortable
			turnOnSortableForList(fieldid);
		},

		/**
		 * Remove a photo
		 * @param {string} fieldid The field unique id.
		 * @param {string} photoid The photo id.
		 */
		remove: function(fieldid, photoid){
			// Does the photo currently exist in the add? If so just remove from there.
			if(api.doesValueExistInField(fieldid, 'add', photoid)){
				api.removeFieldValue(fieldid, 'add', photoid);
			}
			// Otherwise, add to removal
			else{
				api.addFieldValue(fieldid, 'remove', photoid);
			}

			// Remove it from reorder
			api.removeFieldValue(fieldid, 'order', photoid);

			// Destroy the markup
			var $photoElement = getPhotoElement(fieldid, photoid);
			$photoElement.remove();

			// Activate sortable
			turnOnSortableForList(fieldid);
		},

		/**
		 * Remove all existing files in the field.
		 * @param {string} fieldid The field unique id.
		 */
		removeAll: function(fieldid){
			$('[data-photo-field-id="'+fieldid+'"]').each(function(){
				var $el = $(this);
				var photoid = $el.attr('data-photo-id');
				api.remove(fieldid, photoid);
			});
		},

		/**
		 * Show photo options.
		 * @param {string} fieldid The field unique id.
		 * @param {string} photoid The photo id.
		 * @param {boolean} preview Set this to true if the photo file needs to be a preview.
		 */
		showOptions: function(fieldid, photoid, preview){
			var $photoElement = $('[data-photo-id="'+photoid+'"]');
			var url = getPhotoUrl(photoid, preview);
			var $popoverContent = $(_popoverMarkup);
			$photoElement.popover({toggle: 'popover', html: true, container: 'body', placement: 'top', content: $popoverContent });

			// Add preview
			$popoverContent.find('.btn-primary').attr('href', url);
			// Add trash and close events
			$popoverContent.find('.fa-remove').click(function(){
				$photoElement.popover('hide');
			});
			$popoverContent.find('.fa-trash').click(function(){
				$photoElement.popover('hide');
				api.remove(fieldid, photoid);
			});
		},

		/**
		 * Load existing photo.
		 * @param {string} fieldid The field unique id.
		 * @param {string} photoid The id of the photo.
		 */
		loadExisting: function(fieldid, photoid){
			api.add(fieldid, photoid, false);
		},

		/**
		 * Add field value and then set the input value.
		 * @param {string} fieldid The field unique id.
		 * @param {string} type The type which can be 'add', 'remove' or 'order'.
		 * @param {string} photoid A specific id.
		 */
		addFieldValue: function(fieldid, type, photoid){
			// If field value exists already, return
			if(api.doesValueExistInField(fieldid, type, photoid)) return;

			// Get field values
			var fieldValues = api.getFieldValues(fieldid, type);
			// Now add mine
			fieldValues.push(photoid);
			// Now set it!
			api.setFieldValues(fieldid, type, fieldValues);
        },

		/**
		 * Remove field value if it exists.
		 * @param {string} fieldid The field unique id.
		 * @param {string} type The type which can be 'add', 'remove' or 'order'.
		 * @param {string} photoid A specific id.
		 */
		removeFieldValue: function(fieldid, type, photoid){
			// If field value does not exist, return
			if(!api.doesValueExistInField(fieldid, type, photoid)) return;

			// If it does exist, remove it
			var fieldValues = api.getFieldValues(fieldid, type);
			var indexOfFieldValue = fieldValues.indexOf(photoid);
			fieldValues.splice(indexOfFieldValue, 1);

			// Now set it!
			api.setFieldValues(fieldid, type, fieldValues);
        },

		/**
		 * Set field value and then set the input value.
		 * @param {string} fieldid The field unique id.
		 * @param {string} type The type which can be 'add', 'remove' or 'order'.
		 * @param {Array} values An array of ids.
		 */
		setFieldValues: function(fieldid, type, values){
			// If the type does not yet exist
			if(typeof _photoFieldValues[fieldid] === 'undefined') _photoFieldValues[fieldid] = {};

			// Now set appropriate key
			_photoFieldValues[fieldid][type] = values;

			// Set input
			setFieldInput(fieldid);
        },

		/**
		 * Get field value for a specific field and type.
		 * @param {string} fieldid The field unique id.
		 * @param {string} type The type which can be 'add', 'remove' or 'order'.
		 * @return {Array} Returns an array.
		 */
		getFieldValues: function(fieldid, type){
			if(typeof _photoFieldValues[fieldid] === 'undefined') return [];
			else if(typeof _photoFieldValues[fieldid][type] === 'undefined') return [];
			else return _photoFieldValues[fieldid][type];
		},

		/**
		 * Get field value for a specific field and type.
		 * @param {string} fieldid The field unique id.
		 * @param {string} type The type which can be 'add', 'remove' or 'order'.
		 * @param {string} photoid The id of the item you are looking for.
		 * @return {boolean} Returns an array.
		 */
		doesValueExistInField: function(fieldid, type, photoid){
			var values = api.getFieldValues(fieldid, type);
			for(var i = 0; i < values.length; i++){
				if(values[i] === photoid) return true;
			}
			return false;
		},

		/**
		 * Set field option.
		 * @param {string} fieldid The field unique id.
		 * @param {string} key The option key.
		 * @param {string|Number|Object|null} value The option value.
		 */
		setFieldOption: function(fieldid, key, value){
			if(typeof _photoFieldOptions[fieldid] === 'undefined') _photoFieldOptions[fieldid] = {};
			_photoFieldOptions[fieldid][key] = value;
		},

		/**
		 * Set field option.
		 * @param {string} fieldid The field unique id.
		 * @param {string} key The option key.
		 * @return {string|Number|Object|null} The option value or null if the key was not set.
		 */
		getFieldOption: function(fieldid, key){
			if(typeof _photoFieldOptions[fieldid] === 'undefined') return null;
			return _photoFieldOptions[fieldid][key];
		}
	};

	/** Perform initialization **/
    init();

    // Return my external API
    return api;

});