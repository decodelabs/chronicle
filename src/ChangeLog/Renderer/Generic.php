<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog\Renderer;

use DecodeLabs\Chronicle\ChangeLog\Block\Buffered\StandardPreamble;
use DecodeLabs\Chronicle\ChangeLog\Block\Release;
use DecodeLabs\Chronicle\ChangeLog\Block\Unreleased;
use DecodeLabs\Chronicle\ChangeLog\Renderer;

class Generic implements Renderer
{
    public function generatePreamble(): StandardPreamble
    {
        return new StandardPreamble();
    }

    public function renderUnreleasedHeader(
        Unreleased $unreleased
    ): string {
        return '## Unreleased';
    }

    public function renderReleaseHeader(
        Release $release
    ): string {
        $output = '## ';

        $url = $release->commitsUrl ?? $release->comparisonUrl;

        if($url !== null) {
            $output .= '[' . $release->version . '](' . $url . ')';
        } else {
            $output .= $release->version;
        }

        if($release->date) {
            $output .= ' - ' . $release->date->format('jS F Y');
        }

        return $output;
    }
}
