<?php
/**
 * Unit test class for the VerifyNonceSniff sniff.
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Tests\Security;

use NilambarCodingStandard\Sniffs\Security\VerifyNonceSniff;
use NilambarCodingStandard\Tests\AbstractSniffUnitTest;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Unit test class for the VerifyNonceSniff sniff.
 *
 * @since 1.0.0
 */
final class VerifyNonceUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @since 1.0.0
	 *
	 * @param string $test_file The name of the test file being checked.
	 * @return array<int, int> Key is the line number and value is the number of expected errors.
	 */
	public function getErrorList( $test_file = '' ) {
		switch ( $test_file ) {
			case 'VerifyNonceUnitTest.1.inc':
				return array(
					5  => 1,
					11 => 1,
					16 => 1,
					22 => 1,
					26 => 1,
					34 => 1,
					38 => 1,
					46 => 1,
				);
			default:
				return array();
		}
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, int> Key is the line number and value is the number of expected warnings.
	 */
	public function getWarningList() {
		return array();
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @since 1.0.0
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	protected function get_sniff_fqcn() {
		return VerifyNonceSniff::class;
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
