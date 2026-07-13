<?php
/**
 * Unit tests for AllCapsCommentSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Tests\Commenting;

use NilambarCodingStandard\Tests\AbstractSniffUnitTest;

/**
 * Unit tests for AllCapsCommentSniff.
 */
final class AllCapsCommentUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @return array<int, array<int, string>>
	 */
	public function getErrorList() {
		return array();
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array<int, array<int, string>>
	 */
	public function getWarningList() {
		return array(
			3  => 1,
			6  => 1,
			16 => 1,
			20 => 1,
			22 => 1,
			25 => 1,
			31 => 1,
			32 => 1,
			67 => 1,
		);
	}

}
