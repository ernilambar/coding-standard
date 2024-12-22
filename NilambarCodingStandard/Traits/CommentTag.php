<?php
/**
 * CommentTag
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Traits;

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
}
