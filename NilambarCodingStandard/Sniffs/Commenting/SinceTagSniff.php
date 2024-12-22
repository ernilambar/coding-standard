<?php
/**
 * SinceTagSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Commenting;

use NilambarCodingStandard\Traits\CommentTag;
use NilambarCodingStandard\Traits\GetEntityName;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Detect since tag in PHPDoc.
 *
 * @since 1.0.0
 */
final class SinceTagSniff implements Sniff {

	use CommentTag;
	use GetEntityName;

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [
			T_FUNCTION,
			T_CLASS,
			T_INTERFACE,
			T_TRAIT,
			T_CONST,
		];
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
		$tokens = $phpcsFile->getTokens();

		$commentStart = $phpcsFile->findPrevious( T_DOC_COMMENT_OPEN_TAG, $stackPtr );

		if ( empty( $commentStart ) ) {
			return;
		}

		$since  = $this->find_tag( '@since', $commentStart, $tokens );
		$entity = $this->get_entity_full_name( $phpcsFile, $stackPtr, $tokens );

		if ( empty( $since ) ) {
			$phpcsFile->addError(
				sprintf(
					'@since tag missing for %s.',
					$entity
				),
				$stackPtr,
				'Missing'
			);

			return;
		}
	}
}
