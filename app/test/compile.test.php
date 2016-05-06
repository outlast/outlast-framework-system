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

	/**
	 * Test more extended
	 */
	public function system_compile_more_extended(){
	    $contents = $this->zajlib->template->show('system/test/test_more_extended.html', false, true);
		zajTestAssert::areIdentical("This is my base.\nOverwritten once more. Block only in base. \nSome more base-level text.\nNot only in base.", $contents);

	    $contents = $this->zajlib->template->block('system/test/test_more_extended.html', 'only_in_base', false, false, true);
	    zajTestAssert::areIdentical("Not only in base.", $contents);

	    $contents = $this->zajlib->template->block('system/test/test_extended.html', 'only_in_base', false, false, true);
	    zajTestAssert::areIdentical("Block only in base.", $contents);

	    $contents = $this->zajlib->template->block('system/test/test_more_extended.html', 'base_test', false, false, true);
	    zajTestAssert::areIdentical("Overwritten once more. Block only in base. ", $contents);
	}

	/**
	 * Test parent block feature
	 */
	public function system_compile_parent_block(){
	    $contents = $this->zajlib->template->show('system/test/test_parent_block.html', false, true);
		zajTestAssert::areIdentical("This is my base.\nAnd again: Which was overwritten. \nSome more base-level text.\nBlock only in base. Or is it?", $contents);

	    $contents = $this->zajlib->template->block('system/test/test_parent_block.html', 'only_in_base', false, false, true);
	    zajTestAssert::areIdentical("Block only in base. Or is it?", $contents);

	}

	/**
	 * Test embeded blocks feature
	 */
	public function system_compile_embeded_blocks(){
		// Try the top level file
	    $contents = $this->zajlib->template->show('system/test/test_embeded_blocks.html', false, true);
		zajTestAssert::areIdentical("Embeded tags.\n\nTop level.\n\nSecond level.\n\nThird level.\n\nStill second level.\n\n\nEnd of embeded tags.", $contents);

	    $contents = $this->zajlib->template->block('system/test/test_embeded_blocks.html', 'third_level', false, false, true);
		zajTestAssert::areIdentical("Third level.", trim($contents));
	    $contents = $this->zajlib->template->block('system/test/test_embeded_blocks.html', 'second_level', false, false, true);
		zajTestAssert::areIdentical("Second level.\n\nThird level.\n\nStill second level.", trim($contents));

		// Now let's test the extension file
	    $contents = $this->zajlib->template->show('system/test/test_embeded_blocks_extended.html', false, true);
		zajTestAssert::areIdentical("Embeded tags.\n\nTop level.\n\nSecond is overwritten with content.\n\n\nEnd of embeded tags.", trim($contents));

		// Now let's test the extension sub and sub sub file
	    $contents = $this->zajlib->template->show('system/test/test_embeded_blocks_sub.html', false, true);
		zajTestAssert::areIdentical("Embeded tags.\n\nTop level.\n\nSecond is overflowing with content.\n\n\nEnd of embeded tags.", trim($contents));
	    $contents = $this->zajlib->template->show('system/test/test_embeded_blocks_sub_sub.html', false, true);
		zajTestAssert::areIdentical("Embeded tags.\n\nTop level.\n\nSecond is overpowered with content.\n\n\nEnd of embeded tags.", trim($contents));
	}

}