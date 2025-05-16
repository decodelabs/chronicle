<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog\Block;

use DecodeLabs\Chronicle\ChangeLog\Block;

interface Parsed extends Block
{
    public function consolidate(
        bool $rewrite
    ): void;
}
