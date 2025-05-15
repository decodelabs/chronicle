<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog;

use DecodeLabs\Chronicle\ChangeLog\Block\Buffered\NextRelease;
use DecodeLabs\Chronicle\ChangeLog\Block\Issue;
use DecodeLabs\Chronicle\ChangeLog\Block\Preamble;
use DecodeLabs\Chronicle\ChangeLog\Block\PullRequest;
use DecodeLabs\Chronicle\ChangeLog\Block\Release;
use DecodeLabs\Chronicle\ChangeLog\Block\Unreleased;

interface Renderer
{
    public function generatePreamble(): Preamble;

    public function renderUnreleasedHeader(
        Unreleased $unreleased
    ): string;

    public function renderReleaseHeader(
        Release $release
    ): string;

    public function renderNextRelease(
        NextRelease $release
    ): string;

    public function renderIssue(
        Issue $issue
    ): string;

    public function renderPullRequest(
        PullRequest $issue
    ): string;
}
