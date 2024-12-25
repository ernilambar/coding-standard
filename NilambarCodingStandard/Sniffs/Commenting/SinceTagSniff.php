<?php
/**
 * SinceTagSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Commenting;

use NilambarCodingStandard\Traits\CommentTag;
use NilambarCodingStandard\Traits\GetEntityName;
use WordPressCS\WordPress\Sniff;

/**
 * SinceTagSniff class.
 *
 * @since 1.0.0
 */
final class SinceTagSniff extends Sniff {

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
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|void Integer stack pointer to skip forward or void to continue normal file processing.
	 */
	public function process_token( $stackPtr ) {
		$tokens = $this->phpcsFile->getTokens();

		$commentStart = $this->phpcsFile->findPrevious( T_DOC_COMMENT_OPEN_TAG, $stackPtr );

		if ( empty( $commentStart ) ) {
			return;
		}

		$commentEnd = $this->phpcsFile->findNext( T_DOC_COMMENT_CLOSE_TAG, ( $commentStart + 1 ) );

		if ( false === $commentEnd ) {
			return;
		}

		// Current entity.
		$entity = $this->get_entity_full_name( $this->phpcsFile, $stackPtr, $tokens );

		$allTags = $this->find_tags( $this->phpcsFile, $commentStart, $commentEnd );

		$sinceTags = array_filter(
			$allTags,
			function ( $element ) {
				return '@since' === $element['content'];
			}
		);

		// Bail if no since tags.
		if ( empty( $sinceTags ) ) {
			$this->phpcsFile->addError(
				sprintf(
					'Missing @since tag for %s.',
					$entity
				),
				$stackPtr,
				'Missing'
			);

			return;
		}

		// Check for first tag.
		$firstTag = reset( $allTags );

		if ( '@since' !== $firstTag['content'] ) {
			$this->phpcsFile->addError(
				sprintf(
					'Expected @since as the first tag for %s.',
					$entity
				),
				reset( $sinceTags )['tag'],
				'NotFirst'
			);
		}

		foreach ( $sinceTags as $since ) {

			// Tag @since should have a version number.
			if ( ! $this->has_version( $since, $tokens ) ) {
				$this->phpcsFile->addError(
					sprintf(
						'Missing @since version for %s.',
						$entity
					),
					$since['tag'],
					'MissingVersion'
				);

				continue;
			}

			// Check for valid version for @since tag.
			if ( ! $this->is_valid_version( $since, $tokens ) ) {
				$this->phpcsFile->addError(
					sprintf(
						'Invalid @since version for %s.',
						$entity
					),
					$since['tag'],
					'InvalidVersion'
				);
			}
		}

		if ( count( $sinceTags ) > 1 ) {
			$hasProperOrder = $this->isConsecutiveAscendingNumericSeries( array_keys( $sinceTags ) );

			if ( ! $hasProperOrder ) {
				$this->phpcsFile->addError(
					sprintf(
						'Keep all @since tags together in %s.',
						$entity
					),
					reset( $sinceTags )['tag'],
					'Ungrouped'
				);
			}
		}
	}

	/**
	 * Checks if an array represents a consecutive ascending numeric series.
	 *
	 * @since 1.0.0
	 *
	 * @param array $arr The array to check.
	 * @return bool True if the array is a consecutive ascending numeric series, false otherwise.
	 */
	private function isConsecutiveAscendingNumericSeries( array $arr ): bool {
		if ( empty( $arr ) ) {
			return true;
		}

		if ( ! is_numeric( $arr[0] ) ) {
			return false;
		}

		return array_reduce(
			array_slice( $arr, 1 ),
			function ( $carry, $item ) {
				if ( false === $carry || ! is_numeric( $item ) ) {
					return false;
				}

				return $item === $carry + 1 ? $item : false;
			},
			$arr[0]
		) !== false;
	}

	/**
	 * Checks whether tag has version.
	 *
	 * @since 1.0.0
	 *
	 * @param array $tag    Tag information.
	 * @param array $tokens List of tokens.
	 * @return bool True if version is not empty, otherwise false.
	 */
	private function has_version( array $tag, array $tokens ): bool {
		$version = $tokens[ ( $tag['tag'] + 2 ) ]['content'];

		return ! empty( $version ) && T_DOC_COMMENT_STRING === $tokens[ ( $tag['tag'] + 2 ) ]['code'];
	}

	/**
	 * Checks whether version is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param array $tag    Tag information.
	 * @param array $tokens List of tokens.
	 * @return bool True if version is valid, otherwise false.
	 */
	private function is_valid_version( array $tag, array $tokens ): bool {
		$version = $tokens[ ( $tag['tag'] + 2 ) ]['content'];

		return (bool) preg_match( '/^\d+\.\d+(\.\d+)?/', $version );
	}
}
