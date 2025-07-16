<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog;

use DecodeLabs\Chronicle\ChangeLog\Block\Parsed\Preamble;
use DecodeLabs\Chronicle\ChangeLog\Block\Parsed\Release;
use DecodeLabs\Chronicle\ChangeLog\Block\Parsed\Unreleased;
use DecodeLabs\Chronicle\Repository;
use DecodeLabs\Exceptional;

class Parser
{
    public protected(set) string $path;
    public protected(set) bool $rewrite = false;

    public function __construct(
        string $path,
        bool $rewrite = false
    ) {
        if (!str_ends_with($path, '.md')) {
            throw Exceptional::InvalidArgument(
                message: 'Changelog is not a markdown file',
                data: $path
            );
        }

        if (
            !is_file($path) &&
            !$rewrite
        ) {
            throw Exceptional::NotFound(
                message: 'Changelog file not found',
                data: $path
            );
        }

        $this->path = $path;
        $this->rewrite = $rewrite;
    }

    public function parse(
        ?Repository $repository = null
    ): Document {
        $doc = new Document($this->path);
        $releases = [];

        foreach ($this->parseFile() as $block) {
            if ($block instanceof Preamble) {
                $block->consolidate($this->rewrite);
                $doc->preamble = $block;
                continue;
            }

            if ($block instanceof Unreleased) {
                $block->consolidate($this->rewrite);
                $doc->unreleased = $block;
                continue;
            }

            if ($block instanceof Release) {
                $releases[] = $block;
                continue;
            }
        }


        $previousVersion = null;
        $service = $repository?->service;

        foreach (array_reverse($releases) as $release) {
            if (
                $this->rewrite &&
                $repository
            ) {
                if ($release->commitsUrl === null) {
                    $release->commitsUrl = $service?->getReleaseCommitsUrl(
                        (string)$repository->name,
                        $release->version
                    );
                }

                if (
                    $release->compareUrl === null &&
                    $previousVersion !== null
                ) {
                    $release->compareUrl = $service?->getReleaseCompareUrl(
                        (string)$repository->name,
                        $previousVersion,
                        $release->version,
                    );
                }

                $previousVersion = $release->version;
            }

            $release->consolidate($this->rewrite);
        }

        $doc->addReleases($releases);
        return $doc;
    }

    /**
     * @return iterable<Block>
     */
    private function parseFile(): iterable
    {
        if (!is_file($this->path)) {
            return;
        }

        $lines = file($this->path, FILE_IGNORE_NEW_LINES);
        $block = null;
        $preambled = false;
        $unreleased = false;

        while (!empty($lines)) {
            $line = array_shift($lines);

            if (
                (!$unreleased && ($newBlock = $this->parseUnreleased($line))) ||
                (!$preambled && ($newBlock = $this->parsePreamble($line))) ||
                ($newBlock = $this->parseReleaseHeader($line))
            ) {
                $preambled = true;

                if (
                    $newBlock instanceof Unreleased ||
                    $newBlock instanceof Release
                ) {
                    $unreleased = true;
                }

                if ($block !== null) {
                    yield $block;
                }

                $block = $newBlock;
                continue;
            }

            if (!$block) {
                $block = new Preamble();
            }

            $block->body[] = $line;
        }

        if ($block) {
            yield $block;
        }
    }

    private function parsePreamble(
        string $line,
    ): ?Preamble {
        if (
            !preg_match('/^[#]{1,6}/', $line) ||
            preg_match('/(v?[0-9]+\.[0-9]+\.[0-9]+)/', $line)
        ) {
            return null;
        }

        $output = new Preamble();
        $output->body[] = $line;
        return $output;
    }

    private function parseUnreleased(
        string $line,
    ): ?Unreleased {
        if (!preg_match('/^[#]{1,6} \[?Unreleased\]?$/', $line)) {
            return null;
        }

        $output = new Unreleased();
        $output->header = $line;
        return $output;
    }

    private function parseReleaseHeader(
        string $line
    ): ?Release {
        if (!preg_match('/^[#]{1,6}.*[^v.](v?[0-9]+\.[0-9]+\.[0-9]+)([^.]|$)/', $line, $matches)) {
            return null;
        }

        $output = new Release();
        $output->header = $line;
        $output->version = $matches[1];

        if (preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})/', $line, $matches) === 1) {
            $output->date = $matches[1];
        } elseif (preg_match('/([0-9]{1,2})(st|nd|rd|th)? ([A-Za-z]+) ([0-9]{4})/', $line, $matches) === 1) {
            $output->date = $matches[1] . ' ' . $matches[3] . ' ' . $matches[4];
        }

        if (preg_match('|https://github\.com/[^/]+/[^/]+/compare/[v0-9.]+|', $line, $matches)) {
            $output->compareUrl = $matches[0];
        } elseif (preg_match('|https://github\.com/[^/]+/[^/]+/commits/[v0-9.]+|', $line, $matches)) {
            $output->commitsUrl = $matches[0];
        }

        return $output;
    }
}
