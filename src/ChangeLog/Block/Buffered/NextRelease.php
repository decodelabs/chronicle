<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog\Block\Buffered;

use Carbon\Carbon;
use DecodeLabs\Chronicle\ChangeLog\Block\Issue;
use DecodeLabs\Chronicle\ChangeLog\Block\PullRequest;
use DecodeLabs\Chronicle\ChangeLog\Block\Release as ReleaseInterface;
use DecodeLabs\Chronicle\ChangeLog\Block\ReleaseTrait;
use DecodeLabs\Chronicle\ChangeLog\BlockTrait;
use DecodeLabs\Chronicle\ChangeLog\Renderer;

class NextRelease implements ReleaseInterface
{
    use BlockTrait;
    use ReleaseTrait;

    public ?string $notes = null;

    /**
     * @var array<PullRequest>
     */
    public array $pullRequests = [];

    /**
     * @var array<Issue>
     */
    public array $issues = [];

    public function __construct(
        string $version,
        string|Carbon|null $date = null,
        ?string $commitsUrl = null,
        ?string $compareUrl = null
    ) {
        $this->version = $version;
        $this->date = $date;
        $this->commitsUrl = $commitsUrl;
        $this->compareUrl = $compareUrl;
    }

    public function render(
        Renderer $renderer,
    ): string {
        return $renderer->renderNextRelease($this);
    }
}
