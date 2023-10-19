<?php
/**
 * This file helps IDEs autocomplete stuff within this plugin. It is never actually used.
 **/
die("This file is for documentation.");

/**
 * @method static zajDb boolean(boolean $default=false)
 * @method static zajDb category
 * @method static zajDb categories
 * @method static zajDbConfiguration configuration
 * @method static zajDbConfiguration configurations
 * @method static zajDb color
 * @method static zajDB custom
 * @method static zajDb date
 * @method static zajDb datetime
 * @method static zajDb email
 * @method static zajDb file
 * @method static zajDb files
 * @method static zajDb float
 * @method static zajDbFriendly friendly
 * @method static zajDb id
 * @method static zajDb integer
 * @method static zajDb json
 * @method static zajDb keyvalue
 * @method static zajDb locale
 * @method static zajDb locales
 * @method static zajDbManyToMany manytomany(string $other_object, string $other_field='')
 * @method static zajDb manytoone($other_object)
 * @method static zajDbMap map
 * @method static zajDb name(integer $length=255)
 * @method static zajDb onetoone(string $other_object, string $other_field='')
 * @method static zajDb onetomany(string $other_object, string $other_field)
 * @method static zajDb ordernum
 * @method static zajDb password
 * @method static zajDbPhoto photo
 * @method static zajDbPhoto photos
 * @method static zajDb polymorphic
 * @method static zajDb rating
 * @method static zajDb richtext
 * @method static zajDb select($array, $default='')
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
 * Class zajDbConfiguration
 * @method zajDbConfiguration file(string $name)
 * @method zajDbConfiguration section(string $name)
 * @method zajDbConfiguration key(string $name)
 */
class zajDbConfiguration{}


/**
 * Class zajDbPhoto
 * @method zajDbPhoto min_width
 * @method zajDbPhoto min_height
 * @method zajDbPhoto max_file_size
 */
class zajDbPhoto extends zajDb{}

/**
 * @method zajDbFriendly from(string $field_name) Defines which field the friendly url is generated from.
 **/
class zajDbFriendly extends zajDb{}

/**
 * @method zajDbManyToMany maximum_selection_length(int $count) The number of connections that can be selected.
 */
class zajDbManyToMany extends zajDb {}

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
class CategoryData extends zajData {}
// backwards compatibility
class zajDataCategory extends CategoryData {}


/**
 * Add properties to Translation data.
 * @property string $modelname
 * @property string $parent
 * @property string $field
 * @property mixed $value
 * @property string $locale
 */
class zajTranslationData extends zajData {}

/**
 * Class zajFetcherConnectionDetails
 * @property string $table
 * @property string $field
 * @property string|zajModel $primary_model
 * @property string $primary_id_field
 * @property string|zajModel $secondary_model
 * @property string $secondary_id_field
 * @property string $order_field
 */
class zajFetcherConnectionDetails{}

/**
 * Class EmailLogData
 * @property string $subject Subject of email.
 * @property string $from From email address.
 * @property string $to To email address.
 * @property string $html_body Body html.
 * @property string $text_body Body text.
 * @property integer $sentat The time it was sent.
 * @property string $bcc Bcc email address.
 * @property array|stdClass $headers Additional headers.
 * @property string $status Status of email. Can be sent or failed (or new/deleted).
 * @property string $log The log message.
 */
class EmailLogData extends zajData {}

/**
 * Class zajlibConfigVariable
 * @property stdClass $section The variables broken into sections.
 */
class zajlibConfigVariable extends stdClass{}

/**
 * Class zajTestInstance
 * @property zajLib $ofw
 */
class zajTestInstance{}

/**
 * Class OfwTestData
 * @property string $email
 * @property zajFetcher $ofwtestanothers
 */
class OfwTestData extends zajData{}

/**
 * Class OfwTestAnotherData
 * @property zajFetcher $ofwtests
 */
class OfwTestAnotherData extends zajData{}

/**
 * Methods of db can be called on a session.
 */
class zajlib_db_session extends zajlib_db{}