<?php
/**
 * OutputEscapingSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Security;

use NilambarCodingStandard\Helpers\AbstractEscapingCheckSniff;
use PHP_CodeSniffer\Util\Tokens;
use PHP_CodeSniffer\Util\Variables;
use PHPCSUtils\Utils\PassedParameters;

/**
 * Context-aware checks for output escaping.
 *
 * @package Nilambar_Coding_Standard
 *
 * @since 1.0.0
 */
final class OutputEscapingSniff extends AbstractEscapingCheckSniff {

	/**
	 * Rule name for error messages.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $rule_name = 'UnescapedOutputParameter';

	/**
	 * Override the parent class escaping functions to only allow HTML-safe escapes
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, true>
	 */
	protected $escapingFunctions = array(
		'esc_html'                   => true,
		'esc_html__'                 => true,
		'esc_html_x'                 => true,
		'esc_html_e'                 => true,
		'esc_attr'                   => true,
		'esc_attr__'                 => true,
		'esc_attr_x'                 => true,
		'esc_attr_e'                 => true,
		'esc_url'                    => true,
		'esc_js'                     => true,
		'esc_textarea'               => true,
		'sanitize_text_field'        => true,
		'intval'                     => true,
		'absint'                     => true,
		'json_encode'                => true,
		'wp_json_encode'             => true,
		'htmlspecialchars'           => true,
		'wp_kses'                    => true,
		'wp_kses_post'               => true,
		'wp_kses_data'               => true,
		'tag_escape'                 => true,
	);

	/**
	 * Functions that are often mistaken for escaping functions.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string>
	 */
	protected $notEscapingFunctions = array(
		'addslashes',
		'addcslashes',
		'filter_input',
		'wp_strip_all_tags',
		'esc_url_raw',
	);

	/**
	 * None of these are HTML safe
	 *
	 * @since 1.0.0
	 *
	 * @var array<string>
	 */
	protected $sanitizingFunctions = array();

	/**
	 * None of these are HTML safe
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
		'array_fill'          => true,
		'sprintf'             => true, // Sometimes used to get around formatting table and column names in queries
		'array_filter'        => true,
		'__'                  => true,
		'_x'                  => true,
		'date'                => true,
		'date_i18n'           => true,
		'get_the_date'        => true, // Could be unsafe if the format parameter is untrusted
		'get_comment_time'    => true,
		'get_comment_date'    => true,
		'comments_number'     => true,
		'get_the_category_list' => true, // separator parameter is unescaped
		'get_header_image_tag' => true, // args are unescaped
		'get_the_tag_list'     => true, // args are unescaped
		'trim'                => true,
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
		'get_avatar'     => true,
		'get_search_query' => true,
		'get_bloginfo'   => true, // depends on params
		'get_the_ID'     => true,
		'count'          => true,
		'strtotime'      => true,
		'uniqid'         => true,
		'md5'            => true,
		'sha1'           => true,
		'rand'           => true,
		'mt_rand'        => true,
		'max'            => true,
		'wp_get_attachment_image' => true,
		'post_class'     => true,
		'wp_trim_words'  => true, // calls wp_strip_all_tags()
		'paginate_links' => true,
		'selected'       => true,
		'checked'        => true,
		'get_the_posts_pagination' => true,
		'get_the_author_posts_link' => true,
		'get_the_password_form' => true,
		'get_the_tag_list' => true,
		'get_the_post_thumbnail' => true,
		'get_custom_logo' => true,
		'plugin_dir_url'  => true, // probably safe?
		'admin_url'       => true, // also probably safe?
		'get_admin_url'   => true, // probably?
		'get_field_description' => true, // WP_Admin_Settings::get_field_description()
		'get_submit_button' => true, // returns html with escaped attributes
		'wp_star_rating'  => true, // some misc functions from template.php that are safe enough
		'get_settings_errors' => true,
		'_draft_or_post_title' => true,
		'_admin_search_query' => true,
		'get_media_states' => true,
		'get_post_states' => true,
		'wp_readonly'     => true, // some misc functions from general-template.php that are safe enough
		'get_post_timestamp' => true, // some of these return html and are thus intended to be output without escaping
		'wp_get_code_editor_settings' => true,
		'get_the_post_type_description' => true,
		'has_custom_logo' => true,
		'get_custom_logo' => true,
		'get_language_attributes' => true,
		'get_the_archive_title' => true,
		'checked'         => true,
		'selected'        => true,
		'disabled'        => true,
		'get_the_time'    => true,
		'get_post_time'   => true,
		'get_the_modified_time' => true,
		'get_the_modified_date' => true,
		'get_the_date'    => true,
		'get_archives_link' => true,
		'get_calendar'    => true,
		'wp_nav_menu'     => true, // nav-menu-template.php
		'get_post_format' => true,
		'wp_get_attachment_image' => true,
		'mysql2date'      => true,
		'wp_create_nonce' => true,
	);

	/**
	 * Explicit user input will always generate an error when displayed unescaped.
	 * All other variables will generate warnings.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string>
	 */
	protected $error_always_parameters = [
		'$_GET',
		'$_POST',
		'$_REQUEST',
		'$_COOKIE',
	];

	/**
	 * $wpdb methods with escaping built-in
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, true>
	 */
	protected $safe_methods = array(
	);

	/**
	 * $wpdb methods that require the first parameter to be escaped.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, true>
	 */
	protected $unsafe_methods = array(
	);

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int>
	 */
	public function register() {
		return array(
			\T_ECHO,
			\T_PRINT,
			\T_EXIT,
			\T_STRING,
			\T_OPEN_TAG_WITH_ECHO,
			\T_VARIABLE,
		);
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
		// This sniff doesn't check wpdb methods, so always return false.
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
