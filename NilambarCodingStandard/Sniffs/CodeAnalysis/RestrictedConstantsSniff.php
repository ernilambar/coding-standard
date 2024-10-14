<?php
/**
 * RestrictedConstantsSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\MessageHelper;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\TextStrings;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPCSUtils\Utils\PassedParameters;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;
use WordPressCS\WordPress\Helpers\ConstantsHelper;

/**
 * Detect restricted define variables.
 *
 * @since 1.0.0
 */
final class RestrictedConstantsSniff extends AbstractFunctionParameterSniff {

	/**
	 * List of restricted WP constants.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $restricted_constants = [
		'ALLOW_UNFILTERED_UPLOADS' => true,
	];

	/**
	 * Array of functions to check.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, array<string, int|string>> Function name as key, array with target parameter and name as value.
	 */
	protected $target_functions = [
		'define' => [
			'position' => 1,
			'name'     => 'constant_name',
		],
	];

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|void Integer stack pointer to skip forward or void to continue normal file processing.
	 */
	public function process_token( $stackPtr ) {
		if ( isset( $this->target_functions[ strtolower( $this->tokens[ $stackPtr ]['content'] ) ] ) ) {
			// Disallow excluding function groups for this sniff.
			$this->exclude = [];

			return parent::process_token( $stackPtr );
		}
	}

	/**
	 * Process the parameters of a matched `define` function call.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The token content (function name) which was matched in lowercase.
	 * @param array  $parameters      Array with information about the parameters.
	 * @return void
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		$target_param = $this->target_functions[ $matched_content ];

		// Was the target parameter passed?
		$found_param = PassedParameters::getParameterFromStack( $parameters, $target_param['position'], $target_param['name'] );
		if ( false === $found_param ) {
			return;
		}

		$clean_content = TextStrings::stripQuotes( $found_param['clean'] );

		if ( isset( $this->restricted_constants[ $clean_content ] ) ) {
			$first_non_empty = $this->phpcsFile->findNext( Tokens::$emptyTokens, $found_param['start'], ( $found_param['end'] + 1 ), true );

			$this->phpcsFile->addError(
				'Found declaration of constant "%s".',
				$first_non_empty,
				MessageHelper::stringToErrorcode( $clean_content . 'DeclarationFound' ),
				[
					$clean_content,
				]
			);
		}
	}
}
