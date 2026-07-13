<?php
/**
 * SinceTagSniff
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Commenting;

use Exception;
use NilambarCodingStandard\Helpers\CommentTrait;
use NilambarCodingStandard\Helpers\EntityTrait;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Detect since tag in PHPDoc.
 *
 * @since 1.0.0
 */
final class SinceTagSniff implements Sniff {

	use CommentTrait;
	use EntityTrait;
	use SinceTagFixerTrait;

	/**
	 * The file being scanned.
	 *
	 * @var File
	 */
	protected File $phpcsFile;

	/**
	 * Tags that share the @since header group, documented as a single block
	 * (no internal blank lines), separated from @param/@return by one blank line.
	 *
	 * @var string[]
	 */
	private const HEADER_GROUP_TAGS = array(
		'@since',
		'@deprecated',
		'@see',
		'@link',
		'@global',
	);

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		$tokens = array(
			\T_FUNCTION,
			\T_CLASS,
			\T_INTERFACE,
			\T_TRAIT,
			\T_CONST,
			\T_VARIABLE,
		);

		// PHP 8.1+ enums; guarded so the PHP 7.4 floor isn't broken at parse time.
		if ( defined( 'T_ENUM' ) ) {
			$tokens[] = \T_ENUM;
		}
		if ( defined( 'T_ENUM_CASE' ) ) {
			$tokens[] = \T_ENUM_CASE;
		}

		return $tokens;
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @since 1.0.0
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  The position of the current token in the stack.
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$this->phpcsFile = $phpcsFile;
		$tokens          = $this->phpcsFile->getTokens();
		$code   = $tokens[ $stackPtr ]['code'];

		// Skip closures and anonymous functions: getDeclarationName returns null when no name is present.
		if ( \T_FUNCTION === $code ) {
			$name = $this->phpcsFile->getDeclarationName( $stackPtr );
			if ( null === $name || '' === $name ) {
				return;
			}
		}

		// T_VARIABLE matches every variable in the file; only proceed for class properties.
		if ( \T_VARIABLE === $code && ! $this->is_class_property( $stackPtr ) ) {
			return;
		}

		$comment_start = $this->find_doc_block_opener( $this->phpcsFile, $stackPtr );
		if ( null === $comment_start ) {
			return;
		}

		// @inheritDoc defers documentation to the parent — skip every @since check.
		if ( $this->has_inherit_doc( $comment_start ) ) {
			return;
		}

		$entity   = $this->get_entity_name( $this->phpcsFile, $stackPtr );
		$all_tags = $this->collect_tags( $comment_start );

		$since_tags = array_filter(
			$all_tags,
			static function ( $tag ) {
				return '@since' === $tag['content'];
			}
		);

		if ( empty( $since_tags ) ) {
			$this->phpcsFile->addError(
				'Missing @since tag for %s.',
				$stackPtr,
				'Missing',
				array( $entity )
			);
			return;
		}

		$this->check_tag_position( $all_tags, $since_tags, $comment_start, $entity );
		$valid_versions = $this->check_tag_versions( $since_tags, $tokens, $entity );
		$this->check_duplicate_versions( $since_tags, $valid_versions, $entity );
		$grouped = $this->check_grouping( $all_tags, $since_tags, $comment_start, $entity );
		if ( $grouped ) {
			$this->check_blank_line_after_group( $all_tags, $since_tags, $comment_start, $entity );
		}
	}

	/**
	 * Collect every @-tag in the docblock as an array of token records keyed by stack pointer.
	 *
	 * @param int $comment_start Pointer to the T_DOC_COMMENT_OPEN_TAG.
	 * @return array<int, array<string, mixed>> Tags keyed by their stack pointer, ordered as they appear.
	 */
	private function collect_tags( int $comment_start ): array {
		$tokens       = $this->phpcsFile->getTokens();
		$comment_tags = ! empty( $tokens[ $comment_start ]['comment_tags'] )
			? $tokens[ $comment_start ]['comment_tags']
			: array();

		$result = array();
		foreach ( $comment_tags as $tag_ptr ) {
			if ( \T_DOC_COMMENT_TAG !== $tokens[ $tag_ptr ]['code'] ) {
				continue;
			}
			$tag                = $tokens[ $tag_ptr ];
			$tag['tag']         = $tag_ptr;
			$result[ $tag_ptr ] = $tag;
		}

		return $result;
	}

	/**
	 * Check whether @since is the first tag, and whether an empty docblock line precedes it.
	 *
	 * @param array<int, array<string, mixed>> $all_tags      All tags keyed by stack pointer.
	 * @param array<int, array<string, mixed>> $since_tags    @since tags keyed by stack pointer.
	 * @param int                              $comment_start Pointer to T_DOC_COMMENT_OPEN_TAG.
	 * @param string                           $entity        Entity label.
	 */
	private function check_tag_position( array $all_tags, array $since_tags, int $comment_start, string $entity ): void {
		$tokens    = $this->phpcsFile->getTokens();
		$first_tag = reset( $all_tags );

		if ( '@since' !== $first_tag['content'] ) {
			$first_since_ptr = reset( $since_tags )['tag'];
			$fix             = $this->phpcsFile->addFixableWarning(
				'The @since tag must come before other tags in %s.',
				$first_since_ptr,
				'NotFirst',
				array( $entity )
			);
			if ( true === $fix ) {
				$this->reorder_tags_with_since_first( $comment_start );
			}
			return;
		}

		// @since is the first tag. Walk back to see whether description text sits on the line right before it.
		$first_since_ptr  = $first_tag['tag'];
		$first_since_line = $tokens[ $first_since_ptr ]['line'];

		$prev = $this->phpcsFile->findPrevious(
			array( \T_DOC_COMMENT_WHITESPACE, \T_DOC_COMMENT_STAR ),
			$first_since_ptr - 1,
			$comment_start,
			true
		);

		if ( false === $prev
			|| \T_DOC_COMMENT_STRING !== $tokens[ $prev ]['code']
			|| ( $first_since_line - 1 ) !== $tokens[ $prev ]['line']
		) {
			return;
		}

		$fix = $this->phpcsFile->addFixableWarning(
			'Missing empty line before @since tag for %s.',
			$first_since_ptr,
			'MissingEmptyLine',
			array( $entity )
		);
		if ( true === $fix ) {
			$this->fix_missing_empty_line( $first_since_ptr );
		}
	}

	/**
	 * Validate the version on every @since tag and report missing/invalid versions.
	 *
	 * @param array<int, array<string, mixed>> $since_tags @since tags keyed by stack pointer.
	 * @param array<int, array<string, mixed>> $tokens     PHPCS token stack.
	 * @param string                           $entity     Entity label.
	 * @return array<int, string> Map of stack pointer to validated version string for tags that passed validation.
	 */
	private function check_tag_versions( array $since_tags, array $tokens, string $entity ): array {
		$valid = array();

		foreach ( $since_tags as $key => $since ) {
			$check = $this->check_version( $since, $tokens );

			if ( 'missing' === $check['status'] ) {
				$this->phpcsFile->addError(
					'Missing @since version for %s.',
					$since['tag'],
					'MissingVersion',
					array( $entity )
				);
				continue;
			}

			if ( 'invalid' === $check['status'] ) {
				$this->phpcsFile->addError(
					'Invalid @since version for %s.',
					$since['tag'],
					'InvalidVersion',
					array( $entity )
				);
				continue;
			}

			$valid[ $key ] = $check['version'];
		}

		return $valid;
	}

	/**
	 * Warn when the same @since version appears more than once on a single entity.
	 *
	 * @param array<int, array<string, mixed>> $since_tags     @since tags keyed by stack pointer.
	 * @param array<int, string>               $valid_versions Validated versions keyed by stack pointer.
	 * @param string                           $entity         Entity label.
	 */
	private function check_duplicate_versions( array $since_tags, array $valid_versions, string $entity ): void {
		$seen = array();

		foreach ( $valid_versions as $key => $version ) {
			if ( isset( $seen[ $version ] ) ) {
				$this->phpcsFile->addWarning(
					'Duplicate @since version "%s" for %s.',
					$since_tags[ $key ]['tag'],
					'DuplicateVersion',
					array( $version, $entity )
				);
				continue;
			}
			$seen[ $version ] = true;
		}
	}

	/**
	 * Ensure all @since tags are consecutive within the tag block.
	 *
	 * @param array<int, array<string, mixed>> $all_tags      All tags keyed by stack pointer.
	 * @param array<int, array<string, mixed>> $since_tags    @since tags keyed by stack pointer.
	 * @param int                              $comment_start Pointer to T_DOC_COMMENT_OPEN_TAG.
	 * @param string                           $entity        Entity label.
	 * @return bool True when @since tags are contiguous (or only one exists); false when an Ungrouped error fired.
	 */
	private function check_grouping( array $all_tags, array $since_tags, int $comment_start, string $entity ): bool {
		if ( count( $since_tags ) <= 1 ) {
			return true;
		}

		if ( $this->are_indices_contiguous( array_keys( $since_tags ), $all_tags ) ) {
			return true;
		}

		// Anchor on the first non-@since tag that sits between two @since tags — the actually-misplaced line.
		$anchor_ptr = $this->find_ungrouped_anchor( $all_tags, $since_tags );

		$fix = $this->phpcsFile->addFixableError(
			'Multiple @since tags must be consecutive in %s.',
			$anchor_ptr,
			'Ungrouped',
			array( $entity )
		);
		if ( true === $fix ) {
			$this->reorder_tags_with_since_first( $comment_start );
		}
		return false;
	}

	/**
	 * Ensure a blank docblock line separates the @since header group from the parameter/return group.
	 *
	 * The "header group" is the run of HEADER_GROUP_TAGS starting at the first @since
	 * (@since/@deprecated/@see/@link/@global form one block).
	 *
	 * @param array<int, array<string, mixed>> $all_tags      All tags keyed by stack pointer, in order.
	 * @param array<int, array<string, mixed>> $since_tags    @since tags keyed by stack pointer.
	 * @param int                              $comment_start Pointer to T_DOC_COMMENT_OPEN_TAG.
	 * @param string                           $entity        Entity label.
	 */
	private function check_blank_line_after_group( array $all_tags, array $since_tags, int $comment_start, string $entity ): void {
		$tokens          = $this->phpcsFile->getTokens();
		$first_since_ptr = reset( $since_tags )['tag'];

		// Find the first tag after the first @since whose name is not in the header group.
		$next_tag_ptr = null;
		$past_first   = false;
		foreach ( $all_tags as $tag_ptr => $tag ) {
			if ( $tag_ptr === $first_since_ptr ) {
				$past_first = true;
				continue;
			}
			if ( ! $past_first ) {
				continue;
			}
			if ( ! in_array( $tag['content'], self::HEADER_GROUP_TAGS, true ) ) {
				$next_tag_ptr = $tag_ptr;
				break;
			}
		}

		if ( null === $next_tag_ptr ) {
			return;
		}

		$next_tag_line = $tokens[ $next_tag_ptr ]['line'];

		// Find the previous content token (string or tag); stars and whitespace don't count as content.
		$prev = $this->phpcsFile->findPrevious(
			array( \T_DOC_COMMENT_WHITESPACE, \T_DOC_COMMENT_STAR ),
			$next_tag_ptr - 1,
			$comment_start,
			true
		);

		if ( false === $prev || ( $next_tag_line - 1 ) !== $tokens[ $prev ]['line'] ) {
			return;
		}

		$fix = $this->phpcsFile->addFixableWarning(
			'Missing empty line after @since group for %s.',
			$next_tag_ptr,
			'MissingEmptyLineAfter',
			array( $entity )
		);
		if ( true === $fix ) {
			$this->fix_missing_empty_line( $next_tag_ptr );
		}
	}

	/**
	 * Determine whether the @since tags appear as a single contiguous run in the overall tag list.
	 *
	 * @param array<int, int>                  $since_keys Keys (stack pointers) of @since tags, in order.
	 * @param array<int, array<string, mixed>> $all_tags   All tags keyed by stack pointer, in order.
	 * @return bool True if every tag between the first and last @since is itself an @since.
	 */
	private function are_indices_contiguous( array $since_keys, array $all_tags ): bool {
		if ( count( $since_keys ) <= 1 ) {
			return true;
		}

		$all_keys     = array_keys( $all_tags );
		$first_pos    = array_search( $since_keys[0], $all_keys, true );
		$last_pos     = array_search( end( $since_keys ), $all_keys, true );
		$expected_len = ( $last_pos - $first_pos ) + 1;

		return count( $since_keys ) === $expected_len;
	}

	/**
	 * Find the first non-@since tag that sits between two @since tags.
	 *
	 * @param array<int, array<string, mixed>> $all_tags   All tags keyed by stack pointer, in order.
	 * @param array<int, array<string, mixed>> $since_tags @since tags keyed by stack pointer.
	 * @return int Stack pointer of the misplaced tag (falls back to the first @since if none found).
	 */
	private function find_ungrouped_anchor( array $all_tags, array $since_tags ): int {
		$since_keys = array_keys( $since_tags );
		$first_key  = $since_keys[0];
		$last_key   = end( $since_keys );

		$in_range = false;
		foreach ( array_keys( $all_tags ) as $tag_ptr ) {
			if ( $tag_ptr === $first_key ) {
				$in_range = true;
				continue;
			}
			if ( $tag_ptr === $last_key ) {
				break;
			}
			if ( $in_range && ! isset( $since_tags[ $tag_ptr ] ) ) {
				return $tag_ptr;
			}
		}

		return $first_key;
	}

	/**
	 * Determine whether the docblock starting at $comment_start contains an inheritDoc directive.
	 *
	 * Matches `@inheritDoc` as a standalone tag and `{@inheritDoc}` inside any comment-text line, case-insensitive.
	 *
	 * @param int $comment_start Pointer to T_DOC_COMMENT_OPEN_TAG.
	 * @return bool True when an inheritDoc directive is present.
	 */
	private function has_inherit_doc( int $comment_start ): bool {
		$tokens      = $this->phpcsFile->getTokens();
		$comment_end = $tokens[ $comment_start ]['comment_closer'] ?? null;

		if ( null === $comment_end ) {
			return false;
		}

		for ( $i = $comment_start; $i <= $comment_end; $i++ ) {
			$code    = $tokens[ $i ]['code'];
			$content = $tokens[ $i ]['content'];

			if ( \T_DOC_COMMENT_TAG === $code && 0 === strcasecmp( $content, '@inheritDoc' ) ) {
				return true;
			}

			if ( \T_DOC_COMMENT_STRING === $code && false !== stripos( $content, '{@inheritDoc}' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine whether the T_VARIABLE at $stackPtr is a class/trait property.
	 *
	 * @param int $stackPtr Stack pointer to the T_VARIABLE token.
	 * @return bool True when the variable is a class/trait property declaration.
	 */
	private function is_class_property( int $stackPtr ): bool {
		try {
			$this->phpcsFile->getMemberProperties( $stackPtr );
		} catch ( Exception $e ) {
			return false;
		}
		return true;
	}

	/**
	 * Validate the version that follows a single @since tag.
	 *
	 * Supported formats are intentionally narrow: `x.x` and `x.x.x` only. Pre-release suffixes
	 * (`1.0.0-rc1`), single-segment versions (`1`), and historical WP tokens (`MU (3.0.0)`,
	 * `Unknown`) are deliberately rejected — broaden the regex only with an explicit decision.
	 *
	 * @param array<string, mixed>             $tag    Tag record (must contain `tag` => stack pointer).
	 * @param array<int, array<string, mixed>> $tokens PHPCS token stack.
	 * @return array{status: string, version: ?string} `status` is one of `missing`, `invalid`, `ok`.
	 */
	private function check_version( array $tag, array $tokens ): array {
		$version_index = $tag['tag'] + 2;

		if ( ! isset( $tokens[ $version_index ] )
			|| \T_DOC_COMMENT_STRING !== $tokens[ $version_index ]['code']
		) {
			return array(
				'status'  => 'missing',
				'version' => null,
			);
		}

		$version = $tokens[ $version_index ]['content'];
		if ( '' === $version ) {
			return array(
				'status'  => 'missing',
				'version' => null,
			);
		}

		$version_part = preg_split( '/\s+/', $version )[0];

		if ( 1 !== preg_match( '/^\d+\.\d+(\.\d+)?$/', $version_part ) ) {
			return array(
				'status'  => 'invalid',
				'version' => $version_part,
			);
		}

		return array(
			'status'  => 'ok',
			'version' => $version_part,
		);
	}
}
