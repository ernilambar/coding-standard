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
	 * @param int  $commentStart The starting position (token index) of the comment.
	 * @param int  $commentEnd   The ending position (token index) of the comment.
	 * @return array An array of tags, with tag names as keys.  Returns an empty array if no tags are found or if the range is invalid.
	 */
	protected function find_comment_tags( File $phpcsFile, int $commentStart, int $commentEnd ): array {
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
