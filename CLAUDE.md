# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

This package is a PHP_CodeSniffer standard (`phpcodesniffer-standard` Composer type) that ships custom sniffs. It is consumed by other projects via Composer; the rules in [NilambarCodingStandard/ruleset.xml](NilambarCodingStandard/ruleset.xml) are what end users get when they reference the `NilambarCodingStandard` standard. Code must run on PHP 7.4+ — the platform is pinned in [composer.json](composer.json). Requires PHPCS `^3.13 || ^4.0`.

## Common commands

All scripts go through Composer. Run from the repo root:

- `composer test` — PHPUnit suite for the sniffs (see [phpunit.xml.dist](phpunit.xml.dist) and [Tests/bootstrap.php](Tests/bootstrap.php)).
- `composer test -- --filter SinceTagUnitTest` — run a single sniff's unit test (pass any PHPUnit `--filter` pattern after `--`).
- `composer phpcs` — lint this repo's own PHP using [phpcs.xml.dist](phpcs.xml.dist).
- `composer format` — auto-fix with `phpcbf`.
- `composer lint` — runs `lint-php` (parallel-lint syntax check) followed by `phpcs`.

## Architecture

### Sniff organization

Sniffs live under [NilambarCodingStandard/Sniffs/](NilambarCodingStandard/Sniffs/), grouped into categories that mirror the ruleset references: `Commenting/`. Each `*Sniff.php` is registered in [NilambarCodingStandard/ruleset.xml](NilambarCodingStandard/ruleset.xml) — adding a new sniff requires both the class and the `<rule ref>` entry, otherwise it won't ship to consumers.

All sniffs implement `PHP_CodeSniffer\Sniffs\Sniff` directly — no third-party base class. Shared traits live in [NilambarCodingStandard/Helpers/](NilambarCodingStandard/Helpers/) — e.g. `CommentTrait` and `EntityTrait` used by `Commenting/SinceTagSniff`.

### Test harness

The PHPCS-style fixture pattern is enforced:

- Each sniff `Foo/BarSniff.php` has a matching `Foo/BarUnitTest.php` (the test class) and one or more `Foo/BarUnitTest.inc` fixture files under [NilambarCodingStandard/Tests/](NilambarCodingStandard/Tests/).
- Test classes extend [NilambarCodingStandard/Tests/AbstractSniffUnitTest.php](NilambarCodingStandard/Tests/AbstractSniffUnitTest.php), which wraps PHPCS's `AbstractSniffTestCase`.
- `getErrorList()` / `getWarningList()` return `<line number> => <count>` arrays describing expected violations in the `.inc` fixture.
- The bootstrap in [Tests/bootstrap.php](Tests/bootstrap.php) sets `PHPCS_IGNORE_TESTS` to disable every standard except `NilambarCodingStandard`, so cross-standard fixtures don't pollute results.

## Conventions enforced on this repo's own code

[phpcs.xml.dist](phpcs.xml.dist) applies selected Slevomat namespace rules: no grouped/leading-backslash uses, alphabetically sorted uses, no same-namespace uses, unused-use detection (including in annotations), and `ReferenceUsedNamesOnly`.

Style rules that aren't auto-enforced:

- Inline PHP comments start with a capital letter and end with a period. Keep them concise.
- Prefer PHP 7.4+ features (typed properties, arrow functions) where they fit, but don't break the 7.4 floor pinned in [composer.json](composer.json).

## Quality gate

**All gates MUST pass before any task is marked complete. No exceptions.**

- `composer format` — auto-fixes PHPCS violations (must run before lint)
- `composer lint` — must exit with zero errors; fix all errors and re-run until clean

If a step fails: fix the issue, then re-run from that step.
