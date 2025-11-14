<?php
/**
 * HeredocSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\CodeAnalysis;

use WordPressCS\WordPress\Sniff;

/**
 * Bans the use of heredocs.
 *
 * @since 1.0.0
 */
final class HeredocSniff extends Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int>
	 */
	public function register() {
		return array(
			T_START_HEREDOC,
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
		$data = array(
			'heredoc',
			trim( $this->tokens[ $stackPtr ]['content'] ),
		);

		$error = 'Use of heredoc syntax (%s) is not allowed; use standard strings or inline HTML instead';
		$this->phpcsFile->addError( $error, $stackPtr, 'HeredocNotAllowed', $data );
	}
}
