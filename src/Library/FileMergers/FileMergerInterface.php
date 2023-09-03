<?php

declare(strict_types=1);

/*
 * This file is part of Composer File Copier Plugin.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/composer-file-copier-plugin
 */

namespace Markocupic\Composer\Plugin\Library\FileMergers;

use Symfony\Component\Filesystem\Filesystem;

interface FileMergerInterface
{
    /**
     * Merges two files using the specific logic provided by the file merger.
     */
    public function mergeFile(Filesystem $filesystem, string $originPath, string $targetPath, string $mergeOption): void;
}
