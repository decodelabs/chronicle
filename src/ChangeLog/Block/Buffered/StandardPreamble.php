<?php

/**
 * @package Chronicle
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Chronicle\ChangeLog\Block\Buffered;

use DecodeLabs\Chronicle\ChangeLog\Block\Preamble;
use DecodeLabs\Chronicle\ChangeLog\BlockTrait;
use DecodeLabs\Chronicle\ChangeLog\Renderer;

class StandardPreamble implements Preamble
{
    use BlockTrait;

    private const string Body = <<<EOD
        # Changelog

        All notable changes to this project will be documented in this file.<br>
        The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
        and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
        EOD;

    public function render(
        Renderer $renderer,
    ): string {
        return self::Body;
    }
}
