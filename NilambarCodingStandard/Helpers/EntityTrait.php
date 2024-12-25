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
	 * Get entity name.
	 *
	 * @since 1.0.0
	 *
	 * @param File  $phpcsFile The PHP_CodeSniffer file where the token was found.
	 * @param int   $stackPtr  Current position.
	 * @param array $tokens    Token stack for this file.
	 * @return string Entity name.
	 */
	protected function get_entity_name( File $phpcsFile, int $stackPtr, array $tokens ): string {
		$suffix = $this->get_suffix( $tokens[ $stackPtr ]['code'] );

		if ( T_CONST === $tokens[ $stackPtr ]['code'] ) {
			return $tokens[ $stackPtr + 2 ]['content'] . $suffix;
		}

		return $phpcsFile->getDeclarationName( $stackPtr ) . $suffix;
	}

	/**
	 * Get element suffix.
	 *
	 * @since 1.0.0
	 *
	 * @param int $code Code for current element.
	 * @return string Suffix text.
	 */
	private function get_suffix( int $code ): string {
		$suffix = '';

		switch ( $code ) {
			case T_FUNCTION:
				$suffix = '() function';
				break;
			case T_CLASS:
				$suffix = ' class';
				break;
			case T_INTERFACE:
				$suffix = ' interface';
				break;
			case T_TRAIT:
				$suffix = ' trait';
				break;
			case T_CONST:
				$suffix = ' constant';
				break;
		}

		return $suffix;
	}
}
