<?php
/**
 * Unit tests for SinceTagSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Tests\Commenting;

use NilambarCodingStandard\Sniffs\Commenting\SinceTagSniff;
use NilambarCodingStandard\Tests\AbstractSniffUnitTest;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Unit tests for SinceTagSniff.
 */
final class SinceTagUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @return array<int, array<int, string>>
	 */
	public function getErrorList() {
		return [
			5   => 1,
			11  => 1,
			16  => 1,
			25  => 1,
			34  => 1,
			41  => 1,
			49  => 1,
			94  => 1,
			95  => 1,
			103 => 1,
			116 => 1,
			122 => 1,
			131 => 1,
			139 => 1,
			147 => 1,
			155 => 1,
			163 => 1,
			180 => 1,
		];
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array<int, array<int, string>>
	 */
	public function getWarningList() {
		return [
			77  => 1,
			172 => 1,
		];
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	protected function get_sniff_fqcn() {
		return SinceTagSniff::class;
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
