# Nilambar Coding Standard

PHP_CodeSniffer standard targeting WordPress code.

## Installation

```bash
composer require --dev ernilambar/coding-standard
```

## Usage

Reference the standard in your `phpcs.xml`:

```xml
<ruleset>
    <rule ref="NilambarCodingStandard" />
</ruleset>
```

Or run directly:

```bash
./vendor/bin/phpcs --standard=NilambarCodingStandard src/
```

## Sniffs

| Sniff | Description |
|---|---|
| `CodeAnalysis.RequiredFunctionParameters` | Flags missing required function parameters |
| `CodeAnalysis.RestrictedConstants` | Flags restricted `define()` variables |
| `Commenting.AllCapsComment` | Flags inline comments written in ALL CAPS |
| `Commenting.SinceTag` | Enforces `@since` tag presence and format in PHPDoc |

## Requirements

- PHP 7.4+
- `wp-coding-standards/wpcs` ^3.3

## License

MIT
