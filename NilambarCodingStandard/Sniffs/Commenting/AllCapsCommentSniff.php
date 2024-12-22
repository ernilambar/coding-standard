<?php
/**
 * AllCapsCommentSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Detect whether comments are in all caps.
 *
 * @since 1.0.0
 */
final class AllCapsCommentSniff implements Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [ T_COMMENT ];
	}

	/**
	 * Processes this sniff, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens  = $phpcsFile->getTokens();
		$content = $tokens[ $stackPtr ]['content'];

		// Remove the comment characters (//, /*, */, #) and trim whitespace.
		$commentText = preg_replace( '/^\s*(\/\/|#|\/\*|\*\/)/', '', $content );
		$commentText = trim( $commentText );

		if ( strtoupper( $commentText ) === $commentText ) {
			$message = 'Avoid using all capital letters in comments.';
			$phpcsFile->addWarning( $message, $stackPtr, 'Found' );
		}
	}
}
