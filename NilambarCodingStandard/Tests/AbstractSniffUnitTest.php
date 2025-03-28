<?php
/**
 * Abstract test class.
 *
 * @package NilambarCodingStandard
 */

namespace NilambarCodingStandard\Tests;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest as BaseAbstractSniffUnitTest;

/**
 * An abstract test class that contains common methods for all sniff unit tests.
 */
abstract class AbstractSniffUnitTest extends BaseAbstractSniffUnitTest {

	/**
	 * Holds the original Ruleset instance.
	 *
	 * @var Ruleset
	 */
	protected static $original_ruleset;

	/**
	 * This method resets the 'NilambarCodingStandard' ruleset in the $GLOBALS['PHP_CODESNIFFER_RULESETS']
	 * to its original state.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		$GLOBALS['PHP_CODESNIFFER_RULESETS']['NilambarCodingStandard'] = static::$original_ruleset;
		static::$original_ruleset                                      = null;
	}

	/**
	 * Sets the parameters for the sniff.
	 *
	 * @throws RuntimeException If unable to set the ruleset parameters required for the test.
	 *
	 * @param Sniff $sniff The sniff being tested.
	 */
	abstract protected function set_sniff_parameters( Sniff $sniff );

	/**
	 * Returns the fully qualified class name (FQCN) of the sniff.
	 *
	 * @return string The fully qualified class name of the sniff.
	 */
	abstract protected function get_sniff_fqcn();

	/**
	 * Prepares the environment before executing tests. This is needed since
	 * PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest doesn't apply
	 * sniff properties from the NilambarCodingStandard/ruleset.xml file.
	 *
	 * @param string $filename The name of the file being tested.
	 * @param Config $config   The config data for the run.
	 */
	public function setCliValues( $filename, $config ) {
		parent::setCliValues( $filename, $config );

		$error_message = 'Cannot set sniff parameters required for the unit test.';
		if ( ! isset( $GLOBALS['PHP_CODESNIFFER_RULESETS']['NilambarCodingStandard'] )
			|| ( ! $GLOBALS['PHP_CODESNIFFER_RULESETS']['NilambarCodingStandard'] instanceof Ruleset )
		) {
			throw new \RuntimeException( $error_message ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- this is non-production code.
		}

		// Backup the original Ruleset instance.
		static::$original_ruleset = $GLOBALS['PHP_CODESNIFFER_RULESETS']['NilambarCodingStandard'];

		$current_ruleset = clone static::$original_ruleset;
		$GLOBALS['PHP_CODESNIFFER_RULESETS']['NilambarCodingStandard'] = $current_ruleset;

		$sniff_fqcn = $this->get_sniff_fqcn();
		if ( ! isset( $current_ruleset->sniffs[ $sniff_fqcn ] ) ) {
			throw new \RuntimeException( $error_message ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- this is non-production code.
		}

		$sniff = $current_ruleset->sniffs[ $sniff_fqcn ];
		$this->set_sniff_parameters( $sniff );
	}
}
