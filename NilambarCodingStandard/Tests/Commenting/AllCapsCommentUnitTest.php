<?php
/**
 * Unit tests for AllCapsCommentSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Tests\Commenting;

use NilambarCodingStandard\Sniffs\Commenting\AllCapsCommentSniff;
use NilambarCodingStandard\Tests\AbstractSniffUnitTest;
use PHP_CodeSniffer\Sniffs\Sniff;

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
		return [];
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array<int, array<int, string>>
	 */
	public function getWarningList() {
		return [
			3  => 1,
			6  => 1,
			16 => 1,
			20 => 1,
			22 => 1,
			25 => 1,
		];
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	protected function get_sniff_fqcn() {
		return AllCapsCommentSniff::class;
	}

	/**
	 * Sets the parameters for the sniff.
	 *
	 * @throws \RuntimeException If unable to set the ruleset parameters required for the test.
	 *
	 * @param Sniff $sniff The sniff being tested.
	 */
	public function set_sniff_parameters( Sniff $sniff ) {
	}
}
