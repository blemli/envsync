# Sync Laravel .env files

[![Latest Version on Packagist](https://img.shields.io/packagist/v/blemli/envsync.svg?style=flat-square)](https://packagist.org/packages/blemli/envsync)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/blemli/envsync/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/blemli/envsync/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/blemli/envsync/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/blemli/envsync/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/blemli/envsync.svg?style=flat-square)](https://packagist.org/packages/blemli/envsync)

A powerful Laravel package for synchronizing environment files with interactive prompts and intelligent conflict resolution. EnvSync helps you keep your `.env`, `.env.example`, and other environment files in perfect harmony.



> [!CAUTION] 
>
>  🤖🧠 This Package was vibe coded 🤖🧠



**Key Features:**

- 🔄 **Interactive Sync**: Per-key prompts for differing values with sync, ignore, or permanent ignore options
- 🚫 **Smart Ignoring**: Use `#ENVIGNORE` comments to permanently ignore specific environment variables
- 📁 **Structure Preservation**: Maintains file formatting, comments, and organization
- ⚡ **Auto-sync Mode**: Automatically sync all differences without prompts
- 🔒 **Version Control Aware**: Handles version-controlled files intelligently

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/envsync.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/envsync)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require blemli/envsync
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="envsync-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="envsync-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="envsync-views"
```

## Usage

### Basic Sync

Sync your `.env` file with `.env.example`:

```bash
php artisan env:sync
```

Sync with a different target file:

```bash
php artisan env:sync --path=.env.production
```

### Interactive Mode (Default)

When differences are detected, you'll get interactive prompts for each differing key:

```
Key 'DATABASE_URL' has different values:
  .env: 'mysql://localhost/prod_db'
  .env.example: 'mysql://localhost/example_db'

What would you like to do with this key?
  [0] Sync from .env to .env.example
  [1] Ignore once (skip this time only)
  [2] Forever ignore (add #ENVIGNORE to this line)
  [3] Quit without making changes
```

### Auto-Sync Mode

Automatically sync all differing values without prompts:

```bash
php artisan env:sync --auto-sync
```

### Force Mode

Skip all confirmation prompts and apply all changes automatically:

```bash
php artisan env:sync --force
```

### Permanent Ignoring with #ENVIGNORE

Add `#ENVIGNORE` to any line in your target file to permanently ignore differences for that key:

```env
# .env.example
APP_NAME=ExampleApp
DATABASE_URL=mysql://localhost/example_db #ENVIGNORE
API_KEY=your_api_key_here
```

The `DATABASE_URL` line will be ignored in all future sync operations.

### Examples

**Scenario 1: New developer setup**
```bash
# Copy .env.example to .env, then sync any missing keys
cp .env.example .env
php artisan env:sync --path=.env.example
```

**Scenario 2: Production deployment**
```bash
# Sync production environment file
php artisan env:sync --path=.env.production --auto-sync
```

**Scenario 3: Team collaboration**
```bash
# Interactive sync to review each difference
php artisan env:sync --path=.env.example
```

### Command Options

| Option | Description |
|--------|-------------|
| `--path=FILE`🤖🧠 | Target file to sync with (default: `.env.example`) |
| `--force` | Skip all confirmation prompts and apply changes automatically |
| `--auto-sync` | Automatically sync all differing values from source to target |

### How It Works

1. **Missing Keys**: Prompts to add keys that exist in source but not in target
2. **Extra Keys**: Prompts to remove keys that exist in target but not in source  
3. **Differing Values**: Interactive prompts with options to sync, ignore once, or ignore forever
4. **Version Control**: Automatically detects version-controlled files and handles them appropriately
5. **Structure Preservation**: Maintains original file formatting, comments, and organization

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Stephan Graf](https://github.com/grafst)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
