<?php
/**
 * SettingSanitizationSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Security;

use PHPCSUtils\Utils\PassedParameters;
use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * Detect sanitization in register_setting().
 *
 * @since 1.0.0
 */
final class SettingSanitizationSniff extends AbstractFunctionParameterSniff {

	/**
	 * The group name for this group of functions.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $group_name = 'register_setting';

	/**
	 * List of functions to examine.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, true> Key is function name, value irrelevant.
	 */

	protected $target_functions = [
		'register_setting' => true,
	];

	/**
	 * Process the parameters of a matched function.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The token content (function name) which was matched
	 *                                in lowercase.
	 * @param array  $parameters      Array with information about the parameters.
	 *
	 * @return void
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		$third_param = PassedParameters::getParameterFromStack( $parameters, 3, 'args' );

		if ( false === $third_param ) {
			$error = 'Sanitization missing for register_setting().';
			$this->phpcsFile->addError( $error, $stackPtr, 'Missing' );
			return;
		}

		$content = TextStrings::stripQuotes( $third_param['clean'] );

		if ( is_numeric( $content ) || in_array( strtolower( $content ), [ 'true', 'false' ], true ) ) {
			$error = 'Invalid sanitization in third parameter of register_setting().';
			$this->phpcsFile->addError( $error, $stackPtr, 'Invalid' );
		}
	}
}
