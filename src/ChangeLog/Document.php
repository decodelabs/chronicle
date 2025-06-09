<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog;

use Carbon\Carbon;
use DecodeLabs\Atlas;
use DecodeLabs\Atlas\File;
use DecodeLabs\Chronicle\ChangeLog\Block\Buffered\NextRelease;
use DecodeLabs\Chronicle\ChangeLog\Block\Preamble;
use DecodeLabs\Chronicle\ChangeLog\Block\Release;
use DecodeLabs\Chronicle\ChangeLog\Block\Unreleased;
use DecodeLabs\Chronicle\ChangeLog\Renderer;
use DecodeLabs\Chronicle\ChangeLog\Renderer\Generic as GenericRenderer;
use DecodeLabs\Chronicle\Repository;
use DecodeLabs\Chronicle\VersionChange;
use DecodeLabs\Exceptional;
use Stringable;
use z4kn4fein\SemVer\Version as SemVerVersion;
use z4kn4fein\SemVer\SemverException;

class Document implements Stringable
{
    protected(set) string $path;

    public ?Preamble $preamble = null;
    public ?Unreleased $unreleased = null;

    /**
     * @var array<string,Release>
     */
    protected(set) array $releases = [];

    public function __construct(
        string $path
    ) {
        $this->path = $path;
    }

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

    public function getLastRelease(): ?Release
    {
        if (empty($this->releases)) {
            return null;
        }

        return $this->releases[array_key_first($this->releases)];
    }

    public function hasVersion(
        string $version
    ): bool {
        return isset($this->releases[$version]);
    }

    public function getLastVersion(): ?string
    {
        return $this->getLastRelease()?->version;
    }

    public function validateNextVersion(
        string|VersionChange $version
    ): string {
        if(is_string($version)) {
            try {
                $version = 'v' . SemverVersion::parse($version, false);
            } catch(SemverException $e) {
                $version = VersionChange::fromName($version);
            }
        }

        $lastRelease = $this->getLastRelease();

        if($version instanceof VersionChange) {
            if($lastRelease) {
                $lastVersion = SemverVersion::parse(
                    $lastRelease->version,
                    false
                );

                if($version === VersionChange::Breaking) {
                    if($lastVersion->isLessThan(SemverVersion::parse('1.0.0'))) {
                        $version = VersionChange::Minor;
                    } else {
                        $version = VersionChange::Major;
                    }
                } elseif($version === VersionChange::Feature) {
                    if($lastVersion->isLessThan(SemverVersion::parse('1.0.0'))) {
                        $version = VersionChange::Patch;
                    } else {
                        $version = VersionChange::Minor;
                    }
                }

                $version = 'v' . $lastVersion->inc($version->value);
            } else {
                $version = 'v0.1.0';
            }
        }

        if(isset($this->releases[$version])) {
            throw Exceptional::InvalidArgument(
                'Next version already exists in changelog'
            );
        }

        return $version;
    }

    public function generateNextRelease(
        string|VersionChange $version,
        ?Repository $repository = null,
        string|Carbon|null $date = null,
    ): void {
        $date ??= Carbon::now();

        if(is_string($date)) {
            $date = Carbon::parse($date);
        }

        $version = $this->validateNextVersion($version);
        $lastRelease = $this->getLastRelease();

        if($lastRelease) {
            $compareUrl = $repository?->service?->getReleaseCompareUrl(
                (string)$repository?->name,
                $lastRelease->version,
                $version
            );

            $lastDate = $repository?->getTagDate($lastRelease->version) ?? $lastRelease->date;
        } else {
            $compareUrl = null;
        }

        $nextRelease = new NextRelease(
            version: $version,
            date: $date,
            commitsUrl: $repository?->service?->getReleaseCommitsUrl(
                (string)$repository?->name,
                $version
            ),
            compareUrl: $compareUrl,
        );

        $nextRelease->notes = $this->unreleased?->extractAsNotes();

        if(
            !empty($this->releases) &&
            $lastRelease &&
            $repository &&
            $repository->service
        ) {
            if(!$lastDate) {
                throw Exceptional::Runtime(
                    'Last release date could not be parsed from changelog'
                );
            }

            $nextRelease->pullRequests = $repository->service->loadPullRequests(
                (string)$repository->name,
                $lastRelease->version,
                $lastDate,
                $date,
            );

            $nextRelease->issues = $repository->service->loadIssues(
                (string)$repository->name,
                $lastRelease->version,
                $lastDate,
                $date,
            );
        }


        $this->addRelease($nextRelease);
    }

    public function render(
        ?Renderer $renderer = null
    ): string {
        $output = '';
        $renderer ??= new GenericRenderer();

        if ($this->preamble) {
            $output .= $this->preamble->render($renderer) . "\n\n";
        } elseif($preamble = $renderer->generatePreamble()) {
            $output .= $preamble->render($renderer) . "\n\n";
        }

        if ($this->unreleased) {
            $output .= $this->unreleased->render($renderer) . "\n\n";
        }

        foreach ($this->releases as $release) {
            $output .= $release->render($renderer) . "\n\n";
        }

        return trim($output)."\n";
    }

    public function save(
        ?Renderer $renderer = null,
        ?string $path = null
    ): File {
        $output = $this->render($renderer);

        return Atlas::createFile(
            $path ?? $this->path,
            $output
        );
    }

    private function sortReleases(): void
    {
        usort($this->releases, function($a, $b) {
            return SemVerVersion::compareString(
                ltrim($b->version, 'v'),
                ltrim($a->version, 'v'),
            );
        });
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
