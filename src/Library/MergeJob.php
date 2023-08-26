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

namespace Markocupic\Composer\Plugin\Library;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\BasePackage;
use Markocupic\Composer\Plugin\Library\FileMergers\FileMergerInterface;
use Markocupic\Composer\Plugin\Library\FileMergers\JsonFileMerger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * Merge job responsible for merging two files.
 *
 * Assumes that info on source and target path already gathered.
 * Will usually be instantiated from a copyJob.
 */
class MergeJob
{
    /**
     * List of methods that can be used for merging.
     */
    public const MERGE_METHODS = [
        'replace',
        'preserve',
        'none'
    ];

    /**
     * Map of extension -> merge function.
     */
    public const MERGER_EXTENSION_MAP = [
        JsonFileMerger::class => ['json']
    ];

    /**
     * Extension of origin file to be copied.
     */
    protected string $originExtension;

    /**
     * Extension of target file already present.
     */
    protected string $targetExtension;

    public function __construct(
        protected readonly string $originPath,
        protected readonly string $targetPath,
        protected ?string $mergeOption
    ) {
        $this->originExtension = Path::getExtension($this->originPath);
        $this->targetExtension = Path::getExtension($this->targetPath);
    }

    /**
     * Perform a merge action depending on file extension.
     */
    public function mergeResource(Filesystem $filesystem): void
    {
        try {
            $fileMerger = $this->getFileMergerByExtension($this->originExtension);
            // Extension should have already been validated but check just in case.
            if (!$fileMerger) {
                throw new \Exception('Invalid file type supplied');
            }

            $fileMerger->mergeFiles($filesystem, $this->originPath, $this->targetPath, $this->mergeOption);

        } catch (\Exception $exception) {
            throw new \Exception(sprintf('Unable to merge file %s to file %s due to exception %s', $this->originPath, $this->targetPath, $exception->getMessage()));
        }
    }

    /**
     * Check if files should be merged.
     */
    public function shouldMerge(Filesystem $fileSystem): bool
    {
        return $this->mergeOption
            && ($this->mergeOption !== 'none')
            && $fileSystem->exists($this->targetPath);
    }

    /**
     * Checks that both origin and target match the supported extension.
     */
    public function checkSupportedExtension(): bool
    {
        $supportedExtensions = $this->getSupportedExtensions();
        if (!in_array($this->originExtension, $supportedExtensions)) {
            throw new \Exception('The origin file ' . $this->originPath . ' cannot be merged due to unsupported extension');
        }

        if (!in_array($this->targetExtension, $supportedExtensions)) {
            throw new \Exception('The target file ' . $this->targetPath . ' cannot be merged due to unsupported extension');
        }

        return true;
    }

    /**
     * Check list of supported extensions.
     */
    public function getSupportedExtensions(): array
    {
        return array_merge(...array_values(self::MERGER_EXTENSION_MAP));
    }

    /**
     * Gets the file merger class to process the supplied filed type.
     */
    public function getFileMergerByExtension(string $fileExtension): ?FileMergerInterface
    {
        foreach (self::MERGER_EXTENSION_MAP as $fileMerger => $extensions)
        {
            if (in_array($fileExtension, $extensions)) {
                return new $fileMerger();
            }
        }

        return null;
    }
}
