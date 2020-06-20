/**
 * Photo field editor (uploads).
 **/
define('system/js/data/field/photo', ["../../ext/dropzone/dropzone-require.js", "../../jquery/jquery-ui-1.12.1/jquery-ui-sortable-standalone.min", "../../ofw-jquery"], function(Dropzone, jQueryUI) {

	Dropzone.autoDiscover = false;

    /** Properties **/
    var _dataAttributeName = 'photo';
	var _popoverMarkup = "<a class='btn btn-primary' target='_blank'><span class='fa fa-search-plus'></span></a> <a class='btn btn-danger'><span class='fa fa-trash'></span></a> <a style='margin-left: 10px;'><span class='fa fa-remove'></span></a>";

	/** Field settings **/
	var _photoFieldTemplate = {};
	var _photoFieldOptions = {};		// countLimit = the number of photos this field allows (1 or 0 /unlimited/) is supported
	var _photoFieldValues = {};
	var _photoFieldUploaderObjects = {};

	/** The timeout variable **/
	var _setFieldInputTriggerEventTimeout;

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
			let $field = $('#'+fieldid);
			$field.val(JSON.stringify(_photoFieldValues[fieldid]));

			// Trigger change with a bit of delay to filter out duplicate events
			clearTimeout(_setFieldInputTriggerEventTimeout)
			_setFieldInputTriggerEventTimeout = setTimeout(function(){
				$field.trigger('change');
			}, 500);
		}
	};

	/**
	 * Get my list element
	 * @param {string} fieldid The field identifier.
	 * @param {string} photoid The photo identifier.
	 * @return {jQuery} The list jquery element.
	 */
	var getPhotoElement = function(fieldid, photoid) {
		return getListElement(fieldid).find('[data-photo-id="'+photoid+'"]');
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
	 * Get my browse button
	 * @param {string} fieldid The field identifier.
	 * @returns {jQuery} The browse button jquery element.
	 */
	var getBrowseButton = function(fieldid) {
		return $('[data-photo="uploadButton"]').filter('[data-photo-field-id="'+fieldid+'"]');
	};

	/**
	 * Get photo url for photo id.
	 * @param {string} photoid The photo id.
	 * @param {boolean} preview Set to true if preview.
	 * @returns {string} Returns the full url.
	 */
	var getPhotoUrl = function(photoid, preview) {
		if(preview) return ofw.baseurl+'system/api/photo/preview/?id='+photoid+'&size=full';
		else return ofw.baseurl+'system/api/photo/show/?id='+photoid+'&size=full';
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
				dropzoneActions.onReorder(fieldid);
			}
		});
	};

	/**
	 * Init the uploader javascript.
	 * @param {string} fieldid The field unique id.
	 * @param {Object} browseButton The DOM object of the browse button.
	 * @param {Object} dropElement The DOM object of the drop element.
	 */
	var dropzoneInit = function(fieldid, browseButton, dropElement) {
		// Initialize
		var myDropzone = new Dropzone(dropElement, {
			url: ofw.baseurl+'system/plupload/upload/photo/',
			addRemoveLinks: true,
			maxFiles: null,
			clickable: browseButton,
			// Localization
			dictDefaultMessage: "<i class='fa fa-files-o fa-3x'></i>",
			dictCancelUploadConfirmation: null,
			dictCancelUpload: "✕",
			dictRemoveFile: "<i class='fa fa-trash-o'></i>",
			// Custom localizations
			dictPreviewFile: "<i class='fa fa-external-link'></i>",		// Custom!

		});

		// Add callbacks
		myDropzone.on("removedfile", function(file) {
			var $previewElement = $(file.previewElement);
			dropzoneActions.onRemoveImage($previewElement.attr('data-photo-field-id'), $previewElement.attr('data-photo-id'));
		});
		myDropzone.on("complete", function(file) {
			dropzoneActions.onComplete(fieldid, file);
		});
		dropzoneActions.onInit(fieldid);

		// Finally, set
		_photoFieldUploaderObjects[fieldid] = myDropzone;
	};

	/**
	 * Callback functions for dropzone.js
	 */
	var dropzoneActions = {

		addImage: function(fieldid, photoid, file, preview){
			// Get photo url
			var photoUrl = getPhotoUrl(photoid, preview);
			// Set up file with additional details
			file['id'] = photoid;
			file['preview'] = preview;

			// Emit events
			_photoFieldUploaderObjects[fieldid].emit("addedfile", file);
			_photoFieldUploaderObjects[fieldid].emit("thumbnail", file, photoUrl);
			_photoFieldUploaderObjects[fieldid].emit("complete", file);
		},

		removeImage: function(fieldid, photoid, file){
			// Get photo url
			var $photoElement = getPhotoElement(fieldid, photoid);
			// Set up file with additional details
			file['id'] = photoid;
			file['previewElement'] = $photoElement[0];
			// Emit events
			_photoFieldUploaderObjects[fieldid].emit("removedfile", file);
		},


		addImageButtons: function(fieldid, photoid, previewElement, previewMode){
			var $previewElement = $(previewElement);
			// Add data attributes
			$previewElement.attr('data-photo-id', photoid);
			$previewElement.attr('data-photo-field-id', fieldid);

			// Fix remove button
			var $removeButton = $previewElement.find('.dz-remove').html(_photoFieldUploaderObjects[fieldid].options.dictRemoveFile);
			// Add show link
			var $showButton = $('<a class="dz-show">'+_photoFieldUploaderObjects[fieldid].options.dictPreviewFile+'</a>')
			$showButton.insertBefore($removeButton);
			$showButton.attr('href', getPhotoUrl(photoid, previewMode));
			$showButton.attr('target', '_blank');
			// Add rename event
			$previewElement.find('.dz-filename').click(function(){
				var $nameElement = $(this).find('[data-dz-name]');
				var newName = prompt("Enter the new name for the file", $nameElement.text());
				if(newName){
					dropzoneActions.onRenameImage(fieldid, photoid, newName);
				}

			});

			// Add drag event
			$previewElement.on('dragend', function(){
				console.log('drag ended for '+photoid);
			});
		},

		onRenameImage: function(fieldid, photoid, newName){
			// Update UI
			var $previewElement = getPhotoElement(fieldid, photoid);
			var $nameElement = $previewElement.find('[data-dz-name]');
			$nameElement.text(newName);

			// Update api values
			var renameValues = api.getFieldValues(fieldid, 'rename');
			if(renameValues.length === 0) renameValues = {};
			renameValues[photoid] = newName;
			api.setFieldValues(fieldid, 'rename', renameValues);
		},

		onRemoveImage: function(fieldid, photoid){
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

			// Message should not show if more than one photo
			var $fileList = getListElement(fieldid);
			if($fileList.find('.dz-preview').length > 0){
				setTimeout(function(){ $fileList.addClass('dz-started'); }, 10);
			}
		},

		onReorder: function(fieldid){
			// Build array
			var photoReorderArray = [];
			getListElement(fieldid).find('[data-photo-id]').each(function(){
				photoReorderArray.push($(this).attr('data-photo-id'));
			});

			// Set reorder array
			api.setFieldValues(fieldid, 'order', photoReorderArray);
		},

		onInit: function(fieldid){
			var $browseButton = getBrowseButton(fieldid);
			$browseButton.removeClass('hide');
		},

		/**
		 * Called after a file is added. Could be manual adding or after upload.
		 */
		onComplete: function(fieldid, file){
			// If completed from an upload, then verify the file
			if(file.xhr){
				var rJson = JSON.parse(file.xhr.response);
				if(rJson.status === 'success' || rJson.status === 'ok'){
					var photoid = rJson.id;

					// Do we need to remove previous images?
					if(api.getFieldOption(fieldid, 'countLimit') === 1){
						var $fileList = getListElement(fieldid);
						$fileList.find('.dz-preview').each(function(){
							var otherPhotoId = $(this).attr('data-photo-id');
							if(otherPhotoId !== photoid) api.remove(fieldid, otherPhotoId);
						});
					}

					// Add to array and to image buttons
					api.addFieldValue(fieldid, 'add', photoid);
					dropzoneActions.addImageButtons(fieldid, photoid, file.previewElement, true);

					// Trigger onreorder
					dropzoneActions.onReorder(fieldid);
				}
				else{
					_photoFieldUploaderObjects[fieldid].removeFile(file);
					ofw.alert(rJson.message);
				}

			}
			// Manual add
			else{
				// Add orientation class
				if(file.orientation) $(file.previewElement).addClass(file.orientation);
				// Add image buttons
				dropzoneActions.addImageButtons(fieldid, file.id, file.previewElement, false);
			}
		}
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
			dropzoneInit(dataset.photoFieldId, $el[0], $listElement[0]);
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
		 * Add a new photo to the UI.
		 * @param {string} fieldid The field unique id.
		 * @param {string} photoid The photo id.
		 * @param {File|Object} file The file object that must contain at least the name and size parameters.
		 * @param {boolean} [preview=true] If true, this is a new photo and should be in preview mode. Defaults to true.
		 * @return {boolean} Will return true if added, false if not (usually when it already exists).
		 */
		add: function(fieldid, photoid, file, preview){
			// Assuming this photo already exists, just ignore the add
			if(getPhotoElement(fieldid, photoid).length > 0){
				return false;
			}

			// Default for preview
			if(typeof preview === 'undefined') preview = true;

			// If we only have one, then remove @todo add support for countLimit > 1
			if(api.getFieldOption(fieldid, 'countLimit') === 1){
				api.removeAll(fieldid);
			}

			// Create the photo item in ui
			dropzoneActions.addImage(fieldid, photoid, file, preview);
			return true;
		},

		/**
		 * Remove a photo
		 * @param {string} fieldid The field unique id.
		 * @param {string} photoid The photo id.
		 * @param {File|Object} [file={}] The file object that must contain at least the name and size parameters.
		 */
		remove: function(fieldid, photoid, file){
			if(typeof file === 'undefined') file = {};
			dropzoneActions.removeImage(fieldid, photoid, file);
		},

		/**
		 * Remove all existing files in the field.
		 * @param {string} fieldid The field unique id.
		 */
		removeAll: function(fieldid){
			$('[data-photo-field-id="'+fieldid+'"][data-photo-id]').each(function(){
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
		 * @param {Object|File} file The file object that must contain at least the name and size parameters.
		 */
		loadExisting: function(fieldid, photoid, file){
			api.add(fieldid, photoid, file, false);
		},

		/**
		 * Add field value and then set the input value.
		 * @param {string} fieldid The field unique id.
		 * @param {string} type The type which can be 'add', 'remove' or 'order'.
		 * @param {string} photoid A specific id.
		 */
		addFieldValue: function(fieldid, type, photoid){
			// If field value exists already, return
			if(photoid === null || api.doesValueExistInField(fieldid, type, photoid)) return;

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
		},

		/**
		 * Reset values and options for field id.
		 * @param {string} fieldid The field unique id.
		 */
		resetValuesAndOptions: function(fieldid) {
			_photoFieldOptions[fieldid] = {};
			_photoFieldValues[fieldid] = {};
		}
	};

	/** Perform initialization **/
    init();

    // Return my external API
    return api;

});