# AGENTS.md — Nilambar Coding Standard

## Overview

PHP_CodeSniffer standard (`phpcodesniffer-standard`) shipping custom sniff implementations. Built on PHP 7.4+ with PHPCS ^3.13 || ^4.0 and Slevomat namespace rules.

## Setup

```bash
composer install
```

Requires PHP 7.4+. CI runs on Ubuntu with `shivammathur/setup-php@v2`.

## Commands

All commands use Composer script aliases — run from repo root.

| Task | Command |
|---|---|
| Validate composer | `composer validate` |
| Lint PHP syntax | `composer lint-php` |
| PHPCS check | `composer phpcs` |
| Full lint (syntax + PHPCS) | `composer lint` |
| Auto-fix PHPCS violations | `composer format` |
| Run tests | `composer test` |

## Conventions

1. **Sniff registration**: Every new sniff class in `NilambarCodingStandard/Sniffs/` must be registered in `NilambarCodingStandard/ruleset.xml` via `<rule ref="...">`, otherwise consumers won't get it.

2. **Fixture pattern**: Each `*Sniff.php` needs a matching `*UnitTest.php` and one or more `*UnitTest.inc` fixture files under `NilambarCodingStandard/Tests/`. Test classes extend `NilambarCodingStandard\Tests\AbstractSniffUnitTest.php`. Use `getErrorList()` / `getWarningList()` to assert expected violations.

3. **Namespace imports**: No grouped uses, no leading backslash in `use` statements, alphabetical sorting enforced, same-namespace imports prohibited, unused imports flagged (including in annotations). Always reference functions/classes via `use` statements.

4. **No base class inheritance**: Sniffs implement `PHP_CodeSniffer\Sniffs\Sniff` directly — do not wrap or extend third-party base classes. Shared logic belongs in traits under `NilambarCodingStandard/Helpers/`.

5. **PHP 7.4 floor**: All code must remain compatible with PHP 7.4 pinned in `composer.json` `config.platform`. Prefer typed properties and arrow functions where appropriate but never drop the floor.

## Quality gate

**All gates MUST pass before any task is marked complete. No exceptions.**

- `composer format` — auto-fixes PHPCS violations (must run before lint)
- `composer lint` — must exit with zero errors; fix all errors and re-run until clean

If a step fails: fix the issue, then re-run from that step.
