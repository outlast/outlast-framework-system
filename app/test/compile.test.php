<?php
/**
 * A standard unit test for Outlast Framework system stuff.
 **/
class OfwCompileTest extends zajTest {

	/**
	 * Test base and blocks.
	 **/
	public function system_compile_base(){
	    $contents = $this->zajlib->template->show('system/test/test_base.html', false, true);
	    zajTestAssert::areIdentical("This is my base.\nWith a test block.\nSome more base-level text.\nBlock only in base.", $contents);

	    $contents = $this->zajlib->template->block('system/test/test_base.html', 'base_test', false, false, true);
	    zajTestAssert::areIdentical("With a test block.", $contents);
	}

	/**
	 * Test extension.
	 **/
	public function system_compile_extended(){
	    $contents = $this->zajlib->template->show('system/test/test_extended.html', false, true);
	    zajTestAssert::areIdentical("This is my base.\nWhich was overwritten.\nSome more base-level text.\nBlock only in base.", $contents);

	    $contents = $this->zajlib->template->block('system/test/test_base.html', 'base_test', false, false, true);
	    zajTestAssert::areIdentical("With a test block.", $contents);

	    $contents = $this->zajlib->template->block('system/test/test_extended.html', 'base_test', false, false, true);
	    zajTestAssert::areIdentical("Which was overwritten.", $contents);

	    $contents = $this->zajlib->template->block('system/test/test_extended.html', 'only_in_base', false, false, true);
	    zajTestAssert::areIdentical("Block only in base.", $contents);
	}

}