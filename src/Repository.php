<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle;

use DecodeLabs\Atlas\Dir;
use DecodeLabs\Chronicle\ChangeLog\Document;
use DecodeLabs\Chronicle\ChangeLog\Block\Buffered\NextRelease;
use DecodeLabs\Chronicle\ChangeLog\Renderer;
use DecodeLabs\Chronicle\ChangeLog\Renderer\Generic as GenericRenderer;
use DecodeLabs\Chronicle\Service\GitHub as GitHubService;
use DecodeLabs\Exceptional;
use DecodeLabs\Monarch;
use DecodeLabs\Systemic;
use z4kn4fein\SemVer\Version as SemVerVersion;

class Repository
{
    protected(set) string $path;

    public ?string $originUrl {
        get {
            if(
                isset($this->originUrl) &&
                $this->originUrl !== ''
            ) {
                return $this->originUrl;
            }

            if(isset($this->originUrl)) {
                return null;
            }

            if(!is_file($this->path . '/.git/config')) {
                throw Exceptional::Runtime(
                    'No .git/config file found in ' . $this->path
                );
            }

            $config = $this->loadGitConfig();

            if(!isset($config['remote origin']['url'])) {
                $this->originUrl = '';
                return null;
            }

            return $this->originUrl = $config['remote origin']['url'];
        }
    }

    public ?string $name {
        get => $this->name ??= $this->service?->parseName(
            (string)$this->originUrl
        );
    }

    public ?Service $service {
        get {
            if(isset($this->service)) {
                return $this->service;
            }

            if(null === ($originUrl = $this->originUrl)) {
                return null;
            }

            if(str_contains($originUrl, 'github.com')) {
                return $this->service = new GitHubService();
            }

            return null;
        }
    }

    /**
     * @var array<string,string|array<string,string>>
     */
    private ?array $gitConfig = null;

    public function __construct(
        string|Dir|null $path = null,
    ) {
        $this->path = (string)($path ?? Monarch::$paths->root);
    }

    public function parseChangeLog(
        ?string $fileName = null,
        bool $rewrite = false,
    ): Document {
        $fileName = $fileName ?? 'CHANGELOG.md';
        $path = $this->path . '/' . $fileName;

        if(
            !$rewrite &&
            !is_file($path)
        ) {
            $rewrite = true;
        }

        $parser = new ChangeLog\Parser(
            path: $path,
            rewrite: $rewrite
        );

        return $parser->parse($this);
    }

    /**
     * @return array<string,string|array<string,string>>
     */
    public function loadGitConfig(): array
    {
        if ($this->gitConfig !== null) {
            return $this->gitConfig;
        }

        if(false === ($output = parse_ini_file($this->path . '/.git/config', true))) {
            throw Exceptional::Runtime(
                'Failed to load git config'
            );
        }

        return $this->gitConfig = $output;
    }

    /**
     * @return array<string,string|array<string,string>>
     */
    public function reloadGitConfig(): array
    {
        $this->gitConfig = null;
        return $this->loadGitConfig();
    }



    public function hasUncommittedChanges(): bool
    {
        $result = $this->askGit('status', '--porcelain');

        if($result === null) {
            throw Exceptional::Runtime(
                'Failed to check for uncommitted changes'
            );
        }

        return trim($result) !== '';
    }



    /**
     * @return array<string,bool>
     */
    public function getBranches(): array
    {
        $output = [];

        if(null === ($result = $this->askGit('branch', '--list'))) {
            throw Exceptional::Runtime(
                'Failed to retrieve git branches'
            );
        }

        foreach(explode("\n", $result) as $line) {
            $line = trim($line);

            if($line === '') {
                continue;
            }

            $active = false;

            if(str_starts_with($line, '*')) {
                $line = substr($line, 1);
                $active = true;
            }

            $output[trim($line)] = $active;
        }

        return $output;
    }

    public function hasBranch(
        string $branch
    ): bool {
        $list = $this->askGit('branch', '--list', $branch);
        return trim(trim((string)$list, '*')) === $branch;
    }


    /**
     * @return array<string>
     */
    public function getTags(): array
    {
        $output = [];

        if(null === ($result = $this->askGit('tag', '--list'))) {
            throw Exceptional::Runtime(
                'Failed to retrieve git tags'
            );
        }

        foreach(explode("\n", $result) as $line) {
            $line = trim($line);

            if($line === '') {
                continue;
            }

            $output[] = $line;
        }

        return $output;
    }



    public function publishNextRelease(
        NextRelease $release,
        ?Renderer $renderer = null,
    ): bool {
        if(
            !$this->service ||
            !$this->name
        ) {
            return false;
        }

        $semVer = SemverVersion::parse(ltrim($release->version, 'v'));
        $preRelease = $semVer->isLessThan(SemverVersion::parse('1.0.0')) || $semVer->isPreRelease();

        return $this->service->publishNextRelease(
            name: $this->name,
            version: $release->version,
            body: ($renderer ?? new GenericRenderer())->renderNextRelease(
                $release,
                withHeader: false
            ),
            preRelease: $preRelease
        );
    }


    private function askGit(
        string $name,
        string ...$args
    ): ?string {
        $result = Systemic::capture(
            ['git', $name, ...$args],
            $this->path
        );

        if (!$result->wasSuccessful()) {
            return null;
        }

        return (string)$result->getOutput();
    }
}
