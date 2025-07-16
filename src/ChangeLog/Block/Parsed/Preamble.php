<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog\Block\Parsed;

use DecodeLabs\Chronicle\ChangeLog\Block\Parsed;
use DecodeLabs\Chronicle\ChangeLog\Block\Preamble as PreambleInterface;
use DecodeLabs\Chronicle\ChangeLog\BlockTrait;
use DecodeLabs\Chronicle\ChangeLog\Renderer;

class Preamble implements Parsed, PreambleInterface
{
    use BlockTrait;

    /**
     * @var array<string>
     */
    public array $body = [];

    public function consolidate(
        bool $rewrite
    ): void {
        $this->body = $this->trim($this->body);

        if ($rewrite) {
            $this->body = $this->starToDash($this->body);
        }
    }

    public function render(
        Renderer $renderer,
    ): string {
        return implode("\n", $this->body);
    }
}
