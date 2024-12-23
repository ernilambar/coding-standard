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
				$item = $token;

				$item['tag'] = $i;

				$tags[] = $item;
			}
		}

		return $tags;
	}
}
