<?php
/**
 * Unit tests for I18nFunctionParametersSniff.
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Tests\Language;

use NilambarCodingStandard\Sniffs\Language\I18nFunctionParametersSniff;
use NilambarCodingStandard\Tests\AbstractSniffUnitTest;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Unit tests for I18nFunctionParametersSniff.
 */
final class I18nFunctionParametersUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @return array <int line number> => <int number of errors>
	 */
	public function getErrorList() {
		return [];
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array <int line number> => <int number of warnings>
	 */
	public function getWarningList() {
		return [
			3  => 1,
			13 => 2,
			16 => 1,
			19 => 1,
			22 => 1,
			25 => 1,
			28 => 1,
			31 => 1,
			43 => 1,
		];
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	protected function get_sniff_fqcn() {
		return I18nFunctionParametersSniff::class;
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
