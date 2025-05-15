<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog\Block;

use DecodeLabs\Chronicle\ChangeLog\Block;

interface Unreleased extends Block
{
    public function extractAsNotes(): ?string;
}
