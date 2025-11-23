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

    /**
     * If true, target files newer than origin files are overwritten.
     */
    public function shouldOverride(): bool
    {
        return $this->override;
    }

    /**
     * Whether to delete files that are not in the source directory (defaults to false).
     */
    public function shouldDelete(): bool
    {
        return $this->delete;
    }

    /**
     * If true, target files newer than origin files are overwritten.
     */
    public function withOverride(bool $doOverride): self
    {
        $clone = clone $this;
        $clone->override = $doOverride;

        return $clone;
    }

    /**
     * If true, target files that are not in the source directory will be overridden
     * (defaults to false).
     */
    public function withDelete(bool $doDelete): self
    {
        $clone = clone $this;
        $clone->delete = $doDelete;

        return $clone;
    }
}
