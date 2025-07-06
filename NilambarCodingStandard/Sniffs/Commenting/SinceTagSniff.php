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

	use CommentTrait;
	use EntityTrait;

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

		// Current entity.
		$entity = $this->get_entity_name( $this->phpcsFile, $stackPtr );

		// Check for empty line (comment star) before the first @since tag only
		$firstSinceTagFound = false;
		$firstTagInDocblock = null;

		// Find the first tag in the docblock
		for ( $k = $commentStart + 1; $k < $commentEnd; $k++ ) {
			if ( \T_DOC_COMMENT_TAG === $tokens[ $k ]['code'] ) {
				$firstTagInDocblock = $k;
				break;
			}
		}

		// DEBUG: Print tokens for function_one (line 41)
		if ( isset( $tokens[ $stackPtr ]['line'] ) && $tokens[ $stackPtr ]['line'] === 41 ) {
			error_log( '=== DEBUG TOKENS for function_one (line 41) ===' );
			for ( $debug = $commentStart; $debug <= $commentEnd; $debug++ ) {
				error_log( 'Token ' . $debug . ': ' . $tokens[ $debug ]['type'] . ' = "' . $tokens[ $debug ]['content'] . '" (line ' . $tokens[ $debug ]['line'] . ')' );
			}
			error_log( '=== END DEBUG ===' );
		}

		for ( $i = $commentStart + 1; $i < $commentEnd; $i++ ) {
			if ( \T_DOC_COMMENT_TAG === $tokens[ $i ]['code'] && '@since' === $tokens[ $i ]['content'] ) {
				if ( $firstSinceTagFound ) {
					break; // Only check the first @since tag
				}
				$firstSinceTagFound = true;

				// Only check if this @since tag is the first tag in the docblock
				if ( $firstTagInDocblock === $i ) {
					// Look for the nearest non-whitespace token before @since
					$j = $i - 1;
					while ( $j > $commentStart && \T_DOC_COMMENT_WHITESPACE === $tokens[ $j ]['code'] ) {
						--$j;
					}
					// If the token before @since is not a comment star, trigger a warning
					if ( $j <= $commentStart || \T_DOC_COMMENT_STAR !== $tokens[ $j ]['code'] ) {
						$this->phpcsFile->addWarning(
							'Missing empty line (comment star) before @since tag',
							$i,
							'MissingEmptyLineBeforeSinceTag',
							[]
						);
					}
				}
			}
		}

		$allTags = $this->find_comment_tags( $this->phpcsFile, $commentStart );

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

		// FINAL: Only check the first tag in the docblock
		$firstTag = null;
		for ( $i = $commentStart + 1; $i < $commentEnd; $i++ ) {
			if ( \T_DOC_COMMENT_TAG === $tokens[ $i ]['code'] ) {
				$firstTag = $i;
				break;
			}
		}
		if ( null !== $firstTag && '@since' === $tokens[ $firstTag ]['content'] ) {
			// Look for the nearest non-whitespace token before @since
			$j = $firstTag - 1;
			while ( $j > $commentStart && \T_DOC_COMMENT_WHITESPACE === $tokens[ $j ]['code'] ) {
				--$j;
			}
			if ( $j <= $commentStart || \T_DOC_COMMENT_STAR !== $tokens[ $j ]['code'] ) {
				$this->phpcsFile->addWarning(
					'Missing empty line (comment star) before @since tag',
					$firstTag,
					'MissingEmptyLineBeforeSinceTag',
					[]
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

	/**
	 * Get warning details for missing empty line before @since tag.
	 *
	 * @since 1.0.0
	 *
	 * @param string $commentComment The comment content as string.
	 * @param int    $commentStart   The starting position of the comment in the file.
	 * @return array|null Array with warning details or null if no issue.
	 */
	private function get_since_tag_warning( string $commentComment, int $commentStart ): ?array {
		$parsedDetails = $this->get_parsed_comment_details( $commentComment );

		// Find the first @since tag
		$firstSinceLine = null;
		foreach ( $parsedDetails as $lineNumber => $details ) {
			if ( $details['is_tag'] && 'since' === $details['tag_name'] ) {
				$firstSinceLine = $lineNumber;
				break;
			}
		}

		// If no @since tag found, no warning needed
		if ( null === $firstSinceLine ) {
			return null;
		}

		// Find the first tag in the docblock
		$firstTagLine = null;
		foreach ( $parsedDetails as $lineNumber => $details ) {
			if ( $details['is_tag'] ) {
				$firstTagLine = $lineNumber;
				break;
			}
		}

		// Only check for empty line if @since is the first tag
		if ( null === $firstTagLine || $firstSinceLine !== $firstTagLine ) {
			return null;
		}

		// Count empty lines before the @since tag
		$emptyLineCount = 0;
		for ( $i = $firstSinceLine - 1; $i >= 1; $i-- ) {
			if ( ! isset( $parsedDetails[ $i ] ) ) {
				break;
			}

			$lineType = $parsedDetails[ $i ]['line_type'];

			// If we find a non-empty, non-comment-star line, stop counting
			if ( 'empty' !== $lineType && 'comment_star' !== $lineType ) {
				break;
			}

			// Count empty lines and comment stars
			if ( 'empty' === $lineType || 'comment_star' === $lineType ) {
				++$emptyLineCount;
			}
		}

		// Warning if there's not exactly one empty line
		if ( 1 !== $emptyLineCount ) {
			// Find the actual @since tag token position
			$tokens        = $this->phpcsFile->getTokens();
			$sinceTagToken = $this->phpcsFile->findNext( \T_DOC_COMMENT_TAG, $commentStart );

			// Find the specific @since tag
			while ( false !== $sinceTagToken ) {
				if ( '@since' === $tokens[ $sinceTagToken ]['content'] ) {
					break;
				}
				$sinceTagToken = $this->phpcsFile->findNext( \T_DOC_COMMENT_TAG, ( $sinceTagToken + 1 ) );
			}

			if ( false !== $sinceTagToken ) {
				$message = ( 0 === $emptyLineCount )
					? 'Missing empty line (comment star) before @since tag'
					: 'Too many empty lines before @since tag (expected exactly one)';

				return [
					'line_number'   => $tokens[ $sinceTagToken ]['line'],
					'message'       => $message,
					'expected_line' => $tokens[ $sinceTagToken ]['line'] - 1,
				];
			}
		}

		return null;
	}
}
