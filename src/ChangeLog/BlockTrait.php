<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog;

/**
 * @phpstan-require-implements Block
 */
trait BlockTrait
{
    /**
     * @param array<string> $lines
     * @return array<string>
     */
    protected static function trim(
        array $lines,
    ): array {
        while (count($lines) && trim($lines[0]) === '') {
            array_shift($lines);
        }

        while (
            count($lines) &&
            match(trim($lines[count($lines) - 1])) {
                '',
                '---' => true,
                default => false
            }
        ) {
            array_pop($lines);
        }

        return $lines;
    }

    /**
     * @param array<string> $lines
     * @return array<string>
     */
    protected function starToDash(
        array $lines,
    ): array {
        foreach($lines as $key => $line) {
            if(str_starts_with($line, '* ')) {
                $lines[$key] = '- ' . substr($line, 2);
            }
        }

        return $lines;
    }
}
