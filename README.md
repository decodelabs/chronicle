# Chronicle

[![PHP from Packagist](https://img.shields.io/packagist/php-v/decodelabs/chronicle?style=flat)](https://packagist.org/packages/decodelabs/chronicle)
[![Latest Version](https://img.shields.io/packagist/v/decodelabs/chronicle.svg?style=flat)](https://packagist.org/packages/decodelabs/chronicle)
[![Total Downloads](https://img.shields.io/packagist/dt/decodelabs/chronicle.svg?style=flat)](https://packagist.org/packages/decodelabs/chronicle)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/decodelabs/chronicle/integrate.yml?branch=develop)](https://github.com/decodelabs/chronicle/actions/workflows/integrate.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-44CC11.svg?longCache=true&style=flat)](https://github.com/phpstan/phpstan)
[![License](https://img.shields.io/packagist/l/decodelabs/chronicle?style=flat)](https://packagist.org/packages/decodelabs/chronicle)

### Release notes generator

Chronicle provides a set of tools for parsing, generating and rendering release notes and change logs.

---

## Installation

Install via Composer:

```bash
composer require decodelabs/chronicle
```

## Usage

Open a `Repository` in the root of your project - if you don't pass a path, the repository will use your project root defined via `Monarch`.

```php
use DecodeLabs\Chronicle\Repository;

$repo = new Repository();
$repo = new Repository('/path/to/your/repo');
```

Parse your existing change log file - name defaults to `CHANGELOG.md`. Set rewrite to `true` to reformat release headers and layout in Chronicle's format - useful for converting existing change logs.

If your change log file doesn't exist, an empty template document will be created.

```php
$doc = $repo->parseChangeLog(
    fileName: 'CHANGELOG.md',
    rewrite: true
);
```

### Structure

The parser expects three main sections in the change log file:

- **Preamble**: This is the first section of the file, which contains general information about the change log
- **Unreleased**: This section contains unreleased changes, which are not yet assigned to a specific version
- **Releases**: This section contains the list of released versions, each with its own set of changes

```markdown
# Changelog

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

- This is a block of unreleased changes
- It is used to generate the next release

It doesn't have a version number, and can be in list or free text format. It just requires an "Unreleased" header.


## [v0.2.0](https://github.com/decodelabs/chronicle/commits/v0.2.0) - 16th May 2025

- Added a new feature

[Full list of changes](https://github.com/decodelabs/chronicle/compare/v0.1.0...v0.2.0)


## [v0.1.0](https://github.com/decodelabs/chronicle/commits/v0.1.0) - 15th May 2025

- Implemented basic ChangeLog parser
- Implemented ChangeLog document renderer
- Built GitHub issue and pull request fetchers
```

### Next release

You can add unreleased changes to the change log in the `Unreleased` section as you work on your project. When you're ready to release a new version, you can use the `generateNextRelease()` method to turn those changes into a new release.

```php
$doc->generateNextRelease(
    version: 'patch', // major, minor, patch, preRelease, breaking, feature or v0.2.0
    date: '2025-05-16', // optional, defaults to today
    repository: $repo
);
```

if your project is hosted on GitHub, `generateNextRelease()` will automatically fetch the latest issues and pull requests from the repository and add them to the release notes.

if your project is private or you hit the API rate limit, you can place your access token in your `.env` file with the key `GITHUB_TOKEN`. Chronicle will automatically authenticate with the GitHub API using this token.

```markdown
## [v0.2.0](https://github.com/decodelabs/chronicle/commits/v0.2.0) - 16th May 2025

- Added a new feature

### Merged Pull Requests
- [#123](https://github.com/decodelabs/chronicle/pull/123) - Fixed an issue
- [#456](https://github.com/decodelabs/chronicle/pull/456) - Added another new feature

### Issues
- [#789](https://github.com/decodelabs/chronicle/issues/789) - Fixed a bug
```

### Rendering

You can render the change log document to a string using the `render()` method, or back to the original file using `save()`. When you call either of these methods, you can specify a `Renderer` implementation which you can use to customize the output format. If ommitted, the default renderer will be used.

```php
use DecodeLabs\Chronicle\ChangeLog\Renderer;
use MyApp\Chronicle\ChangeLog\Renderer\MyCustomRenderer;

$doc->render(
    renderer: new MyCustomRenderer() // Instance of Renderer
);
```


## Licensing

Chronicle is licensed under the MIT License. See [LICENSE](./LICENSE) for the full license text.
