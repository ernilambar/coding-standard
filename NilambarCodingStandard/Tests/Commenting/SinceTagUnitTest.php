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
			6  => 1,
			10 => 1,
			15 => 1,
			16 => 1,
			21 => 1,
			22 => 1,
			27 => 1,
			28 => 1,
			34 => 1,
			42 => 1,
			70 => 1,
		];
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array<int, array<int, string>>
	 */
	public function getWarningList() {
		return [
			60 => 1,
			70 => 1,
			77 => 1,
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
