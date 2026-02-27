[contributors-shield]: https://img.shields.io/github/contributors/jobmetric/laravel-env-modifier.svg?style=for-the-badge
[contributors-url]: https://github.com/jobmetric/laravel-env-modifier/graphs/contributors
[forks-shield]: https://img.shields.io/github/forks/jobmetric/laravel-env-modifier.svg?style=for-the-badge&label=Fork
[forks-url]: https://github.com/jobmetric/laravel-env-modifier/network/members
[stars-shield]: https://img.shields.io/github/stars/jobmetric/laravel-env-modifier.svg?style=for-the-badge
[stars-url]: https://github.com/jobmetric/laravel-env-modifier/stargazers
[license-shield]: https://img.shields.io/github/license/jobmetric/laravel-env-modifier.svg?style=for-the-badge
[license-url]: https://github.com/jobmetric/laravel-env-modifier/blob/master/LICENCE.md
[linkedin-shield]: https://img.shields.io/badge/-LinkedIn-blue.svg?style=for-the-badge&logo=linkedin&colorB=555
[linkedin-url]: https://linkedin.com/in/majidmohammadian

[![Contributors][contributors-shield]][contributors-url]
[![Forks][forks-shield]][forks-url]
[![Stargazers][stars-shield]][stars-url]
[![MIT License][license-shield]][license-url]
[![LinkedIn][linkedin-shield]][linkedin-url]

# Laravel Env Modifier

**Manage .env Files. Safely and Easily.**

Laravel Env Modifier simplifies working with `.env` files in Laravel applications. Stop managing environment files manually and start automating configuration management with confidence. It provides a clean API to create, read, merge, update, back up, restore, and delete `.env` files safely and predictably—perfect for deployment scripts, environment management, and configuration automation. This is where powerful file management meets developer-friendly simplicity—giving you complete control over your environment configuration without the complexity.

## Why Laravel Env Modifier??????

### Safe File Operations

Laravel Env Modifier uses atomic writes with `LOCK_EX` to prevent race conditions when multiple processes access the same `.env` file. It also includes protection mechanisms to prevent accidental deletion of your main application `.env` file.

### Smart Value Handling

The package automatically handles value normalization, escaping, and quoting. It intelligently quotes values containing spaces, special characters, or newlines, and properly handles booleans, arrays, and JSON data.

### Preserves Comments and Formatting

Unlike many `.env` manipulation tools, Laravel Env Modifier preserves comments and blank lines in your `.env` files. It only modifies the specific keys you target, leaving the rest of your file structure intact.

### Multiple File Support

Work with multiple `.env` files simultaneously. Create environment-specific files (`.env.staging`, `.env.testing`), merge configurations from templates, and manage different environments without code changes.

## What is Env File Management?

Env file management is the process of programmatically reading, writing, and modifying environment configuration files. Traditional approaches often involve manual editing or fragile string manipulation, but Laravel Env Modifier provides a robust, safe solution:

- **Atomic Operations**: All file writes use exclusive locks to prevent corruption
- **Key-Level Operations**: Read, write, rename, and delete individual keys without affecting others
- **File-Level Operations**: Create, backup, restore, and delete entire `.env` files
- **Merge Capabilities**: Combine configurations from multiple files with filtering options
- **Value Normalization**: Automatically handles quoting, escaping, and type conversion

Consider a deployment script that needs to update environment variables based on the deployment environment. With Laravel Env Modifier, you can create environment-specific files, merge configurations from templates, set defaults, create backups before risky operations, and restore if something goes wrong. The power of env file management lies not only in programmatic access but also in making it safe, predictable, and easy to use throughout your application.

## What Awaits You?

By adopting Laravel Env Modifier, you will:

- **Automate deployments** - Programmatically configure environments during deployment
- **Simplify environment management** - Manage multiple environments with ease
- **Improve safety** - Atomic writes and main `.env` protection prevent accidents
- **Preserve file structure** - Comments and formatting remain intact
- **Handle complex values** - Automatic normalization for booleans, arrays, and JSON
- **Maintain clean code** - Simple, intuitive API that follows Laravel conventions

## Quick Start

Install Laravel Env Modifier via Composer:

```bash
composer require jobmetric/laravel-env-modifier
```

## Documentation

Ready to transform your Laravel applications? Our comprehensive documentation is your gateway to mastering Laravel Env Modifier:

**[📚 Read Full Documentation →](https://jobmetric.github.io/packages/laravel-env-modifier/)**

The documentation includes:

- **Getting Started** - Quick introduction and installation guide
- **EnvModifier** - Complete API reference for file and key operations
- **File Operations** - Create, backup, restore, delete, and merge `.env` files
- **Key Operations** - Read, write, rename, delete, and check existence of keys
- **Value Normalization** - Automatic handling of booleans, arrays, JSON, and special characters
- **Helper Functions** - Convenient global helpers for common operations
- **Real-World Examples** - See how it works in practice

## Contributing

Thank you for participating in `laravel-env-modifier`. A contribution guide can be found [here](CONTRIBUTING.md).

## License

The `laravel-env-modifier` is open-sourced software licensed under the MIT license. See [License File](LICENCE.md) for more information.
