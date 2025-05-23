<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog\Block\Parsed;

use Carbon\Carbon;
use DecodeLabs\Chronicle\ChangeLog\Block\Parsed;
use DecodeLabs\Chronicle\ChangeLog\Block\Release as ReleaseInterface;
use DecodeLabs\Chronicle\ChangeLog\Block\ReleaseTrait;
use DecodeLabs\Chronicle\ChangeLog\BlockTrait;
use DecodeLabs\Chronicle\ChangeLog\Renderer;

class Release implements Parsed, ReleaseInterface
{
    use BlockTrait;
    use ReleaseTrait;


    public ?string $header;

    /**
     * @var array<string>
     */
    public array $body = [];

    public function consolidate(
        bool $rewrite
    ): void {
        $this->body = $this->trim($this->body);

        if($rewrite) {
            $this->header = null;

            if (preg_match('/^([#\>]+) (([0-9]{1,2})(st|nd|rd|th)? ([A-Za-z]+) ([0-9]{4}))$/', $this->body[0] ?? '', $matches)) {
                $this->date = Carbon::createFromFormat('jS F Y', $matches[2]);
                array_shift($this->body);
                $this->body = $this->trim($this->body);
            }

            $this->body = $this->starToDash($this->body);

            if($this->compareUrl !== null) {
                $inBody = false;

                foreach($this->body as $key => $line) {
                    if(str_contains($line, $this->compareUrl)) {
                        $inBody = true;
                    }
                }

                if(!$inBody) {
                    $this->body[] = "\n" . '[Full list of changes](' . $this->compareUrl . ')';
                }
            }
        }
    }

    public function render(
        Renderer $renderer
    ): string {
        return $renderer->renderParsedRelease($this);
    }
}

