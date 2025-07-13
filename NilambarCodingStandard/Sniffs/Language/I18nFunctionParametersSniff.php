<?php
/**
 * I18nFunctionParametersSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Language;

use PHPCSUtils\Utils\MessageHelper;
use PHPCSUtils\Utils\PassedParameters;
use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * Detect function parameters.
 *
 * @since 1.0.0
 */
final class I18nFunctionParametersSniff extends AbstractFunctionParameterSniff {

	/**
	 * List of functions to examine.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $target_functions = [
		'add_menu_page'           => [
			1 => [
				'name' => 'page_title',
			],
			2 => [
				'name' => 'menu_title',
			],
		],
		'add_meta_box'            => [
			2 => [
				'name' => 'title',
			],
		],
		'add_options_page'        => [
			1 => [
				'name' => 'page_title',
			],
			2 => [
				'name' => 'menu_title',
			],
		],
		'add_settings_field'      => [
			2 => [
				'name' => 'title',
			],
		],
		'add_settings_section'    => [
			2 => [
				'name' => 'title',
			],
		],
		'add_submenu_page'        => [
			2 => [
				'name' => 'page_title',
			],
			3 => [
				'name' => 'menu_title',
			],
		],
		'register_nav_menu'       => [
			2 => [
				'name' => 'description',
			],
		],
		'wp_add_dashboard_widget' => [
			2 => [
				'name' => 'widget_name',
			],
		],
	];

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.0.0
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return int|void Integer stack pointer to skip forward or void to continue normal file processing.
	 */
	public function process_token( $stackPtr ) {
		if ( isset( $this->target_functions[ strtolower( $this->tokens[ $stackPtr ]['content'] ) ] ) ) {
			// Disallow excluding function groups for this sniff.
			$this->exclude = [];

			return parent::process_token( $stackPtr );
		}
	}

	/**
	 * Process the parameters of a matched function.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $stackPtr        The position of the current token in the stack.
	 * @param string $group_name      The name of the group which was matched.
	 * @param string $matched_content The token content (function name) which was matched in lowercase.
	 * @param array  $parameters      Array with information about the parameters.
	 * @return void
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		$all_params = $this->target_functions[ $matched_content ];

		foreach ( $all_params as $param_position => $param_item ) {
			// Check if the target parameter is passed.
			$found_param = PassedParameters::getParameterFromStack( $parameters, $param_position, $param_item['name'] );

			if ( false === $found_param ) {
				continue;
			}

			$type = $this->determineParameterType( $found_param['start'], $found_param['end'] );

			if ( 'string' === $type ) {
				$target_string     = trim( TextStrings::stripQuotes( $found_param['clean'] ) );
				$non_english_chars = $this->analyze_content_language( $target_string );
				if ( ! empty( $non_english_chars ) ) {
					$error_code = MessageHelper::stringToErrorCode( $matched_content . '_' . $param_item['name'], true );

					$this->phpcsFile->addWarning(
						'The "%s" parameter for function %s() has non-English text.',
						$stackPtr,
						$error_code . 'NonEnglishDetected',
						[
							$param_item['name'],
							$matched_content,
						]
					);
				}
			} elseif ( 'function_call' === $type ) {
				// Extract string content from function call.
				$string_content = $this->extractStringFromFunctionCall( $found_param['start'], $found_param['end'] );
				if ( null !== $string_content ) {
					$non_english_chars = $this->analyze_content_language( $string_content );
					if ( ! empty( $non_english_chars ) ) {
						$error_code = MessageHelper::stringToErrorCode( $matched_content . '_' . $param_item['name'], true );

						$this->phpcsFile->addWarning(
							'The "%s" parameter for function %s() has non-English text.',
							$stackPtr,
							$error_code . 'NonEnglishDetected',
							[
								$param_item['name'],
								$matched_content,
							]
						);
					}
				}
			}
		}
	}

	/**
	 * Determines the parameter type based on token analysis.
	 *
	 * @since 1.0.0
	 *
	 * @param int $start Starting token pointer.
	 * @param int $end   Ending token pointer.
	 * @return string Detected type.
	 */
	protected function determineParameterType( int $start, int $end ): string {
		$tokens = $this->phpcsFile->getTokens();

		$firstMeaningfulToken = $this->phpcsFile->findNext( [ T_WHITESPACE, T_COMMENT ], $start, $end + 1, true );

		if ( false === $firstMeaningfulToken ) {
			return 'mixed';
		}

		$token = $tokens[ $firstMeaningfulToken ];

		switch ( $token['code'] ) {
			case T_CONSTANT_ENCAPSED_STRING:
				return 'string';
			case T_LNUMBER:
				return 'int';
			case T_DNUMBER:
				return 'float';
			case T_ARRAY:
			case T_OPEN_SHORT_ARRAY:
				return 'array';
			case T_NULL:
				return 'null';
			case T_TRUE:
			case T_FALSE:
				return 'bool';
			case T_CLOSURE:
				return 'callable';
			case T_FN:
				return 'callable';
			case T_STRING:
				// Check if this is a function call.
				$nextToken = $this->phpcsFile->findNext( [ T_WHITESPACE, T_COMMENT ], $firstMeaningfulToken + 1, $end + 1, true );
				if ( false !== $nextToken && T_OPEN_PARENTHESIS === $tokens[ $nextToken ]['code'] ) {
					return 'function_call';
				}
				return 'mixed';
		}

		return 'mixed';
	}

	/**
	 * Extracts string content from within a function call.
	 *
	 * @since 1.0.0
	 *
	 * @param int $start Starting token pointer.
	 * @param int $end   Ending token pointer.
	 * @return string|null Extracted string content or null if not found.
	 */
	protected function extractStringFromFunctionCall( int $start, int $end ): ?string {
		$tokens = $this->phpcsFile->getTokens();

		// Find the opening parenthesis.
		$openParen = $this->phpcsFile->findNext( T_OPEN_PARENTHESIS, $start, $end + 1 );
		if ( false === $openParen ) {
			return null;
		}

		// Find the closing parenthesis.
		$closeParen = $this->phpcsFile->findNext( T_CLOSE_PARENTHESIS, $openParen + 1, $end + 1 );
		if ( false === $closeParen ) {
			return null;
		}

		// Look for the first string literal within the parentheses.
		$stringToken = $this->phpcsFile->findNext( T_CONSTANT_ENCAPSED_STRING, $openParen + 1, $closeParen );
		if ( false === $stringToken ) {
			return null;
		}

		$string_content = $tokens[ $stringToken ]['content'];
		return trim( TextStrings::stripQuotes( $string_content ) );
	}

	/**
	 * Get all unique non-English (non-ASCII, non-emoji) characters in the string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The string to analyze.
	 * @return array Array of unique non-English, non-emoji characters found.
	 */
	private function analyze_content_language( string $content ): array {
		$found = [];
		if ( preg_match_all( '/\P{ASCII}+/u', $content, $matches ) ) {
			foreach ( $matches[0] as $non_english_sequence ) {
				preg_match_all( '/./u', $non_english_sequence, $chars );
				foreach ( $chars[0] as $char ) {
					if ( ! $this->is_emoji( $char ) ) {
						$found[] = $char;
					}
				}
			}
		}
		return array_unique( $found );
	}

	/**
	 * Check if a string is an emoji (or only contains emojis).
	 *
	 * @since 1.0.0
	 *
	 * @param string $str The string to check.
	 * @return bool True if the string is an emoji or only contains emojis, false otherwise.
	 */
	private function is_emoji( $str ) {
		return (bool) preg_match(
			'/([\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]|[\x{FE00}-\x{FE0F}]|[\x{1F900}-\x{1F9FF}]|[\x{1FA70}-\x{1FAFF}]|[\x{200D}]|[\x{2300}-\x{23FF}])/xu',
			$str
		);
	}
}
