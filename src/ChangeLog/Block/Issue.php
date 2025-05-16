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

class Issue implements Block
{
    use BlockTrait;

    public string $title;
    public int $number;
    public string $url;
    public ?string $username = null;

    public ?Carbon $closeDate = null {
        get => $this->closeDate;
        set(
            string|Carbon|null $value
        ) {
            if (is_string($value)) {
                $value = Carbon::parse($value);
            }

            $this->closeDate = $value;
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
        string|Carbon|null $closeDate = null,
        array $labels = []
    ) {
        $this->title = $title;
        $this->number = $number;
        $this->url = $url;
        $this->username = $username;
        $this->closeDate = $closeDate;
        $this->labels = $labels;
    }

    public function render(
        Renderer $renderer,
    ): string {
        return $renderer->renderIssue($this);
    }
}
