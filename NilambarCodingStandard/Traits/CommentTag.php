<?php
/**
 * CommentTag
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Traits;

use PHP_CodeSniffer\Files\File;

/**
 * Trait CommentTag.
 *
 * @since 1.0.0
 */
trait CommentTag {

	/**
	 * Find tag in PHPDoc.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tagName      Tag name.
	 * @param int    $commentStart PHPDoc start position.
	 * @param array  $tokens       List of tokens.
	 * @return array An array of tags.
	 */
	protected function find_tag( string $tagName, int $commentStart, array $tokens ): array {
		$commentTags = ! empty( $tokens[ $commentStart ]['comment_tags'] ) ? $tokens[ $commentStart ]['comment_tags'] : [];

		foreach ( $commentTags as $commentTag ) {
			if ( $tokens[ $commentTag ]['content'] === $tagName ) {
				$tag = $tokens[ $commentTag ];

				$tag['tag'] = $commentTag;

				return $tag;
			}
		}

		return [];
	}

	/**
	 * Checks whether tag is the last tag in PHPDoc.
	 *
	 * @since 1.0.0
	 *
	 * @param File  $phpcsFile The PHP_CodeSniffer file where the token was found.
	 * @param array $tag       Tag information.
	 * @return bool True if last tag, otherwise false.
	 */
	protected function is_last_tag( File $phpcsFile, array $tag ): bool {
		$tokens       = $phpcsFile->getTokens();
		$closeComment = $phpcsFile->findNext( T_DOC_COMMENT_CLOSE_TAG, $tag['tag'] );

		return $tag['line'] === $tokens[ $closeComment ]['line'] - 1;
	}

	/**
	 * Checks whether there is empty line after given tag in PHPDoc.
	 *
	 * @since 1.0.0
	 *
	 * @param File  $phpcsFile The PHP_CodeSniffer file where the token was found.
	 * @param array $tag       Tag information.
	 * @return bool True if there is empty line after tag, otherwise false.
	 */
	protected function has_empty_line_after_tag( File $phpcsFile, array $tag ): bool {
		$tokens = $phpcsFile->getTokens();

		$star = $phpcsFile->findNext( T_DOC_COMMENT_STAR, $tag['tag'] );

		if ( ! $star ) {
			return false;
		}

		if ( $tokens[ $star ]['line'] !== $tag['line'] + 1 ) {
			return false;
		}

		return T_DOC_COMMENT_STAR === $tokens[ $star ]['code'] && T_DOC_COMMENT_WHITESPACE === $tokens[ $star + 1 ]['code'] && T_DOC_COMMENT_WHITESPACE === $tokens[ $star + 2 ]['code'];
	}

	/**
	 * Checks whether there is empty line before given tag in PHPDoc.
	 *
	 * @since 1.0.0
	 *
	 * @param File  $phpcsFile The PHP_CodeSniffer file where the token was found.
	 * @param array $tag       Tag information.
	 * @return bool True if there is empty line before tag, otherwise false.
	 */
	protected function has_empty_line_before_tag( File $phpcsFile, array $tag ): bool {
		$tokens = $phpcsFile->getTokens();

		// Find the previous T_DOC_COMMENT_STRING (the text before the tag).
		$prevContent = $phpcsFile->findPrevious( T_DOC_COMMENT_STRING, $tag['tag'] - 1 );

		// No previous content means no empty line needed: beginning of the DocBlock.
		if ( false === $prevContent ) {
			return true;
		}

		$docBlockOpen = $phpcsFile->findPrevious( T_DOC_COMMENT_OPEN_TAG, $prevContent );

		// If the current tag isn't in the same comment as the prevContent, it will always have an empty line because it will be in another docblock.
		if ( $tokens[ $docBlockOpen ]['comment_closer'] < $tag['tag'] ) {
			return true;
		}

		// Check the line difference.
		return $tokens[ $prevContent ]['line'] + 1 < $tag['line'];
	}

	/**
	 * Find all tags in PHPDoc.
	 *
	 * @param File $phpcsFile The PHPCS file object.
	 * @param int  $commentStart The starting position (token index) of the comment.
	 * @param int  $commentEnd   The ending position (token index) of the comment.
	 * @return array An array of tags, with tag names as keys.  Returns an empty array if no tags are found or if the range is invalid.
	 */
	protected function find_tags( File $phpcsFile, int $commentStart, int $commentEnd ): array {
		if ( $commentEnd < $commentStart ) {
			return [];
		}

		$tags = [];

		for ( $i = $commentStart + 1; $i < $commentEnd; $i++ ) {
			$token = $phpcsFile->getTokens()[ $i ];

			if ( T_DOC_COMMENT_TAG === $token['code'] && 0 === strpos( $token['content'], '@' ) ) {
				$tags[] = $token;
			}
		}

		return $tags;
	}
}
