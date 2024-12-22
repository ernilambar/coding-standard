<?php
/**
 * Version
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Traits;

/**
 * Trait Version.
 *
 * @since 1.0.0
 */
trait Version {

	/**
	 * Checks whether tag has version.
	 *
	 * @since 1.0.0
	 *
	 * @param array $tag    Tag information.
	 * @param array $tokens List of tokens.
	 * @return bool True if version is not empty, otherwise false.
	 */
	protected function has_version( array $tag, array $tokens ): bool {
		$version = $tokens[ ( $tag['tag'] + 2 ) ]['content'];

		return ! empty( $version ) && T_DOC_COMMENT_STRING === $tokens[ ( $tag['tag'] + 2 ) ]['code'];
	}

	/**
	 * Checks whether version is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param array $tag    Tag information.
	 * @param array $tokens List of tokens.
	 * @return bool True if version is valid, otherwise false.
	 */
	protected function is_valid_version( array $tag, array $tokens ): bool {
		$version = $tokens[ ( $tag['tag'] + 2 ) ]['content'];

		return (bool) preg_match( '/^[\d.]+\d$/', $version );
	}
}
