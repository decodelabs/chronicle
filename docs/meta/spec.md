# Chronicle — Package Specification

> **Cluster:** `tooling`
> **Language:** `php`
> **Milestone:** `m4`
> **Repo:** `https://github.com/decodelabs/chronicle`
> **Role:** Release manager

This document describes the purpose, contracts, and design of **Chronicle** within the Decode Labs ecosystem.

It is aimed at:

- Developers **using** Chronicle in their own applications or libraries.
- Contributors **maintaining or extending** Chronicle.
- Tools and AI assistants that need to reason about its behaviour.

---

## 1. Overview

### 1.1 Purpose

Chronicle provides a comprehensive set of tools for parsing, generating, and rendering release notes and changelogs. It supports the [Keep a Changelog](https://keepachangelog.com/) format and integrates with GitHub to automatically fetch issues and pull requests for releases. Chronicle can parse existing changelog files, generate new releases from unreleased changes, and render changelogs in customizable formats.

### 1.2 Non-Goals

Chronicle does **not**:

- Manage Git tags or releases directly — it works with existing Git repositories
- Provide version bumping or semantic versioning calculation beyond what's needed for release generation
- Handle multiple changelog files or formats beyond Markdown
- Provide web UI or dashboard for changelog management
- Support Git hosting services other than GitHub (though the architecture allows for extension)
- Automatically commit or push changelog changes to repositories

---

## 2. Role in the Ecosystem

### 2.1 Cluster & Positioning

- **Cluster:** `tooling` (see Chorus taxonomy)
- Chronicle is a development tooling package that helps maintain release documentation. It sits in the tooling cluster alongside other developer productivity tools. It depends on several foundational packages (Atlas, Dovetail, Enumerable, Exceptional, Slingshot, Stash, Systemic) and integrates with external services (GitHub API) to provide comprehensive release management capabilities.

### 2.2 Typical Usage Contexts

Typical places Chronicle appears:

- CI/CD pipelines for automated release note generation
- Release management scripts and tools
- Developer workflows for maintaining project changelogs
- Automated documentation generation systems
- Package release automation (e.g., in Effigy)

Chronicle is intended to be used whenever a project needs to maintain structured release notes in the Keep a Changelog format, especially when integrated with GitHub for automatic issue and pull request tracking.

---

## 3. Public Surface

> This section focuses on the conceptual API, not every symbol.

### 3.1 Key Types

The primary public types are:

- `DecodeLabs\Chronicle\Repository`
  Main entry point representing a Git repository. Provides methods to parse changelogs, access Git metadata (branches, tags, config), and interact with service providers (e.g., GitHub).

- `DecodeLabs\Chronicle\ChangeLog\Document`
  Represents a parsed changelog document with preamble, unreleased section, and releases. Provides methods to generate next releases, validate versions, and render/save the document.

- `DecodeLabs\Chronicle\ChangeLog\Parser`
  Parses Markdown changelog files into Document structures. Supports rewriting existing changelogs to Chronicle's format.

- `DecodeLabs\Chronicle\ChangeLog\Renderer`
  Interface for customizing changelog output format. Implementations control how blocks (preamble, releases, issues, pull requests) are rendered.

- `DecodeLabs\Chronicle\ChangeLog\Renderer\Generic`
  Default renderer implementation that outputs Markdown in Keep a Changelog format.

- `DecodeLabs\Chronicle\Service`
  Interface for Git hosting service integrations (e.g., GitHub). Provides methods to fetch issues, pull requests, and publish releases.

- `DecodeLabs\Chronicle\Service\GitHub`
  GitHub API integration implementation. Fetches issues and pull requests, generates URLs, and publishes releases via GitHub API.

- `DecodeLabs\Chronicle\ChangeLog\Block\Release`
  Interface representing a release block with version, date, and URLs.

- `DecodeLabs\Chronicle\ChangeLog\Block\Unreleased`
  Represents the unreleased changes section.

- `DecodeLabs\Chronicle\ChangeLog\Block\Preamble`
  Represents the changelog preamble section.

- `DecodeLabs\Chronicle\ChangeLog\Block\Issue`
  Represents a closed issue from the service provider.

- `DecodeLabs\Chronicle\ChangeLog\Block\PullRequest`
  Represents a merged pull request from the service provider.

- `DecodeLabs\Chronicle\VersionChange`
  Enum for semantic versioning change types (Major, Minor, Patch, PreRelease, Breaking, Feature).

### 3.2 Main Entry Points

The main usage pattern is through the `Repository` class:

```php
use DecodeLabs\Chronicle\Repository;
use DecodeLabs\Systemic;

$repo = new Repository('/path/to/repo', $systemic);
$doc = $repo->parseChangeLog('CHANGELOG.md', rewrite: true);
```

---

## 4. Dependencies

### 4.1 Decode Labs

- `decodelabs/atlas` (required)
  Used for filesystem operations to read and write changelog files.

- `decodelabs/dovetail` (required)
  Used for environment variable access (e.g., `GITHUB_TOKEN`).

- `decodelabs/enumerable` (required)
  Used for the `VersionChange` enum implementation.

- `decodelabs/exceptional` (required)
  Used for exception handling throughout the package.

- `decodelabs/slingshot` (required)
  Used for dependency injection to resolve service implementations.

- `decodelabs/stash` (required)
  Used for caching GitHub API responses.

- `decodelabs/systemic` (required)
  Used for executing Git commands and process management.

### 4.2 External

- `nesbot/carbon` (required)
  Used for date/time handling and parsing.

- `guzzlehttp/guzzle` (required)
  HTTP client for GitHub API requests.

- `http-interop/http-factory-guzzle` (required)
  PSR HTTP factory implementation for Guzzle.

- `knplabs/github-api` (required)
  GitHub API client library.

- `z4kn4fein/php-semver` (required)
  Semantic versioning parsing and manipulation.

### 4.3 Optional Integrations

- None (all integrations are required dependencies)

---

## 5. Behaviour & Contracts

### 5.1 Invariants

- Changelog files must be Markdown (`.md` extension)
- Release versions must follow semantic versioning format
- Releases are always sorted in descending version order (newest first)
- The unreleased section is always rendered before releases
- Preamble is always rendered first (if present)
- GitHub API calls are cached using Stash
- Git operations use Systemic for process execution

### 5.2 Input & Output Contracts

**Input:**
- `Repository::parseChangeLog(?string $fileName, bool $rewrite): Document`
  - Accepts optional filename (defaults to `CHANGELOG.md`)
  - If file doesn't exist and `rewrite` is false, `rewrite` is automatically set to true
  - Returns a `Document` with parsed changelog structure

- `Document::generateNextRelease(string|VersionChange $version, ?Repository $repository, string|Carbon|null $date): void`
  - Accepts version as string (e.g., `v0.2.0`) or `VersionChange` enum (e.g., `VersionChange::Patch`)
  - Validates version doesn't already exist
  - Automatically increments version if `VersionChange` enum is provided
  - Fetches issues and pull requests from service if repository and service are available
  - Moves unreleased changes to the new release

- `Document::render(?Renderer $renderer): string`
  - Accepts optional custom renderer (defaults to `GenericRenderer`)
  - Returns rendered Markdown string

- `Document::save(?Renderer $renderer, ?string $path): File`
  - Renders and saves changelog to file
  - Returns Atlas `File` object

**Output:**
- All rendering produces Markdown format
- Release headers include version, date, and optional links
- Issues and pull requests are rendered as lists with titles, numbers, and URLs
- Compare URLs are generated for releases when service is available

### 5.3 Version Handling

- Versions can be specified as strings (e.g., `v0.2.0`) or using `VersionChange` enum
- `VersionChange::Breaking` maps to Major for versions >= 1.0.0, Minor for < 1.0.0
- `VersionChange::Feature` maps to Minor for versions >= 1.0.0, Patch for < 1.0.0
- Version validation ensures no duplicate versions exist
- Semantic versioning is enforced using `z4kn4fein/php-semver`

### 5.4 GitHub Integration

- Automatically detects GitHub repositories from Git remote URL
- Uses `GITHUB_TOKEN` environment variable for authentication (optional, for rate limits and private repos)
- Fetches merged pull requests and closed issues between release dates
- Generates compare URLs and commits URLs automatically
- Can publish releases to GitHub via API

---

## 6. Error Handling

- `Repository::parseChangeLog()` throws `Exceptional::NotFound` if file doesn't exist and `rewrite` is false
- `Parser` throws `Exceptional::InvalidArgument` if file is not Markdown
- `Document::validateNextVersion()` throws `Exceptional::InvalidArgument` if version already exists
- `Document::generateNextRelease()` throws `Exceptional::Runtime` if last release date cannot be parsed
- `Repository::loadGitConfig()` throws `Exceptional::Runtime` if Git config cannot be loaded
- GitHub API errors are caught and return empty arrays (graceful degradation)
- Git command failures return `null` or throw `Exceptional::Runtime` depending on context

---

## 7. Configuration & Extensibility

### 7.1 Environment Variables

- `GITHUB_TOKEN`: Optional GitHub personal access token for API authentication. Used to avoid rate limits and access private repositories.

### 7.2 Custom Renderers

Custom renderers can be implemented by implementing the `Renderer` interface:

```php
use DecodeLabs\Chronicle\ChangeLog\Renderer;

class MyCustomRenderer implements Renderer
{
    // Implement all interface methods
}
```

### 7.3 Service Providers

Custom service providers can be implemented by implementing the `Service` interface. The `Repository` automatically detects GitHub repositories, but other services can be added by extending the service detection logic.

### 7.4 Renderer Options

The `GenericRenderer` accepts an `Options` object that controls:
- Whether to show issue assignees
- Whether to show pull request assignees

---

## 8. Interactions with Other Packages

### 8.1 Atlas

Chronicle uses Atlas for all filesystem operations. Changelog files are read and written using Atlas's file abstraction.

### 8.2 Dovetail

Chronicle uses Dovetail's `Env` class to access the `GITHUB_TOKEN` environment variable.

### 8.3 Systemic

Chronicle uses Systemic to execute Git commands (e.g., `git branch`, `git tag`, `git log`) for repository metadata.

### 8.4 Stash

Chronicle uses Stash to cache GitHub API responses, reducing API calls and improving performance.

### 8.5 Slingshot

Chronicle uses Slingshot for dependency injection to resolve service implementations (e.g., `GitHubService`).

---

## 9. Usage Examples

### 9.1 Basic Parsing

```php
use DecodeLabs\Chronicle\Repository;
use DecodeLabs\Systemic;

$repo = new Repository('/path/to/repo', $systemic);
$doc = $repo->parseChangeLog('CHANGELOG.md');

// Access parsed structure
$lastRelease = $doc->getLastRelease();
$unreleased = $doc->unreleased;
```

### 9.2 Generating Next Release

```php
use DecodeLabs\Chronicle\VersionChange;

$doc->generateNextRelease(
    version: VersionChange::Patch,
    repository: $repo,
    date: '2025-05-16'
);

// Save the updated changelog
$doc->save();
```

### 9.3 Custom Renderer

```php
use DecodeLabs\Chronicle\ChangeLog\Renderer\Generic;
use MyApp\Chronicle\MyCustomRenderer;

$renderer = new MyCustomRenderer();
$markdown = $doc->render($renderer);
```

### 9.4 Publishing to GitHub

```php
use DecodeLabs\Chronicle\ChangeLog\Block\Buffered\NextRelease;
use DecodeLabs\Chronicle\ChangeLog\Renderer\Generic;

$nextRelease = new NextRelease(/* ... */);
$published = $repo->publishNextRelease(
    $nextRelease,
    new GenericRenderer()
);
```

---

## 10. Implementation Notes (for Contributors)

### 10.1 Changelog Structure

Chronicle expects changelogs to follow the Keep a Changelog format:
- **Preamble**: First section with general information
- **Unreleased**: Section for unreleased changes
- **Releases**: List of versioned releases (newest first)

### 10.2 Parsing Strategy

The parser uses a line-by-line approach:
1. Detects block headers (preamble, unreleased, releases)
2. Collects body content until next header
3. Consolidates blocks based on rewrite flag
4. Generates missing URLs from service if available

### 10.3 Version Incrementing

Version incrementing uses semantic versioning rules:
- Pre-1.0.0 versions: Breaking → Minor, Feature → Patch
- Post-1.0.0 versions: Breaking → Major, Feature → Minor
- Uses `z4kn4fein/php-semver` for version manipulation

### 10.4 GitHub API Integration

GitHub integration:
- Uses `knplabs/github-api` for API access
- Caches responses using Stash
- Authenticates using `GITHUB_TOKEN` if available
- Filters issues and pull requests by date range
- Sorts results chronologically

### 10.5 Git Operations

Git operations use Systemic:
- All Git commands executed via `systemic->capture()`
- Commands run in repository directory context
- Errors handled gracefully (return null or throw exceptions)

---

## 11. Testing & Quality

- **Code Quality Score:** 4/5
- **README Quality Score:** 3.5/5
- **Documentation Score:** 0/5 (this spec)
- **Test Coverage Score:** 0/5

See `composer.json` for supported PHP versions.

---

## 12. Roadmap & Future Ideas

- Add support for other Git hosting services (GitLab, Bitbucket)
- Support for multiple changelog formats (JSON, YAML)
- Enhanced filtering and categorization of issues/pull requests
- Integration with project management tools
- Automated changelog generation from commit messages
- Support for release templates
- Better error messages and validation
- Add test coverage

---

## 13. References

- [Keep a Changelog](https://keepachangelog.com/) — Changelog format specification
- [Semantic Versioning](https://semver.org/) — Version numbering specification
- [Atlas Package](https://github.com/decodelabs/atlas) — Filesystem operations
- [Systemic Package](https://github.com/decodelabs/systemic) — Process management
- [Chorus Package Index](../../../chorus/config/packages.json) — Ecosystem metadata

