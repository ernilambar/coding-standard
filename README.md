# Nilambar Coding Standard

PHP_CodeSniffer standard for PHP projects.

## Requirements

- PHP 7.4+
- `squizlabs/php_codesniffer` ^3.13 or ^4.0

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
| `Commenting.AllCapsComment` | Flags inline comments written in ALL CAPS |
| `Commenting.SinceTag` | Enforces `@since` tag presence and format in PHPDoc |

## License

MIT
