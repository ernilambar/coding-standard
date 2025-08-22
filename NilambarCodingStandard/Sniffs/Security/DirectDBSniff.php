<?php
/**
 * DirectDBSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Security;

use NilambarCodingStandard\Helpers\AbstractEscapingCheckSniff;
use PHP_CodeSniffer\Util\Tokens;
use PHP_CodeSniffer\Util\Variables;
use PHPCSUtils\Utils\PassedParameters;

/**
 * Flag Database direct queries.
 *
 * @link    https://vip.wordpress.com/documentation/vip-go/code-review-blockers-warnings-notices/#direct-database-queries
 *
 * @package Nilambar_Coding_Standard
 *
 * @since 1.0.0
 */
final class DirectDBSniff extends AbstractEscapingCheckSniff {

	/**
	 * Rule name for error messages.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $rule_name = 'UnescapedDBParameter';

	/**
	 * Override the parent class escaping functions to only allow SQL-safe escapes
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, true>
	 */
	protected $escapingFunctions = array(
		'absint'                     => true,
		'floatval'                   => true,
		'intval'                     => true,
		'json_encode'                => true,
		'like_escape'                => true,
		'wp_json_encode'             => true,
		'isset'                      => true,
		'esc_sql'                    => true,
		'wp_parse_id_list'           => true,
		'bp_esc_like'                => true,
		'sanitize_sql_orderby'       => true,
	);

	/**
	 * Functions that are often mistaken for SQL escaping functions, but are not SQL safe.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string>
	 */
	protected $notEscapingFunctions = array(
		'addslashes',
		'addcslashes',
		'sanitize_text_field',
		'sanitize_title',
		'sanitize_key',
		'filter_input',
		'esc_attr',
	);

	/**
	 * None of these are SQL safe
	 *
	 * @since 1.0.0
	 *
	 * @var array<string>
	 */
	protected $sanitizingFunctions = array();

	/**
	 * None of these are SQL safe
	 *
	 * @since 1.0.0
	 *
	 * @var array<string>
	 */
	protected $unslashingFunctions = array();

	/**
	 * Functions that are neither safe nor unsafe. Their output is as safe as the data passed as parameters.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, true>
	 */
	protected $neutralFunctions = array(
		'implode'             => true,
		'join'                => true,
		'array_keys'          => true,
		'array_values'        => true,
		'sanitize_text_field' => true, // Note that this does not escape for SQL.
		'array_fill'          => true,
		'sprintf'             => true, // Sometimes used to get around formatting table and column names in queries
		'array_filter'        => true,
	);

	/**
	 * Functions with output that can be assumed to be safe. Escaping is always preferred, but alerting on these is unnecessary noise.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, true>
	 */
	protected $implicitSafeFunctions = array(
		'gmdate'         => true,
		'current_time'   => true,
		'mktime'         => true,
		'get_post_types' => true,
		'get_charset_collate' => true,
		'get_blog_prefix' => true,
		'get_post_stati' => true,
		'count'          => true,
		'strtotime'      => true,
		'uniqid'         => true,
		'md5'            => true,
		'sha1'           => true,
		'rand'           => true,
		'mt_rand'        => true,
		'max'            => true,
		'table_name'     => true,
	);

	/**
	 * $wpdb methods with escaping built-in
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, true>
	 */
	protected $safe_methods = array(
		'delete'  => true,
		'replace' => true,
		'update'  => true,
		'insert'  => true,
		// 'prepare' => true, // Commented out as it's handled specially
	);

	/**
	 * $wpdb methods that require the first parameter to be escaped.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, true>
	 */
	protected $unsafe_methods = array(
		'query'       => true,
		'get_var'     => true,
		'get_col'     => true,
		'get_row'     => true,
		'get_results' => true,
	);

	/**
	 * A list of variable names that, if used unescaped in a SQL query, will only produce a warning rather than an error.
	 * For example, 'SELECT * FROM {$table}' is commonly used and typically a red herring.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string>
	 */
	protected $warn_only_parameters = array(
		'$table',
		'$table_name',
		'$table_prefix',
		'$column_name',
		'$this', // typically something like $this->tablename
		'$order_by',
		'$orderby',
		'$where',
		'$wheres',
		'$join',
		'$joins',
		'$bp_prefix',
		'$where_sql',
		'$join_sql',
		'$from_sql',
		'$select_sql',
		'$meta_query_sql',
	);

	/**
	 * A list of SQL query prefixes that with only produce a warning instead of an error if they contain unsafe paramaters.
	 * For example, 'CREATE TABLE $tablename' is often used because there are no clear ways to escape a table name.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string>
	 */
	protected $warn_only_queries = array(
		'CREATE TABLE',
		'SHOW TABLE',
		'DROP TABLE',
		'TRUNCATE TABLE',
	);

	/**
	 * Used for providing extra context from some methods.
	 *
	 * @since 1.0.0
	 *
	 * @var int|null
	 */
	protected $methodPtr = null;

	/**
	 * Used for providing extra context from some methods.
	 *
	 * @since 1.0.0
	 *
	 * @var int|null
	 */
	protected $unsafe_ptr = null;

	/**
	 * Used for providing extra context from some methods.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	protected $unsafe_expression = null;

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int>
	 */
	public function register() {
		return array(
			\T_VARIABLE,
			\T_STRING,
		);
	}

	/**
	 * Is a SQL query of a type that should only produce a warning when it contains unescaped parameters?
	 *
	 * For example, CREATE TABLE queries usually include unescaped table and column names.
	 *
	 * @since 1.0.0
	 *
	 * @param string $sql The SQL query.
	 * @return bool Whether the query should only produce a warning.
	 */
	public function is_warning_expression( $sql ) {
		foreach ( $this->warn_only_queries as $warn_query ) {
			if ( 0 === strpos( ltrim( $sql, '\'"' ), $warn_query ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if this is a wpdb method call.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $stackPtr The position of the current token in the stack.
	 * @param array $methods  Array of methods to check for.
	 * @return bool Whether this is a wpdb method call.
	 */
	protected function is_wpdb_method_call( $stackPtr, $methods ) {
		// Check if this is a method call on $wpdb
		if ( \T_STRING === $this->tokens[ $stackPtr ][ 'code' ] ) {
			$method_name = $this->tokens[ $stackPtr ][ 'content' ];
			if ( isset( $methods[ $method_name ] ) ) {
				// Look for $wpdb->method_name pattern
				$prev_token = $this->previous_non_empty( $stackPtr - 1 );
				if ( \T_OBJECT_OPERATOR === $this->tokens[ $prev_token ][ 'code' ] ) {
					$wpdb_token = $this->previous_non_empty( $prev_token - 1 );
					if ( \T_VARIABLE === $this->tokens[ $wpdb_token ][ 'code' ] && '$wpdb' === $this->tokens[ $wpdb_token ][ 'content' ] ) {
						$this->methodPtr = $stackPtr;
						return true;
					}
				}
			}
		}

		return false;
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
		static $line_no = null;
		if ( $this->tokens[ $stackPtr ][ 'line' ] !== $line_no ) {
			$line_no = $this->tokens[ $stackPtr ][ 'line' ];
		}

		if ( $this->is_assignment( $stackPtr ) ) {

			// Work out what we're assigning to the variable at $stackPtr
			$nextToken = $this->phpcsFile->findNext( Tokens::$assignmentTokens, $stackPtr +1 , null, false, null, true );

			// If the expression being assigned is safe (ie escaped) then mark the variable as sanitized.
			if ( $this->expression_is_safe( $nextToken + 1 ) ) {
				// Don't mark as safe if it's a concat, since that doesn't sanitize the initial part.
				if ( $this->tokens[ $nextToken ][ 'code' ] !== \T_CONCAT_EQUAL ) {
					$this->mark_sanitized_var( $stackPtr, $nextToken + 1 );
				}
			} else {
				$this->mark_unsanitized_var( $stackPtr, $nextToken + 1 );
			}

			return; // ??
		}

		// Handle foreach ( $foo as $bar ), which is similar to assignment
		$nextToken = $this->next_non_empty( $stackPtr + 1 );
		if ( \T_AS === $this->tokens[ $nextToken ][ 'code' ] ) {
			$as_var = $this->next_non_empty( $nextToken + 1 );
			$lookahead = $this->next_non_empty( $as_var + 1 );
			if ( \T_DOUBLE_ARROW === $this->tokens[ $lookahead ][ 'code' ] ) {
				// It's foreach ( $foo as $i => $as_var )
				$as_var = $this->next_non_empty( $lookahead + 1 );
			}
			if ( \T_VARIABLE === $this->tokens[ $as_var ][ 'code' ] ) {
				// $as_var is effectively being assigned to. So if the LHS expression is safe, $as_var is also safe.
				if ( $this->expression_is_safe( $stackPtr, $nextToken ) ) {
					$this->mark_sanitized_var( $as_var );
				} else {
					$this->mark_unsanitized_var( $as_var );
				}
			}
		}

		// Special case for array_walk. Handled here rather than in expression_is_safe() because it's a statement not an expression.
		if ( in_array( $this->tokens[ $stackPtr ][ 'code' ], Tokens::$functionNameTokens )
			&& 'array_walk' === $this->tokens[ $stackPtr ][ 'content' ] ) {
			$function_params = PassedParameters::getParameters( $this->phpcsFile, $stackPtr );
			$mapped_function = trim( $function_params[2][ 'clean' ], '"\'' );
			// If it's an escaping function, then mark the referenced variable in the first parameter as sanitized.
			if ( isset( $this->escapingFunctions[ $mapped_function ] ) ) {
				$escaped_var = $this->next_non_empty( $function_params[ 1 ][ 'start' ] );
				$this->mark_sanitized_var( $escaped_var );
			}
		}

		// If we're in a call to an unsafe db method like $wpdb->query then check all the parameters for safety
		if ( $checkPtr = $this->needs_escaping( $stackPtr ) ) {
			// Function call?
			if ( \T_STRING === $this->tokens[ $checkPtr ][ 'code' ] ) {
				// Only the first parameter needs escaping (FIXME?)
				$parameters = PassedParameters::getParameters( $this->phpcsFile, $checkPtr );
				$method = $this->tokens[ $checkPtr ][ 'content' ];
				$methodParam = reset( $parameters );
				// If the expression wasn't escaped safely, then alert.
				if ( $unsafe_ptr = $this->check_expression( $methodParam[ 'start' ], $methodParam[ 'end' ] + 1 ) ) {
					$extra_context = $this->unwind_unsafe_assignments( $unsafe_ptr );
					$unsafe_expression = $this->get_unsafe_expression_as_string( $unsafe_ptr );

					if ( $this->is_warning_parameter( $unsafe_expression )
						|| $this->is_suppressed_line( $checkPtr, [ 'WordPress.DB.PreparedSQL.NotPrepared', 'WordPress.DB.PreparedSQL.InterpolatedNotPrepared', 'WordPress.DB.DirectDatabaseQuery.DirectQuery', 'DB call', 'unprepared SQL', 'PreparedSQLPlaceholders replacement count'] )
						|| $this->is_warning_expression( $methodParam[ 'clean' ] )
						) {
						$this->phpcsFile->addWarning( 'Unescaped parameter %s used in $wpdb->%s(%s)%s',
							$checkPtr,
							$this->rule_name,
							[ $unsafe_expression, $method, $methodParam[ 'clean' ], rtrim( "\n" . join( "\n", $extra_context ) ) ],
							$this->expression_severity,
							false
						);
					} else {
						$this->phpcsFile->addError( 'Unescaped parameter %s used in $wpdb->%s(%s)%s',
							$checkPtr,
							$this->rule_name,
							[ $unsafe_expression, $method, $methodParam[ 'clean' ], rtrim( "\n" . join( "\n", $extra_context ) ) ],
							$this->expression_severity,
							false
						);
					}
					return; // Only need to error on the first occurrence
				}
			} else {
				// echo etc; check everything to end of statement
				if ( $unsafe_ptr = $this->check_expression( $checkPtr + 1 ) ) {
					$extra_context = $this->unwind_unsafe_assignments( $unsafe_ptr );
					$unsafe_expression = $this->get_unsafe_expression_as_string( $unsafe_ptr );

					if ( $this->is_warning_parameter( $unsafe_expression ) || $this->is_suppressed_line( $checkPtr, [ 'WordPress.DB.PreparedSQL.NotPrepared', 'WordPress.DB.PreparedSQL.InterpolatedNotPrepared', 'WordPress.DB.DirectDatabaseQuery.DirectQuery', 'DB call', 'unprepared SQL', 'PreparedSQLPlaceholders replacement count'] ) ) {
						$this->phpcsFile->addWarning( 'Unescaped parameter %s used in %s%s',
							$checkPtr,
							$this->rule_name,
							[ $unsafe_expression, $this->tokens[ $checkPtr ][ 'content' ], rtrim( "\n" . join( "\n", $extra_context ) ) ],
							$this->expression_severity,
							false
						);
					} else {
						$this->phpcsFile->addError( 'Unescaped parameter %s used in %s%s',
							$checkPtr,
							$this->rule_name,
							[ $unsafe_expression, $this->tokens[ $checkPtr ][ 'content' ], rtrim( "\n" . join( "\n", $extra_context ) ) ],
							$this->expression_severity,
							false
						);
					}
					return; // Only need to error on the first occurrence
				}
			}
		}
	}
}
