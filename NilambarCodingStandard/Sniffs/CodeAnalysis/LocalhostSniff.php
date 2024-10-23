<?php
/**
 * LocalhostSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Detect localhost.
 *
 * @since 1.0.0
 */
final class LocalhostSniff implements Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function register() {
		return Tokens::$textStringTokens;
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.0.0
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The PHP_CodeSniffer file where the token was found.
	 * @param int                         $stackPtr  The position of the current token in the stack.
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens  = $phpcsFile->getTokens();
		$content = $tokens[ $stackPtr ]['content'];

		if ( false === stripos( $content, '//' ) ) {
			return;
		}

		if ( preg_match_all( '#https?:\/\/(localhost|127.0.0.1|(.*\.local(host)?))\/#', $content, $matches ) > 0 ) {
			foreach ( $matches[0] as $matched_url ) {
				$phpcsFile->addError(
					'Do not use Localhost/127.0.0.1 in your code. Found: %s',
					$stackPtr,
					'Found',
					[ $matched_url ]
				);
			}
		}
	}
}
