<?php

declare(strict_types=1);

/*
 * This file is part of the Composer File Copier Plugin.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/composer-file-copier-plugin
 */

namespace Markocupic\Composer\Plugin\Library;

class CopyConfig
{
    public const OVERRIDE = 'OVERRIDE';

    public const DELETE = 'DELETE';

    public const COPY_OPTIONS = [
        self::OVERRIDE,
        self::DELETE,
    ];

    private bool $override = false;

    private bool $delete = false;

    public function shouldOverride(): bool
    {
        return $this->override;
    }

    public function shouldDelete(): bool
    {
        return $this->delete;
    }

    public function withOverride(bool $override): self
    {
        $clone = clone $this;
        $clone->override = $override;

        return $clone;
    }

    public function withDelete(bool $delete): self
    {
        $clone = clone $this;
        $clone->delete = $delete;

        return $clone;
    }
}
