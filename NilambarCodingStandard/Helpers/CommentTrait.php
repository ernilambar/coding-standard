<?php
/**
 * CommentTrait
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Helpers;

use PHP_CodeSniffer\Files\File;

/**
 * CommentTrait.
 *
 * @since 1.0.0
 */
trait CommentTrait {

	/**
	 * Find all tags in PHPDoc.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The PHPCS file object.
	 * @param int  $commentStart The starting position of the comment.
	 * @return array An array of tags.
	 */
	protected function find_comment_tags( File $phpcsFile, int $commentStart ): array {
		$tokens = $phpcsFile->getTokens();

		$commentTags = ! empty( $tokens[ $commentStart ]['comment_tags'] ) ? $tokens[ $commentStart ]['comment_tags'] : [];

		if ( empty( $commentTags ) ) {
			return [];
		}

		$tags = [];

		foreach ( $commentTags as $commentTag ) {
			if ( T_DOC_COMMENT_TAG === $tokens[ $commentTag ]['code'] ) {
				$tag = $tokens[ $commentTag ];

				$tag['tag'] = $commentTag;

				$tags[] = $tag;
			}
		}

		return $tags;
	}

	/**
	 * Checks if token has an associated PHPDoc block.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr The index of the token in the stack.
	 * @return bool True if a PHPDoc block is present immediately before the token; false otherwise.
	 */
	protected function has_doc_block( File $phpcsFile, int $stackPtr ): bool {
		$tokens = $phpcsFile->getTokens();

		// Find the previous non-whitespace token.
		$prevTokenPos = $phpcsFile->findPrevious( [ T_WHITESPACE ], $stackPtr - 1, null, true );

		if ( false !== $prevTokenPos && T_DOC_COMMENT_CLOSE_TAG === $tokens[ $prevTokenPos ]['code'] ) {
			// Ensure the comment block actually opens correctly.
			$commentStart = $phpcsFile->findPrevious( T_DOC_COMMENT_OPEN_TAG, $prevTokenPos );

			if ( false !== $commentStart ) {
				// Verify the tokens are a contiguous comment block up to our $prevTokenPos.
				for ( $i = $commentStart; $i <= $prevTokenPos; $i++ ) {
					if ( ! in_array(
						$tokens[ $i ]['code'],
						[
							T_DOC_COMMENT_OPEN_TAG,
							T_DOC_COMMENT_WHITESPACE,
							T_DOC_COMMENT_STAR,
							T_DOC_COMMENT_TAG,
							T_DOC_COMMENT_STRING,
							T_DOC_COMMENT_CLOSE_TAG,
						],
						true
					) ) {
						// If any other token is between, it's not a contiguous block.
						return false;
					}
				}

				return true;
			}
		}

		return false;
	}
}
