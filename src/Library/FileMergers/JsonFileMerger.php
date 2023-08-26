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

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\BasePackage;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Merge job responsible for merging two files.
 *
 * Assumes that info on source and target path already gathered.
 * Will usually be instantiated from a copyJob.
 */
class JsonFileMerger implements FileMergerInterface
{
    /**
     * @inheritDoc.
     */
    public function mergeFiles(Filesystem $filesystem, string $originPath, string $targetPath, string $mergeOption): void
    {
        $originContentArr = $this->readJson($originPath);
        $targetContentArr = $this->readJson($targetPath);

        // Merge parsed content by either replacing existing keys or preserving.
        $mergedResult = $mergeOption === 'replace' ?
            array_merge($targetContentArr, $originContentArr)
            : array_merge($originContentArr, $targetContentArr);

        // Write Json file
        $filesystem->dumpFile($targetPath, json_encode($mergedResult,JSON_PRETTY_PRINT));
    }

    /**
     * Reads json from a file path and returns decoded content.
     */
    protected function readJson(string $filePath): array
    {
        $contentArr = json_decode(\file_get_contents($filePath), true);

        // Any JSON parsing errors should throw an exception.
        if (json_last_error() > 0) {
            throw new \Exception(sprintf('Error processing file: %s . Error: %s', $filePath, \json_last_error_msg()));
        }

        return $contentArr;
    }
}
