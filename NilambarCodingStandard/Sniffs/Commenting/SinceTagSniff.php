<?php
/**
 * SinceTagSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Commenting;

use NilambarCodingStandard\Traits\CommentTag;
use NilambarCodingStandard\Traits\GetEntityName;
use NilambarCodingStandard\Traits\Version;
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
	use Version;

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
	}
}
