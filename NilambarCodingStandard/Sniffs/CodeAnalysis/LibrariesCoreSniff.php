<?php
/**
 * LibrariesCoreSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\TextStrings;
use PHPCSUtils\Utils\PassedParameters;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * Detect core scripts.
 *
 * @since 1.0.0
 */
final class LibrariesCoreSniff extends AbstractFunctionParameterSniff {

	/**
	 * The group of functions we will look for.
	 *
	 * @var array<string>
	 */
	protected $target_functions = [
		'wp_enqueue_script'  => true,
		'wp_register_script' => true,
	];

	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		$core_services = [
			'jquery\.min\.js',
			'hoverintent\.js',
		];

		$src_param = PassedParameters::getParameterFromStack( $parameters, 2, 'src' );
		if ( empty( $src_param ) ) {
			return;
		}

		$pattern = '/(' . implode( '|', $core_services ) . ')/i';

		$matches = [];

		if ( preg_match_all( $pattern, $src_param['raw'], $matches, PREG_OFFSET_CAPTURE ) > 0 ) {
			$error = 'Core library found.';
			$this->phpcsFile->addError( $error, $src_param['start'], 'Found' );
		}
	}
}
