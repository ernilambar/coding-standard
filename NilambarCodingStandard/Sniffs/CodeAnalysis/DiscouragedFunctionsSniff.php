<?php
/**
 * DiscouragedFunctionsSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\CodeAnalysis;

use WordPressCS\WordPress\AbstractFunctionRestrictionsSniff;

/**
 * Detect discouraged functions.
 *
 * @since 1.0.0
 */
final class DiscouragedFunctionsSniff extends AbstractFunctionRestrictionsSniff {

	/**
	 * Groups of functions to discourage.
	 *
	 * Example: groups => array(
	 *  'lambda' => array(
	 *      'type'      => 'error' | 'warning',
	 *      'message'   => 'Use anonymous functions instead please!',
	 *      'functions' => array( 'file_get_contents', 'create_function' ),
	 *  )
	 * )
	 *
	 * @return array
	 */
	public function getGroups() {
		return [
			'load_plugin_textdomain' => [
				'type'      => 'warning',
				'message'   => 'Using %s() for loading the plugin translations is not needed for WordPress.org directory since WordPress 4.6.',
				'functions' => [
					'load_plugin_textdomain',
				],
			],
		];
	}
}
