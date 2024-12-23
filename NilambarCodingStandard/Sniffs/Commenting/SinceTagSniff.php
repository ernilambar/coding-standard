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

		$since  = $this->find_tag( '@since', $commentStart, $tokens );
		$entity = $this->get_entity_full_name( $phpcsFile, $stackPtr, $tokens );

		if ( empty( $since ) ) {
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

			return;
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

			return;
		}

		$this->tag_spacing( $phpcsFile, $stackPtr, $since );
	}

	/**
	 * Processes and detect empty line between tags.
	 *
	 * @since 1.0.0
	 *
	 * @param File  $phpcsFile The PHP_CodeSniffer file where the token was found.
	 * @param int   $stackPtr  The position in the PHP_CodeSniffer file's token stack where the token was found.
	 * @param array $since     Since tag token.
	 *
	 * @return void
	 */
	private function tag_spacing( $phpcsFile, $stackPtr, $since ) {
		$tokens         = $phpcsFile->getTokens();
		$entity         = $this->get_entity_full_name( $phpcsFile, $stackPtr, $tokens );
		$nextAnnotation = $phpcsFile->findNext( T_DOC_COMMENT_TAG, $since['tag'] + 1 );
		$commentEnd     = $phpcsFile->findPrevious( T_DOC_COMMENT_CLOSE_TAG, $stackPtr );
		$nextAnnotation = $nextAnnotation && $tokens[ $commentEnd ]['line'] > $tokens[ $nextAnnotation ]['line'] ? $nextAnnotation : false;

		if ( ! $this->has_empty_line_before_tag( $phpcsFile, $since ) ) {
			$phpcsFile->addWarning(
				sprintf(
					'Empty line missing before @since tag for %s.',
					$entity
				),
				$since['tag'],
				'MissingEmptyLineBeforeSince'
			);
		}

		if ( ! $this->is_last_tag( $phpcsFile, $since ) && ! $this->has_empty_line_after_tag( $phpcsFile, $since ) ) {
			$phpcsFile->addWarning(
				sprintf(
					'Empty line missing after @since tag for %s.',
					$entity
				),
				$since['tag'],
				'MissingEmptyLineAfterSince'
			);
		}
	}
}
