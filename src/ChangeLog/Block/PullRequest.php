<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog\Block;

use Carbon\Carbon;
use DecodeLabs\Chronicle\ChangeLog\Block;
use DecodeLabs\Chronicle\ChangeLog\BlockTrait;
use DecodeLabs\Chronicle\ChangeLog\Renderer;

class PullRequest implements Block
{
    use BlockTrait;

    public string $title;
    public int $number;
    public string $url;
    public ?string $username = null;

    public ?Carbon $mergeDate = null {
        get => $this->mergeDate;
        set(
            string|Carbon|null $value
        ) {
            if (is_string($value)) {
                $value = Carbon::parse($value);
            }

            $this->mergeDate = $value;
        }
    }

    /**
     * @var array<string>
     */
    public array $labels = [];

    /**
     * @param array<string> $labels
     */
    public function __construct(
        string $title,
        int $number,
        string $url,
        ?string $username = null,
        string|Carbon|null $mergeDate = null,
        array $labels = []
    ) {
        $this->title = $title;
        $this->number = $number;
        $this->url = $url;
        $this->username = $username;
        $this->mergeDate = $mergeDate;
        $this->labels = $labels;
    }

    public function render(
        Renderer $renderer,
    ): string {
        return $renderer->renderPullRequest($this);
    }
}
