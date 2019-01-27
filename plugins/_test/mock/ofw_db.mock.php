<?php

/**
 * A mock implementation of zajDb for testing.
 **/
class ofw_db_mock {

    public $queries = [];
    public $last_query = "";

    /**
     * Methods for compatibility.
     **/
    public function select($sql, $onerow = false, $column_as_key = ''){}
    public function get_num_rows(){ return 1; }

    /**
     * Just save the query.
     * @param string $query
     * @return string
     */
    public function query($query){
        // Add to queries, removing whitespace
        $this->last_query = trim(preg_replace('!\s+!', ' ', $query));
        $this->queries[] = $this->last_query;
        return new ofw_db_session_mock();
    }

}

/**
 * Class ofw_db_session_mock
 */
class ofw_db_session_mock {
    function next(){
        return new stdClass();
    }
}