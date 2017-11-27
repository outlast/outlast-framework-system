/**
 * Photo field editor (uploads).
 **/
define('system/js/data/field/file', ["../../ext/dropzone/dropzone-require.js", "../../jquery/jquery-ui-1.12.1/jquery-ui-sortable-standalone.min", "../../ofw-jquery"], function(Dropzone, jQueryUI) {

	Dropzone.autoDiscover = false;

    /** Properties **/
    var _dataAttributeName = 'file';
	var _popoverMarkup = "<a class='btn btn-primary' target='_blank'><span class='fa fa-download'></span></a> <a class='btn btn-danger'><span class='fa fa-trash'></span></a> <a style='margin-left: 10px;'><span class='fa fa-remove'></span></a>";

	/** Field settings **/
	var _fileFieldOptions = {};		// countLimit = the number of files this field allows (1 or 0 /unlimited/) is supported
	var _fileFieldValues = {};
	var _fileFieldUploaderObjects = {};

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
		if(typeof _fileFieldValues[fieldid] !== 'undefined'){
			// Set stringified array to input
			$('#'+fieldid).val(JSON.stringify(_fileFieldValues[fieldid]));
		}
	};


	/**
	 * Get my list element
	 * @param {string} fieldid The field identifier.
	 * @param {string} fileid The file identifier.
	 * @return {jQuery} The list jquery element.
	 */
	var getFileElement = function(fieldid, fileid) {
		return getListElement(fieldid).find('[data-file-id="'+fileid+'"]');
	};

	/**
	 * Get my list element
	 * @param {string} fieldid The field identifier.
	 * @return {jQuery} The list jquery element.
	 */
	var getListElement = function(fieldid) {
		return $('[data-file="list"]').filter('[data-file-field-id="'+fieldid+'"]');
	};

	/**
	 * Get my browse button
	 * @param {string} fieldid The field identifier.
	 * @returns {jQuery} The browse button jquery element.
	 */
	var getBrowseButton = function(fieldid) {
		return $('[data-file="uploadButton"]').filter('[data-file-field-id="'+fieldid+'"]');
	};

	/**
	 * Get file url for file id.
	 * @param {string} fileid The file id.
	 * @param {boolean} preview Set to true if preview.
	 * @returns {string} Returns the full url.
	 */
	var getFileUrl = function(fileid, preview) {
		if(preview) return ofw.baseurl+'system/api/file/preview/?id='+fileid;
		else return ofw.baseurl+'system/api/file/show/?id='+fileid;
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

		// @todo remove previews from file template

		// Initialize
		var myDropzone = new Dropzone(dropElement, {
			url: ofw.baseurl+'system/plupload/upload/file/',
			addRemoveLinks: true,
			createImageThumbnails: false,
			maxFiles: null,
			clickable: browseButton,
			// Localization
			dictDefaultMessage: "<i class='fa fa-files-o fa-3x'></i>",
			dictCancelUploadConfirmation: null,
			dictCancelUpload: "✕",
			dictRemoveFile: "<i class='fa fa-trash-o'></i>",
			// Custom localizations
			dictPreviewFile: ""//<i class='fa fa-external-link'></i>",		// Custom!

		});

		// Add callbacks
		myDropzone.on("removedfile", function(file) {
			var $previewElement = $(file.previewElement);
			dropzoneActions.onRemoveFile($previewElement.attr('data-file-field-id'), $previewElement.attr('data-file-id'));
		});
		myDropzone.on("complete", function(file) {
			dropzoneActions.onComplete(fieldid, file);
		});
		dropzoneActions.onInit(fieldid);


		// Finally, set
		_fileFieldUploaderObjects[fieldid] = myDropzone;
	};

	/**
	 * Callback functions for dropzone.js
	 */
	var dropzoneActions = {

		addFile: function(fieldid, fileid, file, preview){
			// Get file url
			var fileUrl = getFileUrl(fileid, preview);
			// Set up file with additional details
			file['id'] = fileid;
			//file['preview'] = preview;

			// Emit events
			_fileFieldUploaderObjects[fieldid].emit("addedfile", file);
			//_fileFieldUploaderObjects[fieldid].emit("thumbnail", file, fileUrl);
			_fileFieldUploaderObjects[fieldid].emit("complete", file);
		},


		removeFile: function(fieldid, fileid, file){
			// Get file url
			var $fileElement = getFileElement(fieldid, fileid);
			// Set up file with additional details
			file['id'] = fileid;
			file['previewElement'] = $fileElement[0];
			// Emit events
			_fileFieldUploaderObjects[fieldid].emit("removedfile", file);
			//_fileFieldUploaderObjects[fieldid].emit("thumbnail", file, fileUrl);
			//_fileFieldUploaderObjects[fieldid].emit("complete", file);
		},


		addFileButtons: function(fieldid, fileid, previewElement, previewMode){
			var $previewElement = $(previewElement);
			// Add data attributes
			$previewElement.attr('data-file-id', fileid);
			$previewElement.attr('data-file-field-id', fieldid);

			// Fix remove button
			var $removeButton = $previewElement.find('.dz-remove').html(_fileFieldUploaderObjects[fieldid].options.dictRemoveFile);
			// Add show link
			var $showButton = $('<a class="dz-show">'+_fileFieldUploaderObjects[fieldid].options.dictPreviewFile+'</a>')
			$showButton.insertBefore($removeButton);
			$showButton.attr('href', getFileUrl(fileid, previewMode));
			$showButton.attr('target', '_blank');
			// Add rename event
			$previewElement.find('.dz-filename').click(function(){
				var $nameElement = $(this).find('[data-dz-name]');
				var newName = prompt("Enter the new name for the file", $nameElement.text());
				if(newName){
					dropzoneActions.onRenameFile(fieldid, fileid, newName);
				}

			});

			// Add drag event
			$previewElement.on('dragend', function(){
				console.log('drag ended for '+fileid);
			});
		},

		onRenameFile: function(fieldid, fileid, newName){
			// Update UI
			var $previewElement = getFileElement(fieldid, fileid);
			var $nameElement = $previewElement.find('[data-dz-name]');
			$nameElement.text(newName);

			// Update api values
			var renameValues = api.getFieldValues(fieldid, 'rename');
			if(renameValues.length === 0) renameValues = {};
			renameValues[fileid] = newName;
			api.setFieldValues(fieldid, 'rename', renameValues);
		},

		onRemoveFile: function(fieldid, fileid){
			// Does the file currently exist in the add? If so just remove from there.
			if(api.doesValueExistInField(fieldid, 'add', fileid)){
				api.removeFieldValue(fieldid, 'add', fileid);
			}
			// Otherwise, add to removal
			else{
				api.addFieldValue(fieldid, 'remove', fileid);
			}

			// Remove it from reorder
			api.removeFieldValue(fieldid, 'order', fileid);

			// Message should not show if more than one file
			var $fileList = getListElement(fieldid);
			if($fileList.find('.dz-preview').length > 0){
				setTimeout(function(){ $fileList.addClass('dz-started'); }, 10);
			}
		},

		onReorder: function(fieldid){
			// Build array
			var fileReorderArray = [];
			getListElement(fieldid).find('[data-file-id]').each(function(){
				fileReorderArray.push($(this).attr('data-file-id'));
			});

			// Set reorder array
			api.setFieldValues(fieldid, 'order', fileReorderArray);
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
					var fileid = rJson.id;

					// Do we need to remove previous images?
					if(api.getFieldOption(fieldid, 'countLimit') === 1){
						var $fileList = getListElement(fieldid);
						$fileList.find('.dz-preview').each(function(){
							var otherPhotoId = $(this).attr('data-file-id');
							if(otherPhotoId !== fileid) api.remove(fieldid, otherPhotoId);
						});
					}

					// Add to array and to image buttons
					api.addFieldValue(fieldid, 'add', fileid);
					dropzoneActions.addFileButtons(fieldid, fileid, file.previewElement, true);

					// Trigger onreorder
					dropzoneActions.onReorder(fieldid);
				}
				else{
					_fileFieldUploaderObjects[fieldid].removeFile(file);
					ofw.alert(rJson.message);
				}

			}
			// Manual add
			else{
				// Add orientation class
				if(file.orientation) $(file.previewElement).addClass(file.orientation);
				// Add image buttons
				dropzoneActions.addFileButtons(fieldid, file.id, file.previewElement, false);
			}
		}
	};


    /** Actions **/
    var actions = {



	};

    /** Activations **/
    var activations = {

		/**
		 * Activate a list of files for reordering and for edit popups.
		 * @param dataset
		 * @param $el
		 */
    	list: function(dataset, $el){
			turnOnSortableForList(dataset.fileFieldId);
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
			if(dataset.fileFieldOptionCountLimit){
				api.setFieldOption(dataset.fileFieldId, 'countLimit', dataset.fileFieldOptionCountLimit);
			}
			else api.setFieldOption(dataset.fileFieldId, 'countLimit', 0);

			// Init my uploader
			var $listElement = getListElement(dataset.fileFieldId);
			dropzoneInit(dataset.fileFieldId, $el[0], $listElement[0]);
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
		 * Add a new file to the UI.
		 * @param {string} fieldid The field unique id.
		 * @param {string} fileid The file id.
		 * @param {File|Object} file The file object that must contain at least the name and size parameters.
		 * @param {boolean} [preview=true] If true, this is a new file and should be in preview mode. Defaults to true.
		 * @return {boolean} Will return true if added, false if not (usually when it already exists).
		 */
		add: function(fieldid, fileid, file, preview){
			// Default for preview
			if(typeof preview === 'undefined') preview = true;

			// If we only have one, then remove @todo add support for countLimit > 1
			if(api.getFieldOption(fieldid, 'countLimit') === 1){
				api.removeAll(fieldid);
			}

			// Create the file item in ui
			dropzoneActions.addFile(fieldid, fileid, file, preview);
		},

		/**
		 * Remove a file
		 * @param {string} fieldid The field unique id.
		 * @param {string} fileid The file id.
		 * @param {File|Object} [file={}] The file object that must contain at least the name and size parameters.
		 */
		remove: function(fieldid, fileid, file){
			if(typeof file === 'undefined') file = {};
			dropzoneActions.removeFile(fieldid, fileid, file);
		},

		/**
		 * Remove all existing files in the field.
		 * @param {string} fieldid The field unique id.
		 */
		removeAll: function(fieldid){
			$('[data-file-field-id="'+fieldid+'"][data-file-id]').each(function(){
				var $el = $(this);
				var fileid = $el.attr('data-file-id');
				api.remove(fieldid, fileid);
			});
		},

		/**
		 * Show file options.
		 * @param {string} fieldid The field unique id.
		 * @param {string} fileid The file id.
		 * @param {boolean} preview Set this to true if the file file needs to be a preview.
		 */
		showOptions: function(fieldid, fileid, preview){
			var $fileElement = $('[data-file-id="'+fileid+'"]');
			var url = getFileUrl(fileid, preview);
			var $popoverContent = $(_popoverMarkup);
			$fileElement.popover({toggle: 'popover', html: true, container: 'body', placement: 'top', content: $popoverContent });

			// Add preview
			$popoverContent.find('.btn-primary').attr('href', url);
			// Add trash and close events
			$popoverContent.find('.fa-remove').click(function(){
				$fileElement.popover('hide');
			});
			$popoverContent.find('.fa-trash').click(function(){
				$fileElement.popover('hide');
				api.remove(fieldid, fileid);
			});
		},

		/**
		 * Load existing file.
		 * @param {string} fieldid The field unique id.
		 * @param {string} fileid The id of the file.
		 * @param {Object|File} file The file object that must contain at least the name and size parameters.
		 */
		loadExisting: function(fieldid, fileid, file){
			api.add(fieldid, fileid, file, false);
		},

		/**
		 * Add field value and then set the input value.
		 * @param {string} fieldid The field unique id.
		 * @param {string} type The type which can be 'add', 'remove' or 'order'.
		 * @param {string} fileid A specific id.
		 */
		addFieldValue: function(fieldid, type, fileid){
			// If field value exists already, return
			if(fileid === null || api.doesValueExistInField(fieldid, type, fileid)) return;

			// Get field values
			var fieldValues = api.getFieldValues(fieldid, type);
			// Now add mine
			fieldValues.push(fileid);
			// Now set it!
			api.setFieldValues(fieldid, type, fieldValues);
        },

		/**
		 * Remove field value if it exists.
		 * @param {string} fieldid The field unique id.
		 * @param {string} type The type which can be 'add', 'remove' or 'order'.
		 * @param {string} fileid A specific id.
		 */
		removeFieldValue: function(fieldid, type, fileid){
			// If field value does not exist, return
			if(!api.doesValueExistInField(fieldid, type, fileid)) return;

			// If it does exist, remove it
			var fieldValues = api.getFieldValues(fieldid, type);
			var indexOfFieldValue = fieldValues.indexOf(fileid);
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
			if(typeof _fileFieldValues[fieldid] === 'undefined') _fileFieldValues[fieldid] = {};

			// Now set appropriate key
			_fileFieldValues[fieldid][type] = values;

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
			if(typeof _fileFieldValues[fieldid] === 'undefined') return [];
			else if(typeof _fileFieldValues[fieldid][type] === 'undefined') return [];
			else return _fileFieldValues[fieldid][type];
		},

		/**
		 * Get field value for a specific field and type.
		 * @param {string} fieldid The field unique id.
		 * @param {string} type The type which can be 'add', 'remove' or 'order'.
		 * @param {string} fileid The id of the item you are looking for.
		 * @return {boolean} Returns an array.
		 */
		doesValueExistInField: function(fieldid, type, fileid){
			var values = api.getFieldValues(fieldid, type);
			for(var i = 0; i < values.length; i++){
				if(values[i] === fileid) return true;
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
			if(typeof _fileFieldOptions[fieldid] === 'undefined') _fileFieldOptions[fieldid] = {};
			_fileFieldOptions[fieldid][key] = value;
		},

		/**
		 * Set field option.
		 * @param {string} fieldid The field unique id.
		 * @param {string} key The option key.
		 * @return {string|Number|Object|null} The option value or null if the key was not set.
		 */
		getFieldOption: function(fieldid, key){
			if(typeof _fileFieldOptions[fieldid] === 'undefined') return null;
			return _fileFieldOptions[fieldid][key];
		}
	};

	/** Perform initialization **/
    init();

    // Return my external API
    return api;

});