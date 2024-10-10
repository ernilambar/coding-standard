<?php
/**
 * SettingSanitizationSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Security;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Detect sanitization in register_setting().
 *
 * @since 1.0.0
 */
final class SettingSanitizationSniff implements Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [ T_STRING ];
	}

	/**
	 * Processes this sniff, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token
	 *                                               in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		// Check if the function call is to `register_setting`.
		if ( 'register_setting' === $tokens[ $stackPtr ]['content'] ) {
			$openParenthesis = $phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true, null, true );

			if ( T_OPEN_PARENTHESIS === $tokens[ $openParenthesis ]['code'] ) {
				$closeParenthesis = $tokens[ $openParenthesis ]['parenthesis_closer'];

				// Find the third parameter start.
				$nextComma = $openParenthesis;
				for ( $i = 0; $i < 2; $i++ ) {
					$nextComma = $phpcsFile->findNext( T_COMMA, $nextComma + 1, $closeParenthesis );

					// Less than three parameters.
					if ( false === $nextComma ) {
						$error = 'Sanitization missing for register_setting().';
						$phpcsFile->addError( $error, $stackPtr, 'Missing' );
						return;
					}
				}

				// Move past whitespace to the third parameter.
				$thirdParamStart = $phpcsFile->findNext( Tokens::$emptyTokens, $nextComma + 1, $closeParenthesis, true );

				if ( false === $thirdParamStart ) {
					// No third parameter.
					$error = 'Sanitization missing for register_setting().';
					$phpcsFile->addError( $error, $stackPtr, 'Missing' );
					return;
				}

				// Check the third parameter.
				$thirdTokenType = $tokens[ $thirdParamStart ]['code'];

				if ( ! in_array( $thirdTokenType, [ T_CONSTANT_ENCAPSED_STRING, T_STRING, T_ARRAY, T_OPEN_SHORT_ARRAY ], true ) ) {
						$error = 'Invalid sanitization in third parameter of register_setting().';
						$phpcsFile->addError( $error, $stackPtr, 'Invalid' );
				}
			}
		}
	}
}
