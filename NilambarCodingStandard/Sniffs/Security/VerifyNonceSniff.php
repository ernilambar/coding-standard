<?php
/**
 * VerifyNonceSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Security;

use NilambarCodingStandard\Helpers\AbstractSniffHelper;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Check for buggy/insecure use of wp_verify_nonce()
 *
 * @package Nilambar_Coding_Standard
 *
 * @since 1.0.0
 */
final class VerifyNonceSniff extends AbstractSniffHelper {

	/**
	 * Does the given scope contain an exit, die, wp_send_json_error(), or similar statement that's sufficient to handle a nonce failure?
	 *
	 * @since 1.0.0
	 *
	 * @param int $start The start position.
	 * @param int $end   The end position.
	 * @return int|false The position of the error terminator or false.
	 */
	protected function scope_contains_error_terminator( $start, $end ) {

		$tokens_to_search =
			Tokens::$functionNameTokens +
			array( \T_RETURN => \T_RETURN );

		$stackPtr = $this->phpcsFile->findNext( $tokens_to_search, $start, $end, false, null, false );
		while ( $stackPtr <= $end && $stackPtr ) {
			if ( in_array(
				$this->tokens[ $stackPtr ]['content'],
				array(
					'exit',
					'die',
					'wp_send_json_error',
					'wp_nonce_ays',
					'return',
				),
				true
			) ) {
				return $stackPtr;
			}

			$stackPtr = $this->phpcsFile->findNext( $tokens_to_search, $stackPtr + 1, $end, false, null, false );
		}

		return false;
	}

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int>
	 */
	public function register() {
		return Tokens::$functionNameTokens;
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|void Integer stack pointer to skip forward or void to continue
	 *                  normal file processing.
	 */
	public function process_token( $stackPtr ) {
		if ( 'wp_verify_nonce' === $this->tokens[ $stackPtr ]['content'] ) {
			$ifPtr = $this->is_conditional_expression( $stackPtr );
			if ( $ifPtr ) {
				// We're in a conditional, something like if ( wp_verify_nonce() ).

				if ( $this->expression_is_negated( $stackPtr ) ) {
					// If !wp_verify_nonce() condition.

					list( $expression_start, $expression_end ) = $this->get_expression_from_condition( $ifPtr );
					list( $scope_start, $scope_end )           = $this->get_scope_from_condition( $ifPtr );

					// if ( $something && ! wp_verify_nonce( ... ) ).
					if ( $this->expression_contains_and( $expression_start, $expression_end ) && $this->scope_contains_error_terminator( $scope_start, $scope_end ) ) {
						$andPtr = $this->expression_contains_and( $expression_start, $expression_end );
						if ( $andPtr < $stackPtr ) {
							// if ( ..something.. && ! wp_verify_nonce() ... ).
							$operand_functions = array_count_values( $this->find_functions_in_expression( $expression_start, $andPtr ) );
							// if ( ... wp_verify_nonce() && ! wp_verify_nonce() ... ).
							if ( isset( $operand_functions['wp_verify_nonce'] ) ) {
								// This is ok, and we will have already checked the previous wp_verify_nonce(), so skip.
								return;
							}
						} else {
							// if ( ... !wp_verify_nonce() && ..something.. ).
							// This is ok since the nonce call comes before the &&.
							return;
						}
						$this->phpcsFile->addError(
							'Unsafe use of wp_verify_nonce() in expression %s.',
							$stackPtr,
							'UnsafeVerifyNonceNegatedAnd',
							array( $this->tokens_as_string( $expression_start, $expression_end ) ), // [ $unsafe_expression, $method, $methodParam[ 'clean' ], rtrim( "\n" . join( "\n", $extra_context ) ) ].
							0,
							false
						);

					}
				} else {
					// if ( wp_verify_nonce() ).
					// In this case we want the else {} part.
					$elsePtr = $this->has_else( $ifPtr );
					if ( $elsePtr ) {
						list( $expression_start, $expression_end ) = $this->get_expression_from_condition( $ifPtr );
						list( $scope_start, $scope_end )           = $this->get_scope_from_condition( $elsePtr );

						// if ( $something || wp_verify_nonce( ... ) ).
						if ( $this->expression_contains_or( $expression_start, $expression_end ) && $this->scope_contains_error_terminator( $scope_start, $scope_end ) ) {

							$orPtr = $this->expression_contains_or( $expression_start, $expression_end );
							if ( $orPtr < $stackPtr ) {
								// if ( ..something.. || wp_verify_nonce() ... ).
								$operand_functions = array_count_values( $this->find_functions_in_expression( $expression_start, $orPtr ) );
								// If the previous "something" was another wp_verify_nonce() call then we're fine, ignore.
								if ( isset( $operand_functions['wp_verify_nonce'] ) ) {
									return;
								}
							} else {
								// if ( wp_verify_nonce || ..something.. ).
								$operand_functions = array_count_values( $this->find_functions_in_expression( $orPtr, $expression_end ) );
								// If the next "something" is another wp_verify_nonce() call then we're fine, ignore.
								if ( isset( $operand_functions['wp_verify_nonce'] ) ) {
									return;
								}
							}
							$this->phpcsFile->addError(
								'Possibly unsafe use of wp_verify_nonce() in expression %s.',
								$stackPtr,
								'UnsafeVerifyNonceElse',
								array( $this->tokens_as_string( $expression_start, $expression_end ) ), // [ $unsafe_expression, $method, $methodParam[ 'clean' ], rtrim( "\n" . join( "\n", $extra_context ) ) ].
								0,
								false
							);
						}
					}
				}
			} elseif ( ! $this->is_return_statement( $stackPtr ) && ! $this->is_assignment_statement( $stackPtr ) ) {

					// wp_verify_nonce() used as an unconditional statement - most likely mistaken for check_admin_referer().
					$this->phpcsFile->addError(
						'Unconditional call to wp_verify_nonce(). Consider using check_admin_referer() instead.',
						$stackPtr,
						'UnsafeVerifyNonceStatement',
						array(),
						0,
						false
					);
			}
		}
	}
}
