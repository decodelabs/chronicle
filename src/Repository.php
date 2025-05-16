<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle;

use DecodeLabs\Atlas\Dir;
use DecodeLabs\Chronicle\ChangeLog\Document;
use DecodeLabs\Chronicle\Service\GitHub as GitHubService;
use DecodeLabs\Exceptional;
use DecodeLabs\Monarch;

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

            $ini = parse_ini_file($this->path . '/.git/config', true);

            if(!isset($ini['remote origin']['url'])) {
                $this->originUrl = '';
                return null;
            }

            return $this->originUrl = $ini['remote origin']['url'];
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
}
