<?php
/**
 * TodoCommentSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Detect TODO in comments.
 *
 * @since 1.0.0
 */
final class TodoCommentSniff implements Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [ T_COMMENT, T_DOC_COMMENT_STRING ];
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

		$content = strtolower( $tokens[ $stackPtr ]['content'] );

		if ( strpos( $content, 'todo:' ) !== false ) {
					$error = 'Avoid "TODO" comment.';
					$phpcsFile->addWarning( $error, $stackPtr, 'Found' );
		}
	}
}
