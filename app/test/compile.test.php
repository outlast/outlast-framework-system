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
	    zajTestAssert::areIdentical("This is my base. With a test block. Some more base-level text. Block only in base. Finally some ending.", $this->whitespace_to_space($contents));

	    $contents = $this->zajlib->template->block('system/test/test_base.html', 'base_test', false, false, true);
	    zajTestAssert::areIdentical("With a test block.", $contents);
	}

	/**
	 * Test extension.
	 **/
	public function system_compile_extended(){
	    $contents = $this->zajlib->template->show('system/test/test_extended.html', false, true);
	    zajTestAssert::areIdentical("This is my base. Which was overwritten. Some more base-level text. Block only in base. Finally some ending.", $this->whitespace_to_space($contents));

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
		zajTestAssert::areIdentical("This is my base. Overwritten once more. Block only in base. Some more base-level text. Not only in base. Finally some ending.", $this->whitespace_to_space($contents));

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
		zajTestAssert::areIdentical("This is my base. And again: Which was overwritten. Some more base-level text. Block only in base. Or is it? Finally some ending.", $this->whitespace_to_space($contents));

	    $contents = $this->zajlib->template->block('system/test/test_parent_block.html', 'only_in_base', false, false, true);
	    zajTestAssert::areIdentical("Block only in base. Or is it?", $this->whitespace_to_space($contents));

	}

	/**
	 * Test embeded blocks feature
	 */
	public function system_compile_embeded_blocks(){
		// Try the top level file
	    $contents = $this->zajlib->template->show('system/test/test_embeded_blocks.html', false, true);
		zajTestAssert::areIdentical("Embeded tags. Top level. Second level. Third level. Still second level. End of embeded tags.", $this->whitespace_to_space($contents));

	    $contents = $this->zajlib->template->block('system/test/test_embeded_blocks.html', 'third_level', false, false, true);
		zajTestAssert::areIdentical("Third level.", $this->whitespace_to_space($contents));
	    $contents = $this->zajlib->template->block('system/test/test_embeded_blocks.html', 'second_level', false, false, true);
		zajTestAssert::areIdentical("Second level. Third level. Still second level.", $this->whitespace_to_space($contents));
		$contents = $this->zajlib->template->block('system/test/test_embeded_blocks.html', 'top_level', false, false, true);
		zajTestAssert::areIdentical("Top level. Second level. Third level. Still second level.", $this->whitespace_to_space($contents));

		// Now let's test the extension file
	    $contents = $this->zajlib->template->show('system/test/test_embeded_blocks_extended.html', false, true);
		zajTestAssert::areIdentical("Embeded tags. Top level. Second is really overwritten with content. End of embeded tags.", $this->whitespace_to_space($contents));

		// Now let's test the extension sub and sub sub file
	    $contents = $this->zajlib->template->show('system/test/test_embeded_blocks_sub.html', false, true);
		zajTestAssert::areIdentical("Embeded tags. Top level. Second is really overflowing with content. End of embeded tags.", $this->whitespace_to_space($contents));
	    $contents = $this->zajlib->template->show('system/test/test_embeded_blocks_sub_sub.html', false, true);
		zajTestAssert::areIdentical("Embeded tags. Top level. Second is overpowered with content. End of embeded tags.", $this->whitespace_to_space($contents));

        // Let's make sure all the blocks are correct
	    $contents = $this->zajlib->template->block('system/test/test_embeded_blocks_sub_sub.html', 'second_level_sub_sub', false, false, true);
		zajTestAssert::areIdentical("overpowered with content.", $this->whitespace_to_space($contents));
	    $contents = $this->zajlib->template->block('system/test/test_embeded_blocks_sub_sub.html', 'second_level_sub', false, false, true);
		zajTestAssert::areIdentical("overwritten", $this->whitespace_to_space($contents));
	    $contents = $this->zajlib->template->block('system/test/test_embeded_blocks_sub_sub.html', 'second_level', false, false, true);
		zajTestAssert::areIdentical("Second is overpowered with content.", $this->whitespace_to_space($contents));
	}

	/**
	 * Strip any whitespace down to a space.
	 */
	private function whitespace_to_space($content){
		return trim(preg_replace('/[ \n]+/', ' ', $content));
	}


}