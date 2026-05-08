<?php
/**
 * SinceTagFixerTrait
 *
 * @package Nilambar_Coding_Standard
 */

namespace NilambarCodingStandard\Sniffs\Commenting;

/**
 * Autofix machinery for SinceTagSniff. Lives in its own trait so the host sniff stays focused on detection.
 *
 * Consumers must expose `protected $phpcsFile;` (PHP_CodeSniffer\Files\File), as `WordPressCS\WordPress\Sniff` does.
 *
 * @since 1.0.0
 */
trait SinceTagFixerTrait {

	/**
	 * Insert an empty docblock line directly before the given @since tag.
	 *
	 * @since 1.0.0
	 *
	 * @param int $since_ptr Stack pointer to the offending @since tag.
	 */
	private function fix_missing_empty_line( int $since_ptr ): void {
		$tokens = $this->phpcsFile->getTokens();

		$star_ptr = $this->phpcsFile->findPrevious(
			\T_DOC_COMMENT_STAR,
			$since_ptr - 1
		);
		if ( false === $star_ptr || $tokens[ $star_ptr ]['line'] !== $tokens[ $since_ptr ]['line'] ) {
			return;
		}

		// PHPCS splits a "\n   " sequence into two whitespace tokens — pure newline, then pure indent —
		// so we take the indent token's content directly when it has no newline.
		$indent = '';
		$ws_ptr = $star_ptr - 1;
		if ( $ws_ptr >= 0 && \T_DOC_COMMENT_WHITESPACE === $tokens[ $ws_ptr ]['code'] ) {
			$ws_content  = $tokens[ $ws_ptr ]['content'];
			$newline_pos = strrpos( $ws_content, "\n" );
			$indent      = ( false === $newline_pos ) ? $ws_content : substr( $ws_content, $newline_pos + 1 );
		}

		$this->phpcsFile->fixer->beginChangeset();
		$this->phpcsFile->fixer->addContentBefore( $star_ptr, "*\n" . $indent );
		$this->phpcsFile->fixer->endChangeset();
	}

	/**
	 * Reorder docblock so every @since tag (and its multi-line continuation) sits at the top of the tag block.
	 *
	 * Operates on whole logical lines: each tag, plus every following continuation line that does not itself
	 * open a new tag, is moved as a unit. Description and blank lines before the first tag are preserved.
	 *
	 * @since 1.0.0
	 *
	 * @param int $comment_start Pointer to T_DOC_COMMENT_OPEN_TAG.
	 */
	private function reorder_tags_with_since_first( int $comment_start ): void {
		$tokens      = $this->phpcsFile->getTokens();
		$comment_end = $tokens[ $comment_start ]['comment_closer'] ?? null;

		if ( null === $comment_end ) {
			return;
		}

		$lines = $this->build_doc_block_lines( $comment_start, $comment_end );
		list( $header_lines, $since_blocks, $other_blocks ) = $this->partition_doc_block_lines( $lines );

		if ( empty( $since_blocks ) ) {
			return;
		}

		$close_line  = end( $lines )['text'];
		$new_content = $this->assemble_doc_block( $header_lines, $since_blocks, $other_blocks, $close_line );

		$this->phpcsFile->fixer->beginChangeset();
		$this->phpcsFile->fixer->replaceToken( $comment_start, $new_content );
		for ( $i = $comment_start + 1; $i <= $comment_end; $i++ ) {
			$this->phpcsFile->fixer->replaceToken( $i, '' );
		}
		$this->phpcsFile->fixer->endChangeset();
	}

	/**
	 * Build a per-line view of the docblock between $comment_start and $comment_end (inclusive).
	 *
	 * @since 1.0.0
	 *
	 * @param int $comment_start Pointer to T_DOC_COMMENT_OPEN_TAG.
	 * @param int $comment_end   Pointer to T_DOC_COMMENT_CLOSE_TAG.
	 * @return array<int, array{text:string, is_tag:bool, is_since:bool}> Indexed by file line number.
	 */
	private function build_doc_block_lines( int $comment_start, int $comment_end ): array {
		$tokens     = $this->phpcsFile->getTokens();
		$line_start = $tokens[ $comment_start ]['line'];
		$line_end   = $tokens[ $comment_end ]['line'];

		$lines = array();
		for ( $line = $line_start; $line <= $line_end; $line++ ) {
			$lines[ $line ] = array(
				'text'     => '',
				'is_tag'   => false,
				'is_since' => false,
			);
		}

		for ( $i = $comment_start; $i <= $comment_end; $i++ ) {
			$current_line = $tokens[ $i ]['line'];
			foreach ( explode( "\n", $tokens[ $i ]['content'] ) as $idx => $piece ) {
				$target_line = $current_line + $idx;
				if ( isset( $lines[ $target_line ] ) ) {
					$lines[ $target_line ]['text'] .= $piece;
				}
			}
		}

		$comment_tags = ! empty( $tokens[ $comment_start ]['comment_tags'] )
			? $tokens[ $comment_start ]['comment_tags']
			: array();

		foreach ( $comment_tags as $tag_ptr ) {
			$tag_line = $tokens[ $tag_ptr ]['line'];
			if ( ! isset( $lines[ $tag_line ] ) ) {
				continue;
			}
			$lines[ $tag_line ]['is_tag']   = true;
			$lines[ $tag_line ]['is_since'] = ( '@since' === $tokens[ $tag_ptr ]['content'] );
		}

		return $lines;
	}

	/**
	 * Partition docblock lines into header, @since blocks, and other-tag blocks.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array{text:string, is_tag:bool, is_since:bool}> $lines Per-line view from build_doc_block_lines().
	 * @return array{0: array<int, string>, 1: array<int, array<int, string>>, 2: array<int, array<int, string>>}
	 *         Tuple of (header_lines, since_blocks, other_blocks). Each block is an array of line texts.
	 */
	private function partition_doc_block_lines( array $lines ): array {
		$line_end       = array_key_last( $lines );
		$header_lines   = array();
		$since_blocks   = array();
		$other_blocks   = array();
		$current_block  = null;
		$first_tag_seen = false;

		foreach ( $lines as $line => $info ) {
			if ( ! $first_tag_seen && ! $info['is_tag'] ) {
				$header_lines[] = $info['text'];
				continue;
			}

			if ( $info['is_tag'] ) {
				$this->flush_block( $current_block, $since_blocks, $other_blocks );
				$current_block  = array(
					'is_since' => $info['is_since'],
					'lines'    => array( $info['text'] ),
				);
				$first_tag_seen = true;
				continue;
			}

			if ( null !== $current_block && $line < $line_end ) {
				$current_block['lines'][] = $info['text'];
			}
		}

		$this->flush_block( $current_block, $since_blocks, $other_blocks );

		return array( $header_lines, $since_blocks, $other_blocks );
	}

	/**
	 * Append the in-progress block to either $since_blocks or $other_blocks.
	 *
	 * @since 1.0.0
	 *
	 * @param array{is_since:bool, lines:array<int, string>}|null $block        In-progress block, or null when there's nothing to flush.
	 * @param array<int, array<int, string>>                      $since_blocks Accumulator (passed by reference).
	 * @param array<int, array<int, string>>                      $other_blocks Accumulator (passed by reference).
	 */
	private function flush_block( ?array $block, array &$since_blocks, array &$other_blocks ): void {
		if ( null === $block ) {
			return;
		}
		if ( $block['is_since'] ) {
			$since_blocks[] = $block['lines'];
		} else {
			$other_blocks[] = $block['lines'];
		}
	}

	/**
	 * Reassemble the docblock body in the canonical order: header, @since blocks, other blocks, close line.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>             $header_lines Header lines (description, blank lines).
	 * @param array<int, array<int, string>> $since_blocks @since tag blocks (each block is a tag line plus continuation lines).
	 * @param array<int, array<int, string>> $other_blocks Non-@since tag blocks.
	 * @param string                         $close_line   The line containing the close tag.
	 * @return string The reassembled docblock content.
	 */
	private function assemble_doc_block( array $header_lines, array $since_blocks, array $other_blocks, string $close_line ): string {
		$body = $header_lines;
		foreach ( $since_blocks as $block ) {
			foreach ( $block as $bline ) {
				$body[] = $bline;
			}
		}
		foreach ( $other_blocks as $block ) {
			foreach ( $block as $bline ) {
				$body[] = $bline;
			}
		}
		$body[] = $close_line;

		return implode( "\n", $body );
	}
}
