<?php
/**
 * Unit tests for RequiredFunctionParametersSniff.
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Tests\CodeAnalysis;

use NilambarCodingStandard\Sniffs\CodeAnalysis\RequiredFunctionParametersSniff;
use NilambarCodingStandard\Tests\AbstractSniffUnitTest;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Unit tests for RequiredFunctionParametersSniff.
 */
final class RequiredFunctionParametersUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * @return array <int line number> => <int number of errors>
	 */
	public function getErrorList() {
		return [
			4  => 1,
			8  => 1,
			11 => 1,
		];
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * @return array <int line number> => <int number of warnings>
	 */
	public function getWarningList() {
		return [];
	}

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	protected function get_sniff_fqcn() {
		return RequiredFunctionParametersSniff::class;
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
