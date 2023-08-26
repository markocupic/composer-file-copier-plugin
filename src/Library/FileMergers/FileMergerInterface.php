<?php

namespace Markocupic\Composer\Plugin\Library\FileMergers;

use Symfony\Component\Filesystem\Filesystem;

interface FileMergerInterface
{
    /**
     * Merges two files following logic provided by type of file merger.
     */
    public function mergeFiles(Filesystem $filesystem, string $originPath, string $targetPath, string $mergeOption): void;
}
