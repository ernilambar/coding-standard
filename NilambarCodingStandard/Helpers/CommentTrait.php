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
	 * Finds the opening doc block for the given token, if any.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  The index of the token in the stack.
	 * @return int|null Pointer to T_DOC_COMMENT_OPEN_TAG, or null if no doc block precedes the token.
	 */
	protected function find_doc_block_opener( File $phpcsFile, int $stackPtr ): ?int {
		$tokens = $phpcsFile->getTokens();

		$skip_tokens = array(
			\T_ABSTRACT,
			\T_COMMENT,
			\T_FINAL,
			\T_PRIVATE,
			\T_PROTECTED,
			\T_PUBLIC,
			\T_READONLY,
			\T_STATIC,
			\T_VAR,
			\T_WHITESPACE,
		);

		// Move backwards over modifiers/whitespace/inline-comments to find the previous significant token.
		// PHP 8 attributes can sit between the docblock and the declaration; jump over whole #[...] ranges.
		$search_from = $stackPtr - 1;
		do {
			$prev_token_pos = $phpcsFile->findPrevious( $skip_tokens, $search_from, null, true );

			if ( false !== $prev_token_pos
				&& \T_ATTRIBUTE_END === $tokens[ $prev_token_pos ]['code']
				&& isset( $tokens[ $prev_token_pos ]['attribute_opener'] )
			) {
				$search_from = $tokens[ $prev_token_pos ]['attribute_opener'] - 1;
				continue;
			}

			break;
		} while ( true );

		if ( false === $prev_token_pos
			|| \T_DOC_COMMENT_CLOSE_TAG !== $tokens[ $prev_token_pos ]['code']
		) {
			return null;
		}

		if ( ! isset( $tokens[ $prev_token_pos ]['comment_opener'] ) ) {
			return null;
		}

		$comment_start = $tokens[ $prev_token_pos ]['comment_opener'];

		// Sanity check: the range from opener to closer should only contain doc-comment tokens.
		for ( $i = $comment_start; $i <= $prev_token_pos; $i++ ) {
			if ( ! in_array(
				$tokens[ $i ]['code'],
				array(
					\T_DOC_COMMENT_OPEN_TAG,
					\T_DOC_COMMENT_WHITESPACE,
					\T_DOC_COMMENT_STAR,
					\T_DOC_COMMENT_TAG,
					\T_DOC_COMMENT_STRING,
					\T_DOC_COMMENT_CLOSE_TAG,
				),
				true
			) ) {
				return null;
			}
		}

		return $comment_start;
	}

	/**
	 * Checks if a token has an associated PHPDoc block.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  The index of the token in the stack.
	 * @return bool True if a PHPDoc block is present immediately before the token.
	 */
	protected function has_doc_block( File $phpcsFile, int $stackPtr ): bool {
		return null !== $this->find_doc_block_opener( $phpcsFile, $stackPtr );
	}
}
