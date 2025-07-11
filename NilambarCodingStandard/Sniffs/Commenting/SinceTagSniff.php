<?php
/**
 * SinceTagSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Commenting;

use NilambarCodingStandard\Helpers\CommentTrait;
use NilambarCodingStandard\Helpers\EntityTrait;
use WordPressCS\WordPress\Sniff;

/**
 * Detect since tag in PHPDoc.
 *
 * @since 1.0.0
 */
final class SinceTagSniff extends Sniff {

	use EntityTrait;
	use CommentTrait;

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [
			\T_FUNCTION,
			\T_CLASS,
			\T_INTERFACE,
			\T_TRAIT,
			\T_CONST,
		];
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|void Integer stack pointer to skip forward or void to continue normal file processing.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function process_token( $stackPtr ) {
		$entity = $this->get_entity_name( $this->phpcsFile, $stackPtr );
		// Bail if no doc block.
		if ( ! $this->has_doc_block( $this->phpcsFile, $stackPtr ) ) {
			return;
		}

		$tokens = $this->phpcsFile->getTokens();

		$commentStart = $this->phpcsFile->findPrevious( \T_DOC_COMMENT_OPEN_TAG, $stackPtr );
		if ( empty( $commentStart ) ) {
			return;
		}
		$commentEnd = $this->phpcsFile->findNext( \T_DOC_COMMENT_CLOSE_TAG, ( $commentStart + 1 ) );
		if ( false === $commentEnd ) {
			return;
		}

		// Use PHPCS's tokenization for tag parsing.
		$commentTags = ! empty( $tokens[ $commentStart ]['comment_tags'] ) ? $tokens[ $commentStart ]['comment_tags'] : [];
		$allTags     = [];
		foreach ( $commentTags as $tagPtr ) {
			if ( \T_DOC_COMMENT_TAG === $tokens[ $tagPtr ]['code'] ) {
				$tag        = $tokens[ $tagPtr ];
				$tag['tag'] = $tagPtr;
				$allTags[]  = $tag;
			}
		}

		$sinceTags = array_filter(
			$allTags,
			function ( $element ) {
				return '@since' === $element['content'];
			}
		);

		// Bail if no since tags.
		if ( empty( $sinceTags ) ) {
			$this->phpcsFile->addError(
				'Missing @since tag for %s.',
				$stackPtr,
				'Missing',
				[ $entity ]
			);
			return;
		}

		// Check for first tag.
		$firstTag = reset( $allTags );
		if ( '@since' === $firstTag['content'] ) {
			$commentContent   = $this->get_comment_content( $this->phpcsFile, $commentStart );
			$commentStartLine = $tokens[ $commentStart ]['line'];
			$parsedComment    = $this->get_parsed_comment_details( $commentContent, $commentStartLine, $this->phpcsFile, $commentStart );
			// Find the first tag line in the parsed comment.
			$firstTagLine       = null;
			$firstTagLineNumber = null;
			foreach ( $parsedComment as $lineNumber => $lineDetails ) {
				if ( $lineDetails['is_tag'] ) {
					$firstTagLine       = $lineDetails;
					$firstTagLineNumber = $lineNumber;
					break;
				}
			}
			if ( $firstTagLine && 'since' === $firstTagLine['tag_name'] ) {
				if ( $firstTagLineNumber > 1 ) {
					$previousLine = $parsedComment[ $firstTagLineNumber - 1 ];
					// Only warn if the previous line is a description/summary (comment_text).
					if ( 'comment_text' === $previousLine['line_type'] ) {
						$this->phpcsFile->addWarning(
							'Missing empty line before @since tag for %s.',
							$stackPtr,
							'MissingEmptyLine',
							[ $entity ]
						);
					}
				}
			}
		} else {
			// @since is not the first tag, check if there are any @since tags
			$sinceTags = array_filter(
				$allTags,
				function ( $element ) {
					return '@since' === $element['content'];
				}
			);
			if ( ! empty( $sinceTags ) ) {
				$this->phpcsFile->addWarning(
					'Expected @since as the first tag for %s.',
					reset( $sinceTags )['tag'],
					'NotFirst',
					[ $entity ]
				);
			}
		}

		foreach ( $sinceTags as $since ) {
			// Tag @since should have a version number.
			if ( ! $this->has_version( $since, $tokens ) ) {
				$this->phpcsFile->addError(
					'Missing @since version for %s.',
					$since['tag'],
					'MissingVersion',
					[ $entity ]
				);
				continue;
			}
			// Check for valid version for @since tag.
			if ( ! $this->is_valid_version( $since, $tokens ) ) {
				$this->phpcsFile->addError(
					'Invalid @since version for %s.',
					$since['tag'],
					'InvalidVersion',
					[ $entity ]
				);
			}
		}

		if ( count( $sinceTags ) > 1 ) {
			$hasProperOrder = $this->isConsecutiveAscendingNumericSeries( array_keys( $sinceTags ) );
			if ( ! $hasProperOrder ) {
				$this->phpcsFile->addError(
					'Keep all @since tags together in %s.',
					reset( $sinceTags )['tag'],
					'Ungrouped',
					[ $entity ]
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
		$versionTokenIndex = $tag['tag'] + 2;

		// Check if the token index exists.
		if ( ! isset( $tokens[ $versionTokenIndex ] ) ) {
			return false;
		}

		// Check if the token is a string.
		if ( \T_DOC_COMMENT_STRING !== $tokens[ $versionTokenIndex ]['code'] ) {
			return false;
		}

		$version = $tokens[ $versionTokenIndex ]['content'];

		return ! empty( $version );
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
		$versionTokenIndex = $tag['tag'] + 2;

		// Check if the token index exists.
		if ( ! isset( $tokens[ $versionTokenIndex ] ) ) {
			return false;
		}

		// Check if the token is a string.
		if ( \T_DOC_COMMENT_STRING !== $tokens[ $versionTokenIndex ]['code'] ) {
			return false;
		}

		$version = $tokens[ $versionTokenIndex ]['content'];

		// Check if version is not empty.
		if ( empty( $version ) ) {
			return false;
		}

		$version_part = preg_split( '/\s+/', $version )[0];

		return (bool) preg_match( '/^\d+\.\d+(\.\d+)?$/', $version_part );
	}
}
