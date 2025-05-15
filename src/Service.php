<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle;

use DateTimeInterface;
use DecodeLabs\Chronicle\ChangeLog\Block\Issue;
use DecodeLabs\Chronicle\ChangeLog\Block\PullRequest;

interface Service
{
    public function parseName(
        string $url
    ): string;

    public function getReleaseCommitsUrl(
        string $name,
        string $version,
    ): string;

    public function getReleaseCompareUrl(
        string $name,
        string $versionA,
        string $versionB
    ): string;

    /**
     * @return array<PullRequest>
     */
    public function loadPullRequests(
        string $name,
        string $version,
        DateTimeInterface $from,
        DateTimeInterface $to
    ): array;

    /**
     * @return array<Issue>
     */
    public function loadIssues(
        string $name,
        string $version,
        DateTimeInterface $from,
        DateTimeInterface $to
    ): array;
}
