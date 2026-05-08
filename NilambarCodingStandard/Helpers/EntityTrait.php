<?php
/**
 * EntityTrait
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Helpers;

use PHP_CodeSniffer\Files\File;

/**
 * EntityTrait.
 *
 * @since 1.0.0
 */
trait EntityTrait {

	/**
	 * Get a human-readable label for the entity at the given token position.
	 *
	 * Returns labels in the form "function foo()", "class Foo", "property $bar", etc.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The PHP_CodeSniffer file where the token was found.
	 * @param int  $stackPtr  Current position.
	 * @return string Entity label.
	 */
	protected function get_entity_name( File $phpcsFile, int $stackPtr ): string {
		$tokens = $phpcsFile->getTokens();
		$code   = $tokens[ $stackPtr ]['code'];

		switch ( $code ) {
			case \T_FUNCTION:
				$name = $phpcsFile->getDeclarationName( $stackPtr );
				return 'function ' . ( null !== $name ? $name : '' ) . '()';

			case \T_CLASS:
				return 'class ' . (string) $phpcsFile->getDeclarationName( $stackPtr );

			case \T_INTERFACE:
				return 'interface ' . (string) $phpcsFile->getDeclarationName( $stackPtr );

			case \T_TRAIT:
				return 'trait ' . (string) $phpcsFile->getDeclarationName( $stackPtr );

			case \T_CONST:
				$name_ptr = $phpcsFile->findNext( \T_STRING, $stackPtr + 1 );
				$name     = ( false !== $name_ptr ) ? $tokens[ $name_ptr ]['content'] : '';
				return 'constant ' . $name;

			case \T_VARIABLE:
				return 'property ' . $tokens[ $stackPtr ]['content'];
		}

		// Enums and enum cases (PHP 8.1+). Token codes resolved via constant lookup so PHP 7.4 is happy.
		if ( defined( 'T_ENUM' ) && \T_ENUM === $code ) {
			return 'enum ' . (string) $phpcsFile->getDeclarationName( $stackPtr );
		}

		if ( defined( 'T_ENUM_CASE' ) && \T_ENUM_CASE === $code ) {
			$name_ptr = $phpcsFile->findNext( \T_STRING, $stackPtr + 1 );
			$name     = ( false !== $name_ptr ) ? $tokens[ $name_ptr ]['content'] : '';
			return 'enum case ' . $name;
		}

		return '';
	}
}
