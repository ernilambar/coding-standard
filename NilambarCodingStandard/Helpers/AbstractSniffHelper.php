<?php
/**
 * AbstractSniffHelper
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Helpers;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\PassedParameters;
use WordPressCS\WordPress\Sniff;

/**
 * A base class for building more complex context-aware sniffs.
 *
 * @since 1.0.0
 */
abstract class AbstractSniffHelper extends Sniff {

	/**
	 * Tokens that indicate the start of a function call or other non-constant string
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, int>
	 */
	protected $function_tokens = [
		\T_OBJECT_OPERATOR     => \T_OBJECT_OPERATOR,
		\T_DOUBLE_COLON        => \T_DOUBLE_COLON,
		\T_OPEN_CURLY_BRACKET  => \T_OPEN_CURLY_BRACKET,
		\T_OPEN_SQUARE_BRACKET => \T_OPEN_SQUARE_BRACKET,
		\T_OPEN_PARENTHESIS    => \T_OPEN_PARENTHESIS,
		\T_OBJECT              => \T_OBJECT,
	];

	/**
	 * Keep track of variable assignments.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, array<int, array<int, string>>>
	 */
	protected $assignments = [];

	/**
	 * Used by parent class for providing extra context from some methods.
	 *
	 * @since 1.0.0
	 *
	 * @var int|null
	 */
	protected $i = null;

	/**
	 * Used by parent class for providing extra context from some methods.
	 *
	 * @since 1.0.0
	 *
	 * @var int|null
	 */
	protected $end = null;

	/**
	 * Get the name of the function containing the code at a given point.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return string|false The function name or false if not in a function.
	 */
	public function get_function_name( $stackPtr ) {
		$condition = $this->phpcsFile->getCondition( $stackPtr, \T_FUNCTION );
		if ( false !== $condition ) {
			return $this->phpcsFile->getDeclarationName( $condition );
		}

		return false;
	}

	/**
	 * Get the scope context of the code at a given point.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|string The context (function/closure ID or 'global').
	 */
	public function get_context( $stackPtr ) {
		$context = $this->phpcsFile->getCondition( $stackPtr, \T_CLOSURE );
		if ( $context ) {
			return $context;
		}
		$context = $this->phpcsFile->getCondition( $stackPtr, \T_FUNCTION );
		if ( $context ) {
			return $context;
		}
		return 'global';
	}

	/**
	 * Helper function to return the next non-empty token starting at $stackPtr inclusive.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $stackPtr  The position of the current token in the stack.
	 * @param bool $local_only Whether to only look in the current scope.
	 * @return int|false The next non-empty token position or false.
	 */
	protected function next_non_empty( $stackPtr, $local_only = true ) {
		return $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr, null, true, null, $local_only );
	}

	/**
	 * Find the previous non-empty token starting at $stackPtr inclusive.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $stackPtr  The position of the current token in the stack.
	 * @param bool $local_only Whether to only look in the current scope.
	 * @return int|false The previous non-empty token position or false.
	 */
	protected function previous_non_empty( $stackPtr, $local_only = true ) {
		return $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $stackPtr, null, true, null, $local_only );
	}

	/**
	 * Get the variable at $stackPtr as a string.
	 * Works with complex variables like $foo[0]->bar.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return string|false The variable as a string or false.
	 */
	protected function get_variable_as_string( $stackPtr ) {

		if ( \T_VARIABLE !== $this->tokens[ $stackPtr ]['code'] ) {
			return false;
		}

		$i     = $stackPtr + 1;
		$limit = 200;
		$out   = $this->tokens[ $stackPtr ]['content'];

		while ( $limit > 0 ) {
			// Find the next non-empty token.
			$nextToken = $this->phpcsFile->findNext( Tokens::$emptyTokens, $i, null, true, null, true );

			if ( \T_OPEN_SQUARE_BRACKET === $this->tokens[ $nextToken ]['code'] ) {
				// If it's an array, take everything between the brackets as part of the variable name.
				for ( $i = $nextToken; $i <= $this->tokens[ $nextToken ]['bracket_closer']; $i++ ) {
					if ( ! in_array( $this->tokens[ $i ]['code'], Tokens::$emptyTokens, true ) ) {
						$out .= $this->tokens[ $i ]['content'];
					}
				}
			} elseif ( \T_OBJECT_OPERATOR === $this->tokens[ $nextToken ]['code']
				|| \T_DOUBLE_COLON === $this->tokens[ $nextToken ]['code'] ) {
				// If it's :: or -> then check if the following thing is a string.
				$objectThing = $this->phpcsFile->findNext( Tokens::$emptyTokens, $nextToken + 1, null, true, null, true );

				// It could be a variable name or function name.
				if ( \T_STRING === $this->tokens[ $objectThing ]['code'] ) {
					$lookAhead = $this->phpcsFile->findNext( Tokens::$emptyTokens, $objectThing + 1, null, true, null, true );
					if ( \T_OPEN_PARENTHESIS === $this->tokens[ $lookAhead ]['code'] ) {
						// It's a function name, so ignore it.
						break;
					}
					$out .= '->' . $this->tokens[ $objectThing ]['content'];
					$i    = $objectThing + 1;
				} elseif ( \T_LNUMBER === $this->tokens[ $objectThing ]['code'] ) {
					// It's a numeric array index.
					$out .= '[' . $this->tokens[ $objectThing ]['content'] . ']';
					$i    = $objectThing + 1;

				} else {
					++$i;
				}
			} elseif ( \T_CLOSE_SQUARE_BRACKET === $this->tokens[ $nextToken ]['code'] ) {
				// It's a ] so see what's next.
				++$i;
			} else {
				// Anything else is not part of a variable so stop here.
				break;
			}

			--$limit;
		}

		$this->i = $i - 1;
		return $out;
	}

	/**
	 * Check if this variable is being assigned a value.
	 * Copied from WordPressCS\WordPress\Sniff with improvements
	 *
	 * E.g., $var = 'foo';
	 *
	 * Also handles array assignments to arbitrary depth:
	 *
	 * $array['key'][ $foo ][ something() ] = $bar;
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The index of the token in the stack. This must point to
	 *                      either a T_VARIABLE or T_CLOSE_SQUARE_BRACKET token.
	 * @return bool Whether the token is a variable being assigned a value.
	 */
	protected function is_assignment( $stackPtr ) {

		static $valid = [
			\T_VARIABLE             => true,
			\T_CLOSE_SQUARE_BRACKET => true,
			\T_STRING               => true,
		];

		// Must be a variable, constant or closing square bracket (see below).
		if ( ! isset( $valid[ $this->tokens[ $stackPtr ]['code'] ] ) ) {
			return false;
		}

		$next_non_empty = $this->phpcsFile->findNext(
			Tokens::$emptyTokens,
			( $stackPtr + 1 ),
			null,
			true,
			null,
			true
		);

		// No token found.
		if ( false === $next_non_empty ) {
			return false;
		}

		// If the next token is an assignment, that's all we need to know.
		if ( isset( Tokens::$assignmentTokens[ $this->tokens[ $next_non_empty ]['code'] ] ) ) {
			return true;
		}

		// Check if this is an array assignment, e.g., `$var['key'] = 'val';`.
		if ( \T_OPEN_SQUARE_BRACKET === $this->tokens[ $next_non_empty ]['code']
			&& isset( $this->tokens[ $next_non_empty ]['bracket_closer'] )
		) {
			return $this->is_assignment( $this->tokens[ $next_non_empty ]['bracket_closer'] );
		} elseif ( \T_OBJECT_OPERATOR === $this->tokens[ $next_non_empty ]['code'] ) {
			return $this->is_assignment( $next_non_empty + 1 );
		}

		return false;
	}

	/**
	 * Get tokens between two pointers as a string.
	 *
	 * @since 1.0.0
	 *
	 * @param int $start The start position.
	 * @param int $end The end position.
	 * @return string The tokens as a string.
	 */
	protected function tokens_as_string( $start, $end ) {
		return $this->phpcsFile->getTokensAsString( $start, $end - $start + 1 );
	}

	/**
	 * Is $stackPtr part of the conditional expression in an `if` statement?
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|false The if statement pointer or false.
	 */
	protected function is_conditional_expression( $stackPtr ) {
		if ( isset( $this->tokens[ $stackPtr ]['nested_parenthesis'] ) ) {
			foreach ( array_reverse( $this->tokens[ $stackPtr ]['nested_parenthesis'], true ) as $start => $end ) {
				if ( isset( $this->tokens[ $start ]['parenthesis_owner'] ) ) {
					$ownerPtr = $this->tokens[ $start ]['parenthesis_owner'];
					if ( in_array( $this->tokens[ $ownerPtr ]['code'], [ \T_IF, \T_ELSEIF ], true ) ) {
						return $ownerPtr;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Get the conditional expression part of an if/elseif statement.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return array<int, int>|false Array with start and end positions or false.
	 */
	protected function get_expression_from_condition( $stackPtr ) {
		if ( isset( $this->tokens[ $stackPtr ]['parenthesis_opener'] ) ) {
			return [ $this->tokens[ $stackPtr ]['parenthesis_opener'], $this->tokens[ $stackPtr ]['parenthesis_closer'] ];
		}
		return false;
	}

	/**
	 * Get the scope part of an if/else/elseif statement.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return array<int, int>|false Array with start and end positions or false.
	 */
	protected function get_scope_from_condition( $stackPtr ) {
		if ( ! in_array( $this->tokens[ $stackPtr ]['code'], [ \T_IF, \T_ELSEIF, \T_ELSE ], true ) ) {
			return false;
		}
		if ( isset( $this->tokens[ $stackPtr ]['scope_opener'] ) ) {
			// Has a scope block with curly braces.
			return [ $this->tokens[ $stackPtr ]['scope_opener'], $this->tokens[ $stackPtr ]['scope_closer'] ];
		} elseif ( isset( $this->tokens[ $stackPtr ]['parenthesis_closer'] ) ) {
			// Has parentheses but no scope block.
			$start = $this->next_non_empty( $this->tokens[ $stackPtr ]['parenthesis_closer'] + 1 );
			$end   = $this->phpcsFile->findEndOfStatement( $start );
			return [ $start, $end ];
		} else {
			// No parentheses or scope block.
			$start = $this->next_non_empty( $stackPtr + 1 );
			$end   = $this->phpcsFile->findEndOfStatement( $start );
			return [ $start, $end ];
		}
		return false;
	}

	/**
	 * Does the given if statement have an 'else' or 'elseif'
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|false The else/elseif pointer or false.
	 */
	protected function has_else( $stackPtr ) {
		if ( isset( $this->tokens[ $stackPtr ]['scope_closer'] ) ) {
			// Has a scope block with curly braces.
			$nextPtr = $this->next_non_empty( $this->tokens[ $stackPtr ]['scope_closer'] + 1 );
			if ( $nextPtr && in_array( $this->tokens[ $nextPtr ]['code'], [ \T_ELSE, \T_ELSEIF ], true ) ) {
				return $nextPtr;
			}
		} else {
			// No scope block, check after the statement.
			$endPtr  = $this->phpcsFile->findEndOfStatement( $stackPtr );
			$nextPtr = $this->next_non_empty( $endPtr + 1 );
			if ( $nextPtr && in_array( $this->tokens[ $nextPtr ]['code'], [ \T_ELSE, \T_ELSEIF ], true ) ) {
				return $nextPtr;
			}
		}
		return false;
	}

	/**
	 * Is the expression part of a return statement?
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|false The return statement pointer or false.
	 */
	protected function is_return_statement( $stackPtr ) {
		$start = $this->phpcsFile->findStartOfStatement( $stackPtr );
		if ( \T_RETURN === $this->tokens[ $start ]['code'] ) {
			return $start;
		}

		return false;
	}

	/**
	 * Is the expression part of an assignment?
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|false The assignment statement pointer or false.
	 */
	protected function is_assignment_statement( $stackPtr ) {
		$start = $this->phpcsFile->findStartOfStatement( $stackPtr );
		while ( ! empty( $this->tokens[ $start ]['nested_parenthesis'] ) ) {
			$paren = array_key_first( $this->tokens[ $start ]['nested_parenthesis'] );
			$start = $this->phpcsFile->findStartOfStatement( $paren - 1 );
		}
		return $this->is_assignment( $start );
	}

	/**
	 * Find the token following the end of the current function call pointed to by $stackPtr.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|false The end of function call position or false.
	 */
	protected function end_of_function_call( $stackPtr ) {
		if ( ! in_array( $this->tokens[ $stackPtr ]['code'], Tokens::$functionNameTokens, true ) ) {
			return false;
		}

		$function_params = PassedParameters::getParameters( $this->phpcsFile, $stackPtr );
		$param           = end( $function_params );
		if ( $param ) {
			return $this->next_non_empty( $param['end'] + 1 );
		}

		return false;
	}

	/**
	 * Does the given expression contain multiple 'and' clauses like `$foo && bar()` or `foo() and $bar`?
	 *
	 * @since 1.0.0
	 *
	 * @param int  $start           The first pointer of the expression to check.
	 * @param int  $end             The last pointer of the expression to check.
	 * @param bool $inside_brackets Whether or not to check inside nested parentheses inside the expression.
	 * @return int|false The position of the 'and' operator or false.
	 */
	protected function expression_contains_and( $start, $end, $inside_brackets = false ) {
		$tokens = [
			\T_BOOLEAN_AND => \T_BOOLEAN_AND,
			\T_LOGICAL_AND => \T_LOGICAL_AND,
		];

		if ( $inside_brackets ) {
			return $this->phpcsFile->findNext( $tokens, $start, $end, false, null, false );
		}

		$brackets = [
			\T_OPEN_PARENTHESIS => \T_OPEN_PARENTHESIS,
		];

		$nextPtr = $start;
		do {
			$nextPtr = $this->phpcsFile->findNext( $tokens + $brackets, $nextPtr + 1, $end, false, null, false );
			if ( \T_OPEN_PARENTHESIS === $this->tokens[ $nextPtr ]['code'] ) {
				$nextPtr = $this->tokens[ $nextPtr ]['parenthesis_closer'];
			} elseif ( $nextPtr ) {
				return $nextPtr;
			}
		} while ( $nextPtr && $nextPtr <= $end );

		return false;
	}

	/**
	 * Does the given expression contain multiple 'or' clauses like `$foo || bar()` or `foo() or $bar`?
	 *
	 * @since 1.0.0
	 *
	 * @param int  $start           The first pointer of the expression to check.
	 * @param int  $end             The last pointer of the expression to check.
	 * @param bool $inside_brackets Whether or not to check inside nested parentheses inside the expression.
	 * @return int|false The position of the 'or' operator or false.
	 */
	protected function expression_contains_or( $start, $end, $inside_brackets = false ) {
		$tokens = [
			\T_BOOLEAN_OR => \T_BOOLEAN_OR,
			\T_LOGICAL_OR => \T_LOGICAL_OR,
		];

		if ( $inside_brackets ) {
			return $this->phpcsFile->findNext( $tokens, $start, $end, false, null, false );
		}

		$brackets = [
			\T_OPEN_PARENTHESIS => \T_OPEN_PARENTHESIS,
		];

		$nextPtr = $start;
		do {
			$nextPtr = $this->phpcsFile->findNext( $tokens + $brackets, $nextPtr + 1, $end, false, null, false );
			if ( \T_OPEN_PARENTHESIS === $this->tokens[ $nextPtr ]['code'] ) {
				$nextPtr = $this->tokens[ $nextPtr ]['parenthesis_closer'];
			} elseif ( $nextPtr ) {
				return $nextPtr;
			}
		} while ( $nextPtr && $nextPtr <= $end );

		return false;
	}

	/**
	 * Is the expression immediately preceded by a boolean not `!`?
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|false The position of the '!' operator or false.
	 */
	protected function expression_is_negated( $stackPtr ) {
		$previous = $this->previous_non_empty( $stackPtr - 1 );
		if ( \T_BOOLEAN_NOT === $this->tokens[ $previous ]['code'] ) {
			return $previous;
		}

		return false;
	}

	/**
	 * Get the expression starting at $stackPtr as a string.
	 * A slightly more convenient wrapper around getTokensAsString().
	 *
	 * @since 1.0.0
	 *
	 * @param int      $stackPtr The position of the current token in the stack.
	 * @param int|null $endPtr   The end position or null to auto-detect.
	 * @return string The expression as a string.
	 */
	protected function get_expression_as_string( $stackPtr, $endPtr = null ) {
		if ( null === $endPtr ) {
			$endPtr = $this->find_end_of_expression( $stackPtr );
		}
		return trim( $this->phpcsFile->getTokensAsString( $stackPtr, $endPtr - $stackPtr + 1 ) );
	}

	/**
	 * Find interpolated variable names in a "string" or heredoc.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr Stack pointer to a double quoted string or heredoc.
	 * @return array<string>|false Array of variable names, or false if $stackPtr was not a double quoted string or heredoc.
	 */
	protected function get_interpolated_variables( $stackPtr ) {
		// It must be an interpolated string.
		if ( in_array( $this->tokens[ $stackPtr ]['code'], [ \T_DOUBLE_QUOTED_STRING, \T_HEREDOC ], true ) ) {
			$out = [];
			// Regex for complex variables in strings.
			$regex = '/\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(?:\[[^\]]+\])*(?:->[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*/';
			if ( preg_match_all( $regex, $this->tokens[ $stackPtr ]['content'], $matches ) ) {
				foreach ( $matches[0] as $var ) {
					// Normalize variations with curly braces and dollar signs.
					$out[] = '$' . trim( $var, '${}' );
				}
			}
			return $out;
		}

		return false;
	}

	/**
	 * Is the T_STRING at $stackPtr a constant?
	 * Will accept language constants as set by define(), and class constants.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return bool Whether the token is a constant.
	 */
	protected function is_defined_constant( $stackPtr ) {
		// It must be a string.
		$ok_tokens = [
			\T_SELF,
			\T_PARENT,
			\T_STRING,
		];
		if ( ! in_array( $this->tokens[ $stackPtr ]['code'], $ok_tokens, true ) ) {
			return false;
		}

		// It could be a function call or similar. That depends on what comes after it.

		$nextToken = $this->next_non_empty( $stackPtr + 1 );
		if ( \T_DOUBLE_COLON === $this->tokens[ $nextToken ]['code'] ) {
			// It might be a class constant like self::MYCONST or Table::MYCONST.
			$nextToken = $this->next_non_empty( $nextToken + 1 );
			if ( \T_STRING !== $this->tokens[ $nextToken ]['code'] ) {
				// Must be self::$myvar or something else that we don't recognize.
				return false;
			}
		}
		if ( in_array( $this->tokens[ $nextToken ]['code'], $this->function_tokens, true ) ) {
			// It's followed by a paren or similar, so it's not a constant.
			return false;
		}

		return true;
	}

	/**
	 * Find the end of the current expression, being aware of bracket context etc.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int A pointer to the last token in the expression.
	 */
	protected function find_end_of_expression( $stackPtr ) {

		if ( isset( $this->tokens[ $stackPtr ]['parenthesis_closer'] ) ) {
			return $this->tokens[ $stackPtr ]['parenthesis_closer'];
		}

		$stops = [
			\T_SEMICOLON,
			\T_COMMA,
			\T_CLOSE_TAG,
		];
		$prev  = $stackPtr;
		$next  = $this->next_non_empty( $stackPtr );
		while ( $next ) {
			if ( in_array( $this->tokens[ $next ]['code'], $stops, true ) ) {
				return $prev;
			}
			// If we found nested parens, jump to the end.
			if ( \T_OPEN_PARENTHESIS === $this->tokens[ $next ]['code'] && isset( $this->tokens[ $next ]['parenthesis_closer'] ) ) {
				$prev = $this->tokens[ $next ]['parenthesis_closer'];
				$next = $prev + 1;
				continue;
			}

			$prev = $next;
			$next = $this->next_non_empty( $next + 1 );
		}

		return $next - 1;
	}

	/**
	 * Find the end of the complex variable at $stackPtr.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|false A pointer to the last token in the variable name or false.
	 */
	protected function find_end_of_variable( $stackPtr ) {
		$_i      = $this->i;
		$this->i = null;
		$out     = false;
		$var     = $this->get_variable_as_string( $stackPtr );
		if ( $var && ! is_null( $this->i ) ) {
			$out = $this->i;
		}
		$this->i = $_i;
		return $out;
	}

	/**
	 * Return a list of variable names found within the expression starting at $stackPtr.
	 * Note that this returns variable names as strings, not pointers, and includes interpolated variables.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $stackPtr The position of the current token in the stack.
	 * @param int|null $endPtr   The end position or null to auto-detect.
	 * @return array<string> Array of variable names.
	 */
	protected function find_variables_in_expression( $stackPtr, $endPtr = null ) {
		$tokens_to_find = [
			\T_VARIABLE             => \T_VARIABLE,
			\T_DOUBLE_QUOTED_STRING => \T_DOUBLE_QUOTED_STRING,
			\T_HEREDOC              => \T_HEREDOC,
		];

		if ( is_null( $endPtr ) ) {
			$endPtr = $this->find_end_of_expression( $stackPtr );
		}

		$out = [];

		$newPtr = $stackPtr;
		do {
			if ( in_array( $this->tokens[ $newPtr ]['code'], [ \T_DOUBLE_QUOTED_STRING, \T_HEREDOC ], true ) ) {
				$out = array_merge( $out, $this->get_interpolated_variables( $newPtr ) );
			} elseif ( \T_VARIABLE === $this->tokens[ $newPtr ]['code'] ) {
				$out[] = $this->get_variable_as_string( $newPtr );
			}
		// @codingStandardsIgnoreLine
		} while ( $newPtr = $this->phpcsFile->findNext( $tokens_to_find, $newPtr + 1, $endPtr, false, null, true ) );

		return $out;
	}

	/**
	 * Return a list of function calls found within the expression starting at $stackPtr.
	 * Note that this returns function names as strings. It does not handle variable functions or method calls.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $stackPtr The position of the current token in the stack.
	 * @param int|null $endPtr   The end position or null to auto-detect.
	 * @return array<string> Array of function names.
	 */
	protected function find_functions_in_expression( $stackPtr, $endPtr = null ) {
		$out = [];

		$newPtr = $stackPtr;
		// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		while ( $newPtr = $this->phpcsFile->findNext( [ \T_STRING ], $newPtr, $endPtr, false, null, true ) ) {
			$lookahead = $this->next_non_empty( $newPtr + 1 );
			if ( $lookahead && ( is_null( $endPtr ) || $lookahead <= $endPtr ) ) {
				if ( \T_OPEN_PARENTHESIS === $this->tokens[ $lookahead ]['code'] ) {
					$out[] = $this->tokens[ $newPtr ]['content'];
				}
			}
			$newPtr = $lookahead + 1;
		}

		return $out;
	}

	/**
	 * Determine if a given line has any of the supplied sniff rule names suppressed.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $stackPtr A pointer to the line in question.
	 * @param array $sniffs   A list of sniff rule names to check, e.g. ['WordPress.DB.PreparedSQL.NotPrepared'].
	 * @return bool Whether the line has any of the specified sniffs suppressed.
	 */
	public function is_suppressed_line( $stackPtr, $sniffs ) {
		if ( empty( $this->tokens[ $stackPtr ]['line'] ) ) {
			return false;
		}

		// We'll check all lines related to this function call, because placement can differ depending on exactly where we trigger in a multi-line expression.
		$end = $this->end_of_function_call( $stackPtr );
		if ( $end < $stackPtr ) {
			$end = $stackPtr;
		}

		for ( $ptr = $stackPtr; $ptr <= $end; $ptr++ ) {
			foreach ( $sniffs as $sniff_name ) {
				$line_no = $this->tokens[ $ptr ]['line'];
				if ( ! empty( $this->phpcsFile->tokenizer->ignoredLines[ $line_no ] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Based on the function from wp-includes/wp-db.php
	 *
	 * @since 1.0.0
	 *
	 * @param string $query The SQL query.
	 * @return string|false The table name or false if not found.
	 */
	protected function get_table_from_query( $query ) {
		// Remove characters that can legally trail the table name.
		$query = rtrim( $query, ';/-#' );

		// Allow (select...) union [...] style queries. Use the first query's table name.
		$query = ltrim( $query, "\r\n\t (" );

		// Strip everything between parentheses except nested selects.
		$query = preg_replace( '/\((?!\s*select)[^(]*?\)/is', '()', $query );

		// Quickly match most common queries.
		if ( preg_match(
			'/^\s*(?:'
				. 'SELECT.*?\s+FROM'
				. '|INSERT(?:\s+LOW_PRIORITY|\s+DELAYED|\s+HIGH_PRIORITY)?(?:\s+IGNORE)?(?:\s+INTO)?'
				. '|REPLACE(?:\s+LOW_PRIORITY|\s+DELAYED)?(?:\s+INTO)?'
				. '|UPDATE(?:\s+LOW_PRIORITY)?(?:\s+IGNORE)?'
				. '|DELETE(?:\s+LOW_PRIORITY|\s+QUICK|\s+IGNORE)*(?:.+?FROM)?'
			. ')\s+((?:[0-9a-zA-Z$_.`-]|[\xC2-\xDF][\x80-\xBF])+)/is',
			$query,
			$maybe
		) ) {
			return str_replace( '`', '', $maybe[1] );
		}

		// SHOW TABLE STATUS and SHOW TABLES WHERE Name = 'wp_posts'.
		if ( preg_match( '/^\s*SHOW\s+(?:TABLE\s+STATUS|(?:FULL\s+)?TABLES).+WHERE\s+Name\s*=\s*("|\')((?:[0-9a-zA-Z$_.-]|[\xC2-\xDF][\x80-\xBF])+)\\1/is', $query, $maybe ) ) {
			return $maybe[2];
		}

		/*
		 * SHOW TABLE STATUS LIKE and SHOW TABLES LIKE 'wp\_123\_%'
		 * This quoted LIKE operand seldom holds a full table name.
		 * It is usually a pattern for matching a prefix so we just
		 * strip the trailing % and unescape the _ to get 'wp_123_'
		 * which drop-ins can use for routing these SQL statements.
		 */
		if ( preg_match( '/^\s*SHOW\s+(?:TABLE\s+STATUS|(?:FULL\s+)?TABLES)\s+(?:WHERE\s+Name\s+)?LIKE\s*("|\')((?:[\\\\0-9a-zA-Z$_.-]|[\xC2-\xDF][\x80-\xBF])+)%?\\1/is', $query, $maybe ) ) {
			return str_replace( '\\_', '_', $maybe[2] );
		}

		// Big pattern for the rest of the table-related queries.
		if ( preg_match(
			'/^\s*(?:'
				. '(?:EXPLAIN\s+(?:EXTENDED\s+)?)?SELECT.*?\s+FROM'
				. '|DESCRIBE|DESC|EXPLAIN|HANDLER'
				. '|(?:LOCK|UNLOCK)\s+TABLE(?:S)?'
				. '|(?:RENAME|OPTIMIZE|BACKUP|RESTORE|CHECK|CHECKSUM|ANALYZE|REPAIR).*\s+TABLE'
				. '|TRUNCATE(?:\s+TABLE)?'
				. '|CREATE(?:\s+TEMPORARY)?\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?'
				. '|ALTER(?:\s+IGNORE)?\s+TABLE'
				. '|DROP\s+TABLE(?:\s+IF\s+EXISTS)?'
				. '|CREATE(?:\s+\w+)?\s+INDEX.*\s+ON'
				. '|DROP\s+INDEX.*\s+ON'
				. '|LOAD\s+DATA.*INFILE.*INTO\s+TABLE'
				. '|(?:GRANT|REVOKE).*ON\s+TABLE'
				. '|SHOW\s+(?:.*FROM|.*TABLE)'
			. ')\s+\(*\s*((?:[0-9a-zA-Z$_.`-]|[\xC2-\xDF][\x80-\xBF])+)\s*\)*/is',
			$query,
			$maybe
		) ) {
			return str_replace( '`', '', $maybe[1] );
		}

		return false;
	}

	/**
	 * Find assignments for a variable in the current scope.
	 *
	 * @since 1.0.0
	 *
	 * @param int         $stackPtr The position of the current token in the stack.
	 * @param string|null $var_name The variable name to search for.
	 * @return array|false The assignments or false if not found.
	 */
	protected function find_assignments( $stackPtr, $var_name = null ) {
		if ( is_null( $var_name ) && \T_VARIABLE !== $this->tokens[ $stackPtr ]['code'] ) {
			return false;
		}

		// Find the closure or function scope of the variable.
		$context = $this->get_context( $stackPtr );

		if ( is_null( $var_name ) ) {
			$var = $this->get_variable_as_string( $stackPtr );
		} else {
			$var = $var_name;
		}

		return $this->assignments[ $context ][ $var ] ?? false;
	}

	/**
	 * Check if the token at $stackPtr is part of a ternary condition.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $stackPtr    The position of the current token in the stack.
	 * @param bool $allow_empty Whether to allow empty ternary conditions.
	 * @return int|false The position of the ternary operator or false.
	 */
	protected function is_ternary_condition( $stackPtr, $allow_empty = false ) {

		$end_of_expression = $this->find_end_of_expression( $stackPtr );
		$next              = $this->next_non_empty( $end_of_expression + 1 );

		$next = $this->next_non_empty( $stackPtr );
		while ( $next && $next < $end_of_expression ) {
			/**
			 * `foo( $bar ) ? $baz : ''` -> ternary expression
			 * `foo( $bar ? $baz : '' )` -> not a ternary expression
			 */
			if ( in_array( $this->tokens[ $next ]['code'], Tokens::$functionNameTokens, true ) ) {
				$next = $this->next_non_empty( $next + 1 );
				if ( \T_OPEN_PARENTHESIS === $this->tokens[ $next ]['code'] && isset( $this->tokens[ $next ]['parenthesis_closer'] ) ) {
					$next = $this->tokens[ $next ]['parenthesis_closer'];
				}
			}

			if ( \T_INLINE_THEN === $this->tokens[ $next ]['code'] ) {
				// Found a ternary; check the $allow_empty condition.
				if ( ! $allow_empty ) {
					$lookahead = $this->next_non_empty( $next + 1 );
					if ( \T_INLINE_ELSE === $this->tokens[ $lookahead ]['code'] ) {
						return false;
					}
				}

				return $next;
			}

			$next = $this->next_non_empty( $next + 1 );
		}

		return false;
	}
}
