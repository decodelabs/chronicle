<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle;

use DecodeLabs\Enumerable\Backed\NamedInt;
use DecodeLabs\Enumerable\Backed\NamedIntTrait;
use z4kn4fein\SemVer\Inc;

enum VersionChange: int implements NamedInt
{
    use NamedIntTrait;

    case Major = Inc::MAJOR;
    case Minor = Inc::MINOR;
    case Patch = Inc::PATCH;
    case PreRelease = Inc::PRE_RELEASE;

    case Breaking = 999;
    case Feature = 99;
}
