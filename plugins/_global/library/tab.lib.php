<?php
/**
 * Handles graphical tabs. This is depricated and should not be used.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

class zajlib_tab extends zajLibExtension {
	public $selected_tab = array();
	
	///////////////////////////////////////////////
	// select a tab on the next loaded template
	function select($group_id, $tab_id, $tab_selector_id=''){
		if(!$tab_selector_id) $tab_selector_id = true;
		$this->selected_tab[$group_id][$tab_id] = $tab_selector_id;
	}
	///////////////////////////////////////////////
	// alias for select
	function show($group_id, $tab_id, $tab_selector_id=''){
		$this->select($group_id, $tab_id, $tab_selector_id);
	}

	///////////////////////////////////////////////////////
	// returns true or the string of the tab_selector_id
	function is_selected($group_id, $tab_id){
		return $this->selected_tab[$group_id][$tab_id];
	}

	///////////////////////////////////////////////////////
	// returns true or the string of the tab_selector_id
	function is_group($group_id){
		return is_array($this->selected_tab[$group_id]);
	}

}




?>