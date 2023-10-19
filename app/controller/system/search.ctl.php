<?php
    /**
     * Search API.
     * @package Controller
     * @subpackage BuiltinControllers
     **/

    class zajapp_system_search extends zajController {

        /**
         * Load method is called each time any system action is executed.
         * @todo Allow a complete disabling of this controller.
         **/
        function __load() {
            // Add disable-check here!
        }


        /**
         * Search for a relationship.
         **/
        function relation() {

            // strip all non-alphanumeric characters
            $class_name = ucfirst(strtolower(preg_replace('/\W/', "", $_REQUEST['class'])));
            $field_name = preg_replace('/\W/', "", $_REQUEST['field']);
            if (!empty($_REQUEST['type'])) {
                $type = preg_replace('/\W/', "", $_REQUEST['type']);
            } else {
                $type = 'default';
            }

            // limit defaults to 15
            if (empty($_REQUEST['limit']) || !is_numeric($_REQUEST['limit'])) {
                $limit = 15;
            } else {
                $limit = $_REQUEST['limit'];
            }

            // is it a valid model?
            if (!is_subclass_of($class_name, "zajModel")) {
                return $this->ofw->error("Cannot search model '$class_name': not a zajModel!");
            }

            // now what is my field connected to?
            /** @var zajModel $class_name */
            $field_data = $class_name::__field($field_name);
            $other_model = $field_data->options['model'];
            if (empty($other_model)) {
                $this->ofw->error("Cannot connect to field '$field_name' because it is not defined as a relation or its relation model has not been defined!");
            }

            // first fetch all
            /** @var zajFetcher $relations */
            $relations = $other_model::fetch();

            // filter by search query (if any)
            if (!empty($_REQUEST['query'])) {
                $relations->search('%'.$_REQUEST['query'].'%', false);
            }
            // limit
            $relations->limit($limit);

            // now send this to the magic method
            if (empty($this->ofw->variable->user)) {
                $this->ofw->variable->user = User::fetch_by_session();
            }
            $relations = $other_model::fire_static('onSearch', [$relations, $type]);
            // error?
            if (!is_object($relations)) {
                return zajLib::me()->error("You are trying to access the client-side search API for $other_model and access was denied by this model. <a href='http://framework.outlast.hu/advanced/client-side-search-api/' target='_blank'>See docs</a>.");
            }

            // now output to relations json
            $my_relations = [];
            foreach ($relations as $rel) {
                /** @var zajModel $rel */
                $my_relations[] = $rel->fire('toSearchApiJson');
            }

            // now return the json-encoded object
            return $this->ofw->json(['query' => $_REQUEST['query'], 'data' => $my_relations]);
        }
    }