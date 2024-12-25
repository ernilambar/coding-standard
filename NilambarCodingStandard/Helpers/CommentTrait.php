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
}
