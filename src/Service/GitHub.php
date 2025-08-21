<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\Service;

use Carbon\Carbon;
use DateTimeInterface;
use DecodeLabs\Chronicle\ChangeLog\Block\Issue;
use DecodeLabs\Chronicle\ChangeLog\Block\PullRequest;
use DecodeLabs\Chronicle\Service;
use DecodeLabs\Dovetail\Env;
use DecodeLabs\Exceptional;
use DecodeLabs\Stash;
use Github\AuthMethod;
use Github\Client as ApiClient;
use Github\Exception\RuntimeException as GithubRuntimeException;

class GitHub implements Service
{
    protected ApiClient $client {
        get {
            if (isset($this->client)) {
                return $this->client;
            }

            $this->client = new ApiClient();

            $this->client->addCache(
                $this->stash->loadStealth(self::class)
            );

            if (null !== ($token = Env::tryString('GITHUB_TOKEN'))) {
                $this->client->authenticate(
                    $token,
                    null,
                    AuthMethod::ACCESS_TOKEN
                );
            }

            return $this->client;
        }
    }

    public function __construct(
        protected Stash $stash
    ) {
    }

    public function parseName(
        string $url
    ): string {
        if (str_starts_with($url, 'git@')) {
            $url = (string)preg_replace(
                '#^git@github\.com:(.*)\.git$#',
                'https://github.com/$1',
                $url
            );
        }

        $parts = parse_url($url);

        if (!isset($parts['path'])) {
            throw Exceptional::InvalidArgument(
                message: 'Invalid URL',
                data: $url
            );
        }

        $path = trim($parts['path'], '/');

        if (!preg_match('#^[a-zA-Z0-9_.-]+/[a-zA-Z0-9_.-]+$#', $path)) {
            throw Exceptional::InvalidArgument(
                message: 'Invalid URL',
                data: $url
            );
        }

        return $path;
    }


    public function getReleaseCommitsUrl(
        string $name,
        string $version,
    ): string {
        return 'https://github.com/' . $name . '/commits/' . $version;
    }

    public function getReleaseCompareUrl(
        string $name,
        string $versionA,
        string $versionB
    ): string {
        return 'https://github.com/' . $name . '/compare/' . $versionA . '...' . $versionB;
    }



    /**
     * @return array<PullRequest>
     */
    public function loadPullRequests(
        string $name,
        string $version,
        DateTimeInterface $from,
        DateTimeInterface $to
    ): array {
        [$vendor, $package] = explode('/', $name, 2);

        try {
            // @phpstan-ignore-next-line
            $pullRequests = $this->client->api('pull_request')->all($vendor, $package, [
                'state' => 'closed',
            ]);
        } catch (GithubRuntimeException $e) {
            return [];
        }

        $output = [];

        foreach ($pullRequests as $record) {
            if ($record['merged_at'] === null) {
                continue;
            }

            $date = Carbon::parse($record['merged_at']);

            if (
                $date->lt($from) ||
                $date->gt($to)
            ) {
                continue;
            }

            $output[] = new PullRequest(
                $record['title'],
                $record['number'],
                $record['html_url'],
                $record['merged_by']['login'] ?? null,
                $date,
                array_map(
                    static fn ($label) => $label['name'],
                    $record['labels']
                )
            );
        }

        usort($output, function (
            PullRequest $a,
            PullRequest $b
        ) {
            return $a->mergeDate?->format('YmdHis') <=> $b->mergeDate?->format('YmdHis');
        });

        return $output;
    }

    /**
     * @return array<Issue>
     */
    public function loadIssues(
        string $name,
        string $version,
        DateTimeInterface $from,
        DateTimeInterface $to
    ): array {
        [$vendor, $package] = explode('/', $name, 2);
        $from = Carbon::parse($from);
        $to = Carbon::parse($to);


        try {
            // @phpstan-ignore-next-line
            $issues = $this->client->api('issue')->all($vendor, $package, [
                'state' => 'closed',
                'since' => $from->format('c'),
                'until' => $to->format('c'),
            ]);
        } catch (GithubRuntimeException $e) {
            return [];
        }

        $output = [];

        foreach ($issues as $record) {
            if ($record['state_reason'] !== 'completed') {
                continue;
            }

            $issue = new Issue(
                $record['title'],
                $record['number'],
                $record['html_url'],
                $record['closed_by']['login'] ?? null,
                $record['closed_at'] ?? null,
                array_map(
                    static fn ($label) => $label['name'],
                    $record['labels']
                )
            );

            if (
                !$issue->closeDate ||
                $from->gt($issue->closeDate) ||
                $to->lt($issue->closeDate)
            ) {
                continue;
            }

            if (isset($record['type'])) {
                $issue->labels[] = lcfirst($record['type']['name']);
            }

            $output[] = $issue;
        }

        usort($output, function (
            Issue $a,
            Issue $b
        ) {
            return $a->closeDate?->format('YmdHis') <=> $b->closeDate?->format('YmdHis');
        });

        return $output;
    }


    public function publishNextRelease(
        string $name,
        string $version,
        string $body,
        bool $preRelease
    ): bool {
        [$vendor, $package] = explode('/', $name, 2);

        // @phpstan-ignore-next-line
        $this->client->api('repo')->releases()->create($vendor, $package, [
            'tag_name' => $version,
            'name' => $version,
            'body' => $body,
            'prerelease' => $preRelease
        ]);

        return true;
    }
}
