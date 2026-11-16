<?php

/**
 * Chronicle
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog;

class Options
{
    public function __construct(
        public protected(set) bool $issueAssignees = true,
        public protected(set) bool $pullRequestAssignees = true,
    ) {
    }
}
