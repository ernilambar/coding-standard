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
			// Was the target parameter passed?
			$found_param = PassedParameters::getParameterFromStack( $parameters, $param_position, $param_item['name'] );

			if ( false === $found_param ) {
				continue;
			}

			$type = $this->determineParameterType( $found_param['start'], $found_param['end'] );

			if ( 'string' === $type ) {
				$target_string = trim( TextStrings::stripQuotes( $found_param['clean'] ) );

				$hasChars = $this->hasNonEnglishChars( $target_string );

				if ( true === $hasChars ) {
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
		}

		return 'mixed';
	}

	/**
	 * Checks if string contains non-ASCII characters.
	 *
	 * @since 1.0.0
	 *
	 * @param string $input_string String to check.
	 * @return bool True if non-ASCII characters are found, false otherwise.
	 */
	private function hasNonEnglishChars( string $input_string ): bool {
		if ( '' === $input_string ) {
			return false;
		}

		return (bool) preg_match( '/[^\x00-\x7F]/', $input_string );
	}
}
