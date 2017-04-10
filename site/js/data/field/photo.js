/**
 * Course editing API.
 **/
define('system/js/data/field/photo', ["../../plupload/plupload.full.js", "../../jquery/jquery-ui-1.9.2/js/jquery-ui-1.9.2.custom.min.js", "../../ofw-jquery"], function(plUpload, jQueryUI) {

    /** Properties **/
    var _dataAttributeName = 'photo';
	var popoverMarkup = "<a class='btn btn-primary' target='_blank'><span class='glyphicon glyphicon-zoom-in'></span></a> <a class='btn btn-danger'><span class='glyphicon glyphicon-trash'></span></a> <a style='margin-left: 10px;'><span class='glyphicon glyphicon-remove'></span></a>";

	/** Field settings **/
	var photoFieldTemplate = {};
	var photoFieldValues = {};
	var photoFieldUploaderObjects = {};

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
		if(typeof photoFieldValues[fieldid] !== 'undefined'){
			// Set stringified array to input
			$('#'+fieldid).val(JSON.stringify(photoFieldValues[fieldid]));
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
		var $newPhotoMarkup = $(photoFieldTemplate[fieldid]).clone();

		// Set image url
		var $myImage = $newPhotoMarkup;
		if($myImage.tagName !== 'img') $myImage = $newPhotoMarkup.find('img');
		$myImage.attr('src', getPhotoUrl(photoid, preview));

		// Add photo id
		$newPhotoMarkup.attr('data-photo-id', photoid);

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
	 * Get photo url for photo id.
	 * @param {string} photoid The photo id.
	 * @param {boolean} preview Set to true if preview.
	 * @returns {string} Returns the full url.
	 */
	var getPhotoUrl = function(photoid, preview) {
		if(preview) return ofw.baseurl+'system/photo/preview/?id='+photoid;
		else return ofw.baseurl+'system/photo/show/?id='+photoid;
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
				api.setFieldValues(fieldid, 'reorder', photoReorderArray);
			}
		});
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
			photoFieldTemplate[dataset.photoFieldId] = $el;
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
			var $listElement = getListElement(dataset.photoFieldId);
			/**photoFieldUploaderObjects[dataset.photoFieldId] = new plUpload.Uploader({
				runtimes : 'html5,flash,html4',
				browse_button : $el,
				drop_element : $listElement,
				max_file_size : 100, //'{{field.options.max_file_size|escapejs}}',
				url : ofw.baseurl+'system/plupload/upload/photo/{% endblock upload_url %}',
				flash_swf_url : ofw.baseurl+'system/js/plupload/plupload.flash.swf'
			});
			photoFieldUploaderObjects[dataset.photoFieldId].init();**/
			$el.removeClass('hide');
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
			// Was the item already added?
			if(api.doesValueExistInField(fieldid, 'add', photoid)) return false;

			// Default for preview
			if(typeof preview === 'undefined') preview = true;

			// Create the photo item in data
			api.addFieldValue(fieldid, 'add', photoid);

			// Create the photo item in ui
			var $photoElement = createPhotoMarkupFromTemplate(fieldid, photoid, preview);
			getListElement(fieldid).append($photoElement);

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

			// Otherwise, add to removal


			// Does it exist in reorder, if so always remove from that

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
			var $popoverContent = $("<a class='btn btn-primary' href='"+url+"' target='_blank'><span class='glyphicon glyphicon-zoom-in'></span></a> <a class='btn btn-danger'><span class='glyphicon glyphicon-trash'></span></a> <a style='margin-left: 10px;'><span class='glyphicon glyphicon-remove'></span></a>");
			$photoElement.popover({toggle: 'popover', html: true, container: 'body', placement: 'top', content: $popoverContent });

			// @todo add events for trash and close
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
		 * @param {string} type The type which can be 'add', 'remove' or 'reorder'.
		 * @param {string} photoid A specific id.
		 */
		addFieldValue: function(fieldid, type, photoid){
			// Get field values
			var fieldValues = api.getFieldValues(fieldid, type);
			// Now add mine
			fieldValues.push(photoid);
			// Now set it!
			api.setFieldValues(fieldid, type, fieldValues);
        },

		/**
		 * Set field value and then set the input value.
		 * @param {string} fieldid The field unique id.
		 * @param {string} type The type which can be 'add', 'remove' or 'reorder'.
		 * @param {Array} values An array of ids.
		 */
		setFieldValues: function(fieldid, type, values){
			// If the type does not yet exist
			if(typeof photoFieldValues[fieldid] === 'undefined') photoFieldValues[fieldid] = {};

			// Now set appropriate key
			photoFieldValues[fieldid][type] = values;

			// Set input
			setFieldInput(fieldid);
        },

		/**
		 * Get field value for a specific field and type.
		 * @param {string} fieldid The field unique id.
		 * @param {string} type The type which can be 'add', 'remove' or 'reorder'.
		 * @return {Array} Returns an array.
		 */
		getFieldValues: function(fieldid, type){
			if(typeof photoFieldValues[fieldid] === 'undefined') return [];
			else if(typeof photoFieldValues[fieldid][type] === 'undefined') return [];
			else return photoFieldValues[fieldid][type];
		},

		/**
		 * Get field value for a specific field and type.
		 * @param {string} fieldid The field unique id.
		 * @param {string} type The type which can be 'add', 'remove' or 'reorder'.
		 * @param {string} photoid The id of the item you are looking for.
		 * @return {boolean} Returns an array.
		 */
		doesValueExistInField: function(fieldid, type, photoid){
			var values = api.getFieldValues(fieldid, type);
			for(var i = 0; i < values.length; i++){
				if(values[i] === photoid) return true;
			}
			return false;
		}

	};

	/** Perform initialization **/
    init();

    // Return my external API
    return api;

});

	/**
	var photoHandler = {};

	photoHandler.addMyPhotoPopover = function(el, url){
		var popover_markup = "<a class='btn btn-primary' href='"+url+"' target='_blank'><span class='glyphicon glyphicon-zoom-in'></span></a> <a class='btn btn-danger' data-photo='"+el[0].id+"' onclick='upload_remove_{{field.uid}}(event);'><span data-photo='"+el[0].id+"' class='glyphicon glyphicon-trash'></span></a> <a style='margin-left: 10px;' data-photo='"+el[0].id+"' onclick='photoHandler.closeMyPhotoPopover(event)'><span data-photo='"+el[0].id+"' class='glyphicon glyphicon-remove'></span></a>";

		el.popover({
			toggle: 'popover',
			html: true,
			container: 'body',
			placement: 'top',
			content: popover_markup
		});

		// Hide all other popovers
		el.on('show.bs.popover', function() {
			$('.popover').popover('hide');
		});
	};
	photoHandler.closeMyPhotoPopover = function(ev){
		$('#'+$(ev.target).attr('data-photo')).popover('hide');
	};
	zaj.ready(function(){
		// Make sortable
			$('#{{field.uid}}-filelist').sortable({
			    start: function(event, ui) {
			    	ui.item.addClass('draginprogress');
			    },
			    stop: function(event, ui) {
			    	ui.item.removeClass('draginprogress');
					// Build array
						var my_array = [];
						$('#{{field.uid}}-filelist').children('div').each(function(){
							my_array.push(this.id);
						});
						file_upload_changes_{{field.uid}}.order = my_array;
						$('#{{field.uid}}').val(JSON.stringify(file_upload_changes_{{field.uid}}));
			    }
			});

	})**/

/**
	var file_upload_changes_{{field.uid}} = {add: [], remove: [], order: [] };

		// create objects
			var uploader_{{field.uid}} = new plupload.Uploader({
				runtimes : 'html5,flash,html4',
				browse_button : '{{field.uid}}-pickfiles',
				drop_element : '{{field.uid}}-filelist',
				max_file_size : '{{field.options.max_file_size|escapejs}}',
				url : '{% block upload_url %}{{baseurl}}system/plupload/upload/photo/{% endblock upload_url %}',
				flash_swf_url : '{{baseurl}}system/js/plupload/plupload.flash.swf'
			});
			var uploadergo_{{field.uid}} = function(){
				uploader_{{field.uid}}.start()
			};

			uploader_{{field.uid}}.bind('Init', function(up, params){
				//console.log("Uploader initialized. Runtime: " + params.runtime);
			});
			uploader_{{field.uid}}.bind('FilesAdded', function(up, files) {
				setTimeout("uploadergo_{{field.uid}}()", 800);
			});
			uploader_{{field.uid}}.bind('UploadProgress', function(up, file) {
				$('#{{field.uid}}-transferbar div').css('width', file.percent + "%")
				$('#{{field.uid}}-transferbar').removeClass('hidden');
			});
			uploader_{{field.uid}}.bind('Error', function(up, err) {
				zaj.alert("{{#system_field_file_upload_error#|printf:field.options.max_file_size|escapejs}}");
				up.refresh(); // Reposition Flash/Silverlight
			});
			uploader_{{field.uid}}.bind('FileUploaded', function(up, file, result) {
				setTimeout(function() { $('#{{field.uid}}-transferbar').addClass('hidden'); }, 1000);
				var res = jQuery.parseJSON(result.response);
				// check for error
					if(res.status == 'error'){
						zaj.alert(res.message);
					}
					else{
						uploader_update_{{field.uid}}(res);
					}
			});


			uploader_update_{{field.uid}} = function(res){
				// Is it wide/tall enough?
					if(res.height < {{field.options.min_height|escapejs}} || res.width < {{field.options.min_width|escapejs}}) return alert("{{#system_field_picture_too_small#|printf:field.options.min_width|printf:field.options.min_height|escapejs}}");
					{% if field.options.max_height %}
					// check for height, if is > 0;
						var max_height = {{field.options.max_height|escapejs}} + 0;
						if(max_height && res.height > max_height) return alert("{{#system_field_picture_too_high#|printf:field.options.max_height|escapejs}}");
					{% endif %}
					{% if field.options.max_width %}
					// check for width, if is > 0;
						if(max_width && res.width > max_width) return alert("{{#system_field_picture_too_wide#|printf:field.options.max_width|escapejs}}");
						var max_width = {{field.options.max_width|escapejs}} + 0;
					{% endif %}

				// Add my image
					var imgurl = '{{baseurl}}system/plupload/preview/?id='+res.id;
					var el = $("<div class='col-sm-2' id='"+res.id+"' style='position: relative; cursor: pointer;'><img class='pull-left img-thumbnail img-responsive' src='"+imgurl+"'></div>");
					$('#{{field.uid}}-filelist').append(el);
					photoHandler.addMyPhotoPopover(el, imgurl);
				// Set as my input value
					file_upload_changes_{{field.uid}}.add.push(res.id);
					$('#{{field.uid}}').val(JSON.stringify(file_upload_changes_{{field.uid}}));
				return res.id;
			};

			upload_remove_{{field.uid}} = function(ev){
				var id = $(ev.target).attr('data-photo');
				var $photodiv = $('#'+id);
				// Turn off my popover
					$photodiv.popover('hide');
				// Remove my div
					$photodiv.remove();
				// Remove from add value
					file_upload_changes_{{field.uid}}.add = jQuery.grep(file_upload_changes_{{field.uid}}.add, function(value) { return value != id; });
				// Update my remove value
					file_upload_changes_{{field.uid}}.remove.push(id);
                // Set input
					$('#{{field.uid}}').val(JSON.stringify(file_upload_changes_{{field.uid}}));
			};

		// Run init
			uploader_{{field.uid}}.init();
			**/