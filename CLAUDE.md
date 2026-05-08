# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

This package is a PHP_CodeSniffer standard (`phpcodesniffer-standard` Composer type) that ships custom sniffs targeting WordPress code. It is consumed by other projects via Composer; the rules in [NilambarCodingStandard/ruleset.xml](NilambarCodingStandard/ruleset.xml) are what end users get when they reference the `NilambarCodingStandard` standard. Code must run on PHP 7.4+ — the platform is pinned in [composer.json](composer.json).

## Common commands

All scripts go through Composer. Run from the repo root:

- `composer test` — PHPUnit suite for the sniffs. Filters down to `NilambarCodingStandard` and runs against PHPCS's `AllTests.php` harness (see [phpunit.xml.dist](phpunit.xml.dist) and [Tests/bootstrap.php](Tests/bootstrap.php)).
- `composer test -- --filter SinceTagUnitTest` — run a single sniff's unit test (pass any PHPUnit `--filter` pattern after `--`).
- `composer phpcs` — lint this repo's own PHP using [phpcs.xml.dist](phpcs.xml.dist) (WordPress + selected Slevomat rules).
- `composer format` — auto-fix with `phpcbf`.
- `composer lint` — runs `lint-php` (parallel-lint syntax check) followed by `phpcs`.
- `composer phpmd` — mess detector against [phpmd.xml](phpmd.xml).

## Architecture

### Sniff organization

Sniffs live under [NilambarCodingStandard/Sniffs/](NilambarCodingStandard/Sniffs/), grouped into four categories that mirror the ruleset references: `CodeAnalysis/`, `Commenting/`, `Language/`, `Security/`. Each `*Sniff.php` is registered in [NilambarCodingStandard/ruleset.xml](NilambarCodingStandard/ruleset.xml) — adding a new sniff requires both the class and the `<rule ref>` entry, otherwise it won't ship to consumers.

Most sniffs extend `WordPressCS\WordPress\Sniff` (or `AbstractFunctionParameterSniff` for function-parameter checks). Shared traits live in [NilambarCodingStandard/Helpers/](NilambarCodingStandard/Helpers/) — e.g. `CommentTrait` and `EntityTrait` used by `Commenting/SinceTagSniff`.

### Test harness

The PHPCS-style fixture pattern is enforced:

- Each sniff `Foo/BarSniff.php` has a matching `Foo/BarUnitTest.php` (the test class) and one or more `Foo/BarUnitTest.inc` (or `BarUnitTest.1.inc`, `BarUnitTest.2.inc`) fixture files under [NilambarCodingStandard/Tests/](NilambarCodingStandard/Tests/).
- Test classes extend [NilambarCodingStandard/Tests/AbstractSniffUnitTest.php](NilambarCodingStandard/Tests/AbstractSniffUnitTest.php), which wraps PHPCS's `AbstractSniffUnitTest`. The wrapper exists because the upstream harness does not apply sniff properties from `ruleset.xml`; subclasses must implement `get_sniff_fqcn()` and `set_sniff_parameters()` so per-test ruleset properties take effect.
- `getErrorList()` / `getWarningList()` return `<line number> => <count>` arrays describing expected violations in the `.inc` fixture.
- The bootstrap in [Tests/bootstrap.php](Tests/bootstrap.php) sets `PHPCS_IGNORE_TESTS` to disable every standard except `NilambarCodingStandard`, so cross-standard fixtures don't pollute results.

### Data files

[data/i18n/](data/i18n/) contains generated lists (`admin.php`, `core.php`) of WordPress functions whose user-facing parameters require translation — consumed by `Language/I18nFunctionParametersSniff`. Treat these as data, not source: the `phpcs.xml.dist` excludes them from linting.

## Conventions enforced on this repo's own code

[phpcs.xml.dist](phpcs.xml.dist) applies `WordPress` (minus `WordPress.Files.FileName` and `WordPress.NamingConventions.ValidVariableName`) plus PSR-1 class declarations and several Slevomat namespace rules: no grouped/leading-backslash uses, alphabetically sorted uses, no same-namespace uses, unused-use detection (including in annotations), and `ReferenceUsedNamesOnly` (import classes — fully qualified globals/exceptions are forbidden, fully qualified functions/constants are allowed).

Style rules that aren't auto-enforced:

- Use Yoda conditions, always.
- Inline PHP comments start with a capital letter and end with a period. Keep them concise.
- Prefer PHP 7.4+ features (typed properties, arrow functions) where they fit, but don't break the 7.4 floor pinned in [composer.json](composer.json).
- Follow the general WordPress PHP Coding Standards style for sniff source code (this is a WordPress-focused standards package; the surrounding ecosystem expects WP conventions).
