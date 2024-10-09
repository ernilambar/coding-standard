<?php
/**
 * UnfilteredUploadsSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\TextStrings;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Detect ALLOW_UNFILTERED_UPLOADS define variable.
 *
 * @since 1.0.0
 */
final class UnfilteredUploadsSniff implements Sniff {

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

		// Check for 'define(' specifically, allowing optional whitespace.
		if ( 'define' !== $tokens[ $stackPtr ]['content']
			|| T_OPEN_PARENTHESIS !== $tokens[ ( $stackPtr + 1 ) ]['code'] ) {
			return;
		}

		// Determine the position after potential whitespace.
		$nextNonWhitespace = $phpcsFile->findNext( T_WHITESPACE, ( $stackPtr + 2 ), null, true );

		// Check if we have a valid constant name (string or constant).
		if ( ! in_array( $tokens[ $nextNonWhitespace ]['code'], [ T_CONSTANT_ENCAPSED_STRING, T_STRING ], true ) ) {
			return;
		}

		// Extract the constant name, handling both quoted and unquoted.
		$constantName = $tokens[ $nextNonWhitespace ]['content'];

		if ( T_CONSTANT_ENCAPSED_STRING === $tokens[ $nextNonWhitespace ]['code'] ) {
			$constantName = trim( $constantName, "'\"" ); // Remove quotes.
		}

		// Check if it's our target constant.
		if ( 'ALLOW_UNFILTERED_UPLOADS' === $constantName ) {
			$error = 'Use of `ALLOW_UNFILTERED_UPLOADS` is prohibited.';
			$phpcsFile->addError( $error, $stackPtr, 'Prohibited' );
		}
	}
}
