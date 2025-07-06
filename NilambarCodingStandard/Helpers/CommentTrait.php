<?php
/**
 * CommentTrait
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Helpers;

use PHP_CodeSniffer\Files\File;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

/**
 * CommentTrait.
 *
 * @since 1.0.0
 */
trait CommentTrait {

	/**
	 * Find all tags in PHPDoc.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The PHPCS file object.
	 * @param int  $commentStart The starting position of the comment.
	 * @return array An array of tags.
	 */
	protected function find_comment_tags( File $phpcsFile, int $commentStart ): array {
		$tokens = $phpcsFile->getTokens();

		$commentContent = $this->get_comment_content( $phpcsFile, $commentStart );

		if ( empty( $commentContent ) ) {
			return [];
		}

		$parsedTags = $this->parse_comment_content( $commentContent );

		if ( empty( $parsedTags ) ) {
			return [];
		}

		return $this->convert_parsed_tags_to_phpcs_format( $parsedTags, $phpcsFile, $commentStart );
	}

	/**
	 * Parse PHPDoc content using phpstan/phpdoc-parser.
	 *
	 * @since 1.0.0
	 *
	 * @param string $commentContent The PHPDoc comment content.
	 * @return array Array of parsed tags or empty array on failure.
	 */
	private function parse_comment_content( string $commentContent ): array {
		try {
			$lexer           = new Lexer();
			$typeParser      = new TypeParser();
			$constExprParser = new ConstExprParser();
			$parser          = new PhpDocParser( $typeParser, $constExprParser );

			$tokens        = $lexer->tokenize( $commentContent );
			$tokenIterator = new TokenIterator( $tokens );
			$phpDocNode    = $parser->parse( $tokenIterator );

			$tags = [];

			foreach ( $phpDocNode->children as $child ) {
				if ( $child instanceof PhpDocTagNode ) {
					$tags[] = [
						'name'  => $child->name,
						'value' => $child->value,
					];
				}
			}

			return $tags;
		} catch ( \Exception $e ) {
			return [];
		}
	}

	/**
	 * Convert parsed tags to PHPCS format.
	 *
	 * @since 1.0.0
	 *
	 * @param array $parsedTags Array of parsed tags.
	 * @param File  $phpcsFile The PHPCS file object.
	 * @param int   $commentStart The starting position of the comment.
	 * @return array Array of tags in PHPCS format.
	 */
	private function convert_parsed_tags_to_phpcs_format( array $parsedTags, File $phpcsFile, int $commentStart ): array {
		$tokens = $phpcsFile->getTokens();

		$commentTags = ! empty( $tokens[ $commentStart ]['comment_tags'] ) ? $tokens[ $commentStart ]['comment_tags'] : [];

		$tags = [];

		foreach ( $commentTags as $commentTag ) {
			if ( \T_DOC_COMMENT_TAG === $tokens[ $commentTag ]['code'] ) {
				$tag        = $tokens[ $commentTag ];
				$tag['tag'] = $commentTag;
				$tags[]     = $tag;
			}
		}

		return $tags;
	}

	/**
	 * Get the comment content as a string.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The PHPCS file object.
	 * @param int  $commentStart The starting position of the comment.
	 * @return string The comment content.
	 */
	private function get_comment_content( File $phpcsFile, int $commentStart ): string {
		$tokens = $phpcsFile->getTokens();

		$commentEnd = $phpcsFile->findNext( \T_DOC_COMMENT_CLOSE_TAG, ( $commentStart + 1 ) );

		if ( false === $commentEnd ) {
			return '';
		}

		$content = '';

		for ( $i = $commentStart; $i <= $commentEnd; $i++ ) {
			$content .= $tokens[ $i ]['content'];
		}

		return $content;
	}

	/**
	 * Checks if token has an associated PHPDoc block.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  The index of the token in the stack.
	 * @return bool True if a PHPDoc block is present immediately before the token; false otherwise.
	 */
	protected function has_doc_block( File $phpcsFile, int $stackPtr ): bool {
		$tokens = $phpcsFile->getTokens();

		// Move backwards to find the previous significant token that's not a comment or whitespace.
		$prevTokenPos = $phpcsFile->findPrevious(
			[
				\T_ABSTRACT,
				\T_COMMENT,
				\T_PRIVATE,
				\T_PROTECTED,
				\T_PUBLIC,
				\T_STATIC,
				\T_WHITESPACE,
			],
			$stackPtr - 1,
			null,
			true
		);

		if ( false !== $prevTokenPos && \T_DOC_COMMENT_CLOSE_TAG === $tokens[ $prevTokenPos ]['code'] ) {
			$commentStart = $phpcsFile->findPrevious( \T_DOC_COMMENT_OPEN_TAG, $prevTokenPos );

			if ( false !== $commentStart ) {
				for ( $i = $commentStart; $i <= $prevTokenPos; $i++ ) {
					if ( ! in_array(
						$tokens[ $i ]['code'],
						[
							\T_DOC_COMMENT_OPEN_TAG,
							\T_DOC_COMMENT_WHITESPACE,
							\T_DOC_COMMENT_STAR,
							\T_DOC_COMMENT_TAG,
							\T_DOC_COMMENT_STRING,
							\T_DOC_COMMENT_CLOSE_TAG,
						],
						true
					) ) {
						return false;
					}
				}

				return true;
			}
		}

		return false;
	}
}
