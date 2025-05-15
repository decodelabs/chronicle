<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog;

use Composer\Semver\Comparator;
use DecodeLabs\Chronicle\ChangeLog\Block\Preamble;
use DecodeLabs\Chronicle\ChangeLog\Block\Release;
use DecodeLabs\Chronicle\ChangeLog\Block\Unreleased;
use DecodeLabs\Chronicle\ChangeLog\Renderer;
use DecodeLabs\Chronicle\ChangeLog\Renderer\Generic as GenericRenderer;
use Stringable;

class Document implements Stringable
{
    public ?Preamble $preamble = null;
    public ?Unreleased $unreleased = null;

    /**
     * @var array<string,Release>
     */
    protected(set) array $releases = [];

    public function addRelease(
        Release $release
    ): void {
        $this->releases[$release->version] = $release;
        $this->sortReleases();
    }

    /**
     * @param array<Release> $releases
     */
    public function addReleases(
        array $releases
    ): void {
        foreach ($releases as $release) {
            $this->releases[$release->version] = $release;
        }

        $this->sortReleases();
    }

    public function render(
        ?Renderer $renderer = null
    ): string {
        $output = '';
        $renderer ??= new GenericRenderer();

        if ($this->preamble) {
            $output .= $this->preamble->render($renderer) . "\n\n";
        }

        if ($this->unreleased) {
            $output .= $this->unreleased->render($renderer) . "\n\n";
        }

        foreach ($this->releases as $release) {
            $output .= $release->render($renderer) . "\n\n";
        }

        return trim($output);
    }

    private function sortReleases(): void
    {
        usort($this->releases, function($a, $b) {
            return Comparator::greaterThan(
                $a->version,
                $b->version
            ) ? -1 : 1;
        });
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
