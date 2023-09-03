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

use Markocupic\Composer\Plugin\Library\MergeJob;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Handles the merging process.
 *
 * Assumes that info on source and target path already gathered.
 * Will normally be instantiated by a copyJob.
 */
class JsonFileMerger implements FileMergerInterface
{
    public const SUPPORTS_FILE_EXTENSION = 'json';

    public function mergeFile(Filesystem $filesystem, string $originPath, string $targetPath, string $mergeOption): void
    {
        if (!is_file($originPath)) {
            throw new \Exception(sprintf('Could not find file "%s".', $originPath));
        }

        if (!is_file($targetPath)) {
            throw new \Exception(sprintf('Could not find file "%s".', $targetPath));
        }

        $originContentArr = $this->readJson($originPath);
        $targetContentArr = $this->readJson($targetPath);

        // Merge parsed content by either replacing existing keys or preserving them.
        if (MergeJob::MERGE_METHOD_REPLACE === $mergeOption) {
            $mergedResult = array_merge($targetContentArr, $originContentArr);
        } else {
            $mergedResult = array_merge($originContentArr, $targetContentArr);
        }

        // Write json file
        $filesystem->dumpFile($targetPath, json_encode($mergedResult, JSON_PRETTY_PRINT));
    }

    /**
     * Reads json from a file path and returns decoded content.
     */
    protected function readJson(string $filePath): array
    {
        $contentArr = json_decode(file_get_contents($filePath), true);

        // Any json parsing errors should throw an exception.
        if (json_last_error() > 0) {
            throw new \Exception(sprintf('Error processing file: %s . Error: %s', $filePath, json_last_error_msg()));
        }

        return $contentArr;
    }
}
