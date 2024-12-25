<?php
/**
 * AllCapsCommentSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Commenting;

use WordPressCS\WordPress\Sniff;

/**
 * AllCapsCommentSniff class.
 *
 * @since 1.0.0
 */
final class AllCapsCommentSniff extends Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [ \T_COMMENT ];
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|void Integer stack pointer to skip forward or void to continue normal file processing.
	 */
	public function process_token( $stackPtr ) {
		$content = $this->tokens[ $stackPtr ]['content'];

		// Remove the comment characters (//, /*, */, #) and trim whitespace.
		$commentText = preg_replace( '/^\s*(\/\/|#|\/\*|\*\/)/', '', $content );
		$commentText = trim( $commentText );

		if ( strtoupper( $commentText ) === $commentText ) {
			$message = 'Avoid using all capital letters in comments.';
			$this->phpcsFile->addWarning( $message, $stackPtr, 'Found' );
		}
	}
}
