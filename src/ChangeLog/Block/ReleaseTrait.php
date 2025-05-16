<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog\Block;

use Carbon\Carbon;

trait ReleaseTrait
{
    public string $version;

    public ?Carbon $date = null {
        get => $this->date;
        set(
            string|Carbon|null $value
        ) {
            if(is_string($value)) {
                $value = Carbon::parse($value);
            }

            $this->date = $value;
        }
    }

    public ?string $commitsUrl = null;
    public ?string $compareUrl = null;
}
