<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog\Block;

use Carbon\Carbon;
use DecodeLabs\Chronicle\ChangeLog\Block;

interface Release extends Block
{
    public string $version { get; }
    public ?Carbon $date { get; }

    public ?string $commitsUrl { get; }
    public ?string $comparisonUrl { get; }
}
