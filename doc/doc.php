<?php
/**
 * This file helps IDEs autocomplete stuff within this plugin. It is never actually used.
 **/
die("This file is for documentation.");

/**
 * @method static zajDb boolean
 * @method static zajDb category
 * @method static zajDb categories
 * @method static zajDb color
 * @method static zajDb date
 * @method static zajDb email
 * @method static zajDb file
 * @method static zajDb files
 * @method static zajDb float
 * @method static zajDb id
 * @method static zajDb integer
 * @method static zajDb json
 * @method static zajDb locale
 * @method static zajDb locales
 * @method static zajDb manytomany(string $other_object, string $other_field='')
 * @method static zajDb manytoone($other_object)
 * @method static zajDbMap map
 * @method static zajDb name
 * @method static zajDb onetoone($other_object, $other_field='')
 * @method static zajDb onetomany($other_object, $other_field='')
 * @method static zajDb ordernum
 * @method static zajDb password
 * @method static zajDbPhoto photo
 * @method static zajDbPhoto photos
 * @method static zajDb rating
 * @method static zajDb richtext
 * @method static zajDb select($array)
 * @method static zajDb serialized
 * @method static zajDb text(integer $length=255)
 * @method static zajDb textarea
 * @method static zajDb textbox
 * @method static zajDb time
 * @method static zajDb timestamp
 * @method static zajDb tinymce
 * @method static zajDb unittest
 * @method static zajDb year
 **/
class zajDb{}


/**
 * Class zajDbPhoto
 * @method zajDbPhoto min_width
 * @method zajDbPhoto min_height
 * @method zajDbPhoto max_file_size
 */
class zajDbPhoto extends zajDb{}


/**
 * Class zajDbMap
 * @method zajDbMap geolocation
 */
class zajDbMap extends zajDb{}



/**
 * Adds some dynamic properties
 * @property string $class The class of the parent.
 * @property string $parent The id of the parent.
 * @property string $field The field name of the parent.
 * @property string $name The file name.
 * @property string $imagetype Can be IMAGETYPE_PNG, IMAGETYPE_GIF, or IMAGETYPE_JPG constant.
 * @property string $description Description.
 * @property stdClass $filesizes
 * @property stdClass $dimensions
 * @property stdClass $cropdata Stores original photo data and associated cropping values.
 * @property boolean $timepath Deprecated.
 * @property string $original Deprecated.
 **/
class zajDataPhoto extends zajData{}

/**
 * Add some dynamic properties
 * @property string $class The class of the parent.
 * @property string $parent The id of the parent.
 * @property string $field The field name of the parent.
 * @property string $name The file name.
 * @property string $mime The file mime type.
 * @property integer $size File size in bytes.
 * @property string $description Any longer description of the file.
 * @property boolean $timepath Deprecated.
 * @property string $original Deprecated.
 */
class zajDataFile extends zajData{}

/**
 * Adds some dynamic properties
 * @property float $lat
 * @property float $lng
 **/
class zajDataMap {}

/**
 * Add some properties to category data.
 * @property string $abc The converted, abc-sort compatibly text field.
 * @property string $description
 * @property Category $parentcategory
 * @property string $friendlyurl
 * @property Photo $photo
 * @property boolean $featured
 * @property zajFetcher $subcategories
 */
class zajDataCategory extends zajData {}

/**
 * Add properties to Translation data.
 * @property string $modelname
 * @property string $parent
 * @property string $field
 * @property mixed $value
 * @property string $locale
 */
class zajTranslationData extends zajData {}
