<?php
/**
 * AllCapsCommentSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Commenting;

use WordPressCS\WordPress\Sniff;

/**
 * Detects comment in all capital letters.
 *
 * @since 1.0.0
 */
final class AllCapsCommentSniff extends Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return array(
			\T_COMMENT,
			\T_DOC_COMMENT,
		);
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
		$content = $this->tokens[ $stackPtr ]['content'];

		// Split multi-line comments into lines.
		$lines = preg_split( '/\R/', $content );

		foreach ( $lines as $line ) {
			// Remove comment markers and trim.
			$line = preg_replace( '/^\s*(\/\/|#|\/\*+|\*\/|\*)/', '', $line );
			$line = trim( $line );

			// Skip empty lines or lines that are just comment markers.
			if ( '' === $line ) {
				continue;
			}

			// Only check lines with at least one letter.
			if ( preg_match( '/[a-z]/i', $line ) && strtoupper( $line ) === $line ) {
				$this->phpcsFile->addWarning(
					'Avoid using all capital letters in comments.',
					$stackPtr,
					'Found'
				);

				break;
			}
		}
	}
}
