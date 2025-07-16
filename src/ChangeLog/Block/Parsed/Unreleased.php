<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog\Block\Parsed;

use DecodeLabs\Chronicle\ChangeLog\Block\Parsed;
use DecodeLabs\Chronicle\ChangeLog\Block\Unreleased as UnreleasedInterface;
use DecodeLabs\Chronicle\ChangeLog\BlockTrait;
use DecodeLabs\Chronicle\ChangeLog\Renderer;

class Unreleased implements Parsed, UnreleasedInterface
{
    use BlockTrait;

    public ?string $header = null;

    /**
     * @var array<string>
     */
    public array $body = [];

    public function consolidate(
        bool $rewrite
    ): void {
        $this->body = $this->trim($this->body);

        if ($rewrite) {
            $this->header = null;
            $this->body = $this->starToDash($this->body);
        }
    }

    public function extractAsNotes(): ?string
    {
        if (empty($this->body)) {
            return null;
        }

        $output = implode("\n", $this->body);
        $output = trim($output);

        if ($output === '--') {
            $output = null;
        }

        $this->body = ['--'];
        return $output;
    }

    public function render(
        Renderer $renderer,
    ): string {
        $output = '';

        if ($this->header !== null) {
            $output .= $this->header . "\n";
        } else {
            $output .= $renderer->renderUnreleasedHeader($this) . "\n";
        }

        if (!empty($this->body)) {
            $output .= implode("\n", $this->body);
        }

        return rtrim($output);
    }
}
