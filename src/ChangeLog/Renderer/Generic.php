<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog\Renderer;

use DecodeLabs\Chronicle\ChangeLog\Block\Buffered\NextRelease;
use DecodeLabs\Chronicle\ChangeLog\Block\Buffered\StandardPreamble;
use DecodeLabs\Chronicle\ChangeLog\Block\Issue;
use DecodeLabs\Chronicle\ChangeLog\Block\Parsed\Release as ParsedRelease;
use DecodeLabs\Chronicle\ChangeLog\Block\PullRequest;
use DecodeLabs\Chronicle\ChangeLog\Block\Release;
use DecodeLabs\Chronicle\ChangeLog\Block\Unreleased;
use DecodeLabs\Chronicle\ChangeLog\Options;
use DecodeLabs\Chronicle\ChangeLog\Renderer;

class Generic implements Renderer
{
    public protected(set) Options $options;

    public function __construct(
        ?Options $options = null
    ) {
        $this->options = $options ?? new Options();
    }

    public function generatePreamble(): StandardPreamble
    {
        return new StandardPreamble();
    }

    public function renderUnreleasedHeader(
        Unreleased $unreleased
    ): string {
        return '### Unreleased';
    }

    public function renderReleaseHeader(
        Release $release
    ): string {
        $output = '### ';

        $url = $release->commitsUrl ?? $release->compareUrl;

        if ($url !== null) {
            $output .= '[' . $release->version . '](' . $url . ')';
        } else {
            $output .= $release->version;
        }

        if ($release->date) {
            $output .= ' - ' . $release->date->format('jS F Y');
        }

        return $output;
    }

    public function renderParsedRelease(
        ParsedRelease $release
    ): string {
        $output = "---\n\n";

        if ($release->header) {
            $output .= rtrim($release->header) . "\n\n";
        } else {
            $output .= $this->renderReleaseHeader($release) . "\n\n";
        }

        if (!empty($release->body)) {
            $output .= implode("\n", $release->body);
        }

        return rtrim($output);
    }

    public function renderNextRelease(
        NextRelease $release,
        bool $withHeader = true
    ): string {
        $output = '';

        if ($withHeader) {
            $output .= "---\n\n";
            $output .= $this->renderReleaseHeader($release) . "\n\n";
        }

        if (!empty($release->notes)) {
            $output .= $release->notes . "\n";
        }

        if (!empty($release->pullRequests)) {
            $output .= "\n";
            $output .= '#### Merged Pull Requests' . "\n";

            foreach ($release->pullRequests as $issue) {
                $output .= '- ' . $this->renderPullRequest($issue) . "\n";
            }
        }

        if (!empty($release->issues)) {
            $output .= "\n";
            $output .= '#### Closed Issues' . "\n";

            foreach ($release->issues as $issue) {
                $output .= '- ' . $this->renderIssue($issue) . "\n";
            }
        }

        if (
            $release->commitsUrl &&
            $release->compareUrl
        ) {
            $output .= "\n";
            $output .= '[Full list of changes](' . $release->compareUrl . ')';
        }

        return rtrim($output);
    }

    public function renderIssue(
        Issue $issue
    ): string {
        $output = $issue->title . ' ';
        $output .= '\[[' . $issue->number . '](' . $issue->url . ')\]';

        if (
            $this->options->issueAssignees &&
            $issue->username !== null
        ) {
            $output .= ' - @' . $issue->username;
        }

        return $output;
    }

    public function renderPullRequest(
        PullRequest $issue
    ): string {
        $output = $issue->title . ' ';
        $output .= '\[[' . $issue->number . '](' . $issue->url . ')\]';

        if (
            $this->options->pullRequestAssignees &&
            $issue->username !== null
        ) {
            $output .= ' - @' . $issue->username;
        }

        return $output;
    }
}
