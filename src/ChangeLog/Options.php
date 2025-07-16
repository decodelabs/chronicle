<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog;

class Options
{
    public function __construct(
        protected(set) bool $issueAssignees = true,
        protected(set) bool $pullRequestAssignees = true,
    ) {
    }
}
