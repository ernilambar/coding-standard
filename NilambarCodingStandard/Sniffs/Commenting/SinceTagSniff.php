<?php
/**
 * SinceTagSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Commenting;

use NilambarCodingStandard\Traits\CommentTag;
use NilambarCodingStandard\Traits\GetEntityName;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Detect since tag in PHPDoc.
 *
 * @since 1.0.0
 */
final class SinceTagSniff implements Sniff {

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
	 * Processes this sniff, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		$commentStart = $phpcsFile->findPrevious( T_DOC_COMMENT_OPEN_TAG, $stackPtr );

		if ( empty( $commentStart ) ) {
			return;
		}

		$commentEnd = $phpcsFile->findNext( T_DOC_COMMENT_CLOSE_TAG, ( $commentStart + 1 ) );

		if ( false === $commentEnd ) {
			return;
		}

		// Current entity.
		$entity = $this->get_entity_full_name( $phpcsFile, $stackPtr, $tokens );

		$allTags = $this->find_tags( $phpcsFile, $commentStart, $commentEnd );

		if ( empty( $allTags ) ) {
			$phpcsFile->addError(
				sprintf(
					'@since tag missing for %s.',
					$entity
				),
				$stackPtr,
				'Missing'
			);

			return;
		}

		$sinceTags = array_filter(
			$allTags,
			function ( $element ) {
				return '@since' === $element['content'];
			}
		);

		// Bail if no since tags.
		if ( empty( $sinceTags ) ) {
			return;
		}

		// Check for first tag.
		$firstTag = reset( $allTags );

		if ( '@since' !== $firstTag['content'] ) {
			$phpcsFile->addError(
				sprintf(
					'Expected @since as the first tag for %s.',
					$entity
				),
				reset( $sinceTags )['tag'],
				'NotFirst'
			);
		} else {
			// Find the previous line.
			$previous_content = $this->get_previous_line_content( $phpcsFile, $firstTag['tag'] );

			$previous_content = trim( str_replace( '*', '', $previous_content ) );

			if ( strlen( $previous_content ) > 0 ) {
				$phpcsFile->addError(
					sprintf(
						'Expected empty line before @since tag for %s.',
						$entity
					),
					$firstTag['tag'],
					'MissingEmptyLineBeforeTag'
				);
			}
		}

		foreach ( $sinceTags as $since ) {

			// Tag @since should have a version number.
			if ( ! $this->has_version( $since, $tokens ) ) {
				$phpcsFile->addError(
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
				$phpcsFile->addError(
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
				$phpcsFile->addError(
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

	/**
	 * Returns content of the previous line.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr The position of the current token in the stack.
	 * @return string The content of the previous line.
	 */
	private function get_previous_line_content( File $phpcsFile, int $stackPtr ): string {
		$previousLineFirstTokenPtr = $this->get_previous_line_first_token( $phpcsFile, $stackPtr );

		if ( false !== $previousLineFirstTokenPtr ) {
			$lineContent = $phpcsFile->getTokensAsString( $previousLineFirstTokenPtr, $stackPtr - $previousLineFirstTokenPtr );

			return trim( $lineContent );
		}

		return '';
	}

	/**
	 * Returns content of the next line.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  The position of the current token in the stack.
	 * @return string The content of the next line, or an empty string if there's no next line.
	 */
	private function get_next_line_content( File $phpcsFile, int $stackPtr ): string {
		$nextLineFirstTokenPtr = $this->get_next_line_first_token( $phpcsFile, $stackPtr );

		if ( false !== $nextLineFirstTokenPtr ) {
			$tokens      = $phpcsFile->getTokens();
			$totalTokens = count( $tokens );
			$nextLine    = $tokens[ $nextLineFirstTokenPtr ]['line'];

			// Determine the last token of the next line.
			$nextLineLastTokenPtr = $nextLineFirstTokenPtr;
			while ( $nextLineLastTokenPtr < $totalTokens - 1 && $tokens[ $nextLineLastTokenPtr + 1 ]['line'] === $nextLine ) {
				++$nextLineLastTokenPtr;
			}

			// Get the content of the next line as a string.
			$lineContent = $phpcsFile->getTokensAsString( $nextLineFirstTokenPtr, $nextLineLastTokenPtr - $nextLineFirstTokenPtr + 1 );

			return trim( $lineContent );
		}

		return '';
	}

	/**
	 * Get the first token of the previous line.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr The position of the current token in the stack.
	 * @return int|false The position of the first token on the previous line, or false if there is no previous line.
	 */
	private function get_previous_line_first_token( File $phpcsFile, int $stackPtr ) {
		$tokens      = $phpcsFile->getTokens();
		$currentLine = $tokens[ $stackPtr ]['line'];

		// Backtrack to reach a previous line.
		$prevPtr = $stackPtr;
		while ( $prevPtr > 0 && $tokens[ $prevPtr ]['line'] === $currentLine ) {
			--$prevPtr;
		}

		// Now find the first token on the previous line.
		if ( $prevPtr > 0 ) {
			return $phpcsFile->findFirstOnLine( [], $prevPtr, true );
		}

		return false;
	}

	/**
	 * Get the first token of the next line.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  The position of the current token in the stack.
	 * @return int|false The position of the first token on the next line, or false if there is no next line.
	 */
	private function get_next_line_first_token( File $phpcsFile, int $stackPtr ) {
		$tokens      = $phpcsFile->getTokens();
		$currentLine = $tokens[ $stackPtr ]['line'];
		$totalTokens = count( $tokens );

		// Advance to reach the next line.
		$nextPtr = $stackPtr;
		while ( $nextPtr < $totalTokens - 1 && $tokens[ $nextPtr ]['line'] === $currentLine ) {
			++$nextPtr;
		}

		// Now find the first token on the next line.
		if ( $nextPtr < $totalTokens && $tokens[ $nextPtr ]['line'] !== $currentLine ) {
			return $phpcsFile->findFirstOnLine( [], $nextPtr, true );
		}

		return false;
	}
}
