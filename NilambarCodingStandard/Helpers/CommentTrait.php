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
	 * Checks if token has an associated PHPDoc block.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  The index of the token in the stack.
	 * @return bool True if a PHPDoc block is present immediately before the token; false otherwise.
	 */
	protected function has_doc_block( File $phpcsFile, int $stackPtr ): bool {
		$tokens = $phpcsFile->getTokens();

		// Move backwards to find the previous significant token that's not a comment or whitespace.
		$prevTokenPos = $phpcsFile->findPrevious(
			[
				\T_ABSTRACT,
				\T_COMMENT,
				\T_PRIVATE,
				\T_PROTECTED,
				\T_PUBLIC,
				\T_STATIC,
				\T_WHITESPACE,
			],
			$stackPtr - 1,
			null,
			true
		);

		if ( false !== $prevTokenPos && \T_DOC_COMMENT_CLOSE_TAG === $tokens[ $prevTokenPos ]['code'] ) {
			$commentStart = $phpcsFile->findPrevious( \T_DOC_COMMENT_OPEN_TAG, $prevTokenPos );

			if ( false !== $commentStart ) {
				for ( $i = $commentStart; $i <= $prevTokenPos; $i++ ) {
					if ( ! in_array(
						$tokens[ $i ]['code'],
						[
							\T_DOC_COMMENT_OPEN_TAG,
							\T_DOC_COMMENT_WHITESPACE,
							\T_DOC_COMMENT_STAR,
							\T_DOC_COMMENT_TAG,
							\T_DOC_COMMENT_STRING,
							\T_DOC_COMMENT_CLOSE_TAG,
						],
						true
					) ) {
						return false;
					}
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Parse comment details and return structured information.
	 *
	 * @since 1.0.0
	 *
	 * @param string $commentComment The comment content as string.
	 * @param int    $commentStartLine The starting line number of the comment in the file (optional).
	 * @param File   $phpcsFile The PHPCS file object (optional, for more accurate line numbers).
	 * @param int    $commentStart The comment start token position (optional, for more accurate line numbers).
	 * @return array Array with line numbers as keys and parsed details as values.
	 */
	protected function get_parsed_comment_details( string $commentComment, int $commentStartLine = 0, File $phpcsFile = null, int $commentStart = 0 ): array {
		$lines  = explode( "\n", $commentComment );
		$result = [];

		// If we have PHPCS file and comment start, use token line information for better accuracy.
		$useTokenLines = ( null !== $phpcsFile && $commentStart > 0 );
		$tokenLines    = [];

		if ( $useTokenLines ) {
			$tokens     = $phpcsFile->getTokens();
			$commentEnd = $phpcsFile->findNext( \T_DOC_COMMENT_CLOSE_TAG, ( $commentStart + 1 ) );

			if ( false !== $commentEnd ) {
				for ( $i = $commentStart; $i <= $commentEnd; $i++ ) {
					$tokenLines[] = $tokens[ $i ]['line'];
				}
			}
		}

		foreach ( $lines as $lineNumber => $line ) {
			++$lineNumber; // Convert to 1-based indexing.
			$trimmedLine = trim( $line );

			$lineDetails = [
				'line_type'     => 'unknown',
				'is_tag'        => false,
				'tag_name'      => null,
				'content'       => $trimmedLine,
				'original_line' => $line,
			];

			// Add actual file line number if provided.
			if ( $commentStartLine > 0 ) {
				if ( $useTokenLines && isset( $tokenLines[ $lineNumber - 1 ] ) ) {
					// Use actual token line number for better accuracy.
					$lineDetails['file_line'] = $tokenLines[ $lineNumber - 1 ];
				} else {
					// Fallback to calculated line number.
					$lineDetails['file_line'] = $commentStartLine + $lineNumber - 1;
				}
			}

			if ( empty( $trimmedLine ) ) {
				$lineDetails['line_type'] = 'empty';
			} elseif ( '/**' === $trimmedLine ) { // Check if line is comment opening tag.
				$lineDetails['line_type'] = 'comment_open';
			} elseif ( '*/' === $trimmedLine ) { // Check if line is comment closing tag.
				$lineDetails['line_type'] = 'comment_close';
			} elseif ( strpos( $trimmedLine, '*' ) === 0 ) { // Check if line starts with * (comment content).
				$content = trim( substr( $trimmedLine, 1 ) );

				if ( empty( $content ) ) {
					$lineDetails['line_type'] = 'comment_star';
				} elseif ( strpos( $content, '@' ) === 0 ) {
					$lineDetails['line_type'] = 'tag';
					$lineDetails['is_tag']    = true;

					// Extract tag name.
					$tagParts                = explode( ' ', $content, 2 );
					$tagName                 = substr( $tagParts[0], 1 ); // Remove @ symbol.
					$lineDetails['tag_name'] = $tagName;

					// Add tag description if present.
					if ( isset( $tagParts[1] ) ) {
						$lineDetails['tag_description'] = trim( $tagParts[1] );
					}
				} else {
					$lineDetails['line_type'] = 'comment_text';
					$lineDetails['content']   = $content;
				}
			} else { // Default to comment text.
				$lineDetails['line_type'] = 'comment_text';
			}

			$result[ $lineNumber ] = $lineDetails;
		}

		return $result;
	}

	/**
	 * Get the actual file line number for a token.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The PHPCS file object.
	 * @param int  $stackPtr  The token position.
	 * @return int The line number in the file.
	 */
	protected function get_token_line_number( File $phpcsFile, int $stackPtr ): int {
		$tokens = $phpcsFile->getTokens();
		return $tokens[ $stackPtr ]['line'];
	}

	/**
	 * Get the comment content as a string.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The PHPCS file object.
	 * @param int  $commentStart The starting position of the comment.
	 * @return string The comment content.
	 */
	protected function get_comment_content( File $phpcsFile, int $commentStart ): string {
		$tokens = $phpcsFile->getTokens();

		$commentEnd = $phpcsFile->findNext( \T_DOC_COMMENT_CLOSE_TAG, ( $commentStart + 1 ) );

		if ( false === $commentEnd ) {
			return '';
		}

		$content = '';

		for ( $i = $commentStart; $i <= $commentEnd; $i++ ) {
			$content .= $tokens[ $i ]['content'];
		}

		return $content;
	}
}
