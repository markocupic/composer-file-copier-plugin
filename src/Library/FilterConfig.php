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

class FilterConfig
{
    public const NAME = 'NAME';

    public const NOT_NAME = 'NOT_NAME';

    public const DEPTH = 'DEPTH';

    public const FILTERS = [
        self::NOT_NAME,
        self::NAME,
        self::DEPTH,
    ];

    private array $name = [];

    private array $notName = [];

    private array $depth = [];

    public function getName(): array
    {
        return $this->name;
    }

    public function getNotName(): array
    {
        return $this->notName;
    }

    public function getDepth(): array
    {
        return $this->depth;
    }

    public function withName(array $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    public function withNotName(array $notName): self
    {
        $clone = clone $this;
        $clone->notName = $notName;

        return $clone;
    }

    public function withDepth(array $depth): self
    {
        $clone = clone $this;
        $clone->depth = $depth;

        return $clone;
    }
}
