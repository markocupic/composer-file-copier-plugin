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
use Composer\InstalledVersions;
use Composer\IO\IOInterface;
use Composer\Package\BasePackage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

class CopyJob
{
    public const OVERRIDE = 'OVERRIDE';
    public const DELETE = 'DELETE';
    public const MERGE = 'MERGE';
    public const NAME = 'NAME';
    public const NOT_NAME = 'NOT_NAME';
    public const DEPTH = 'DEPTH';

    protected array $options = [
        'override' => false,
        'delete' => false,
        'merge' => 'none',
    ];

    protected array $filter = [
        'name' => [],
        'notName' => [],
        'depth' => [],
    ];

    /**
     * The absolute and canonicalized path to the source located inside the package install path.
     */
    protected string|null $strOriginAbsolute;

    /**
     * The absolute path to the destination.
     */
    protected string $strTargetAbsolute;

    public function __construct(
        protected readonly string $strOrigin,
        protected readonly string $strTarget,
        array $arrOptions,
        array $arrFilter,
        protected readonly BasePackage $package,
        protected readonly Composer $composer,
        protected readonly IOInterface $io,
    ) {
        $this->strOriginAbsolute = $this->getAbsolutePathForSource($strOrigin, $this->package->getName());
        $this->strTargetAbsolute = $this->getAbsolutePathForTarget($strTarget, $this->getRootDir());

        // Set $this->options from $arrOptions
        $this->options['override'] = isset($arrOptions[self::OVERRIDE]) && \is_bool($arrOptions[self::OVERRIDE]) && $arrOptions[self::OVERRIDE];
        $this->options['merge'] = isset($arrOptions[self::MERGE]) && \is_string($arrOptions[self::MERGE]) ? $arrOptions[self::MERGE] : $this->options['merge'];
        $this->options['delete'] = isset($arrOptions[self::DELETE]) && \is_bool($arrOptions[self::DELETE]) && $arrOptions[self::DELETE];

        // Set $this->filter from $arrFilter
        $this->filter['name'] = !empty($arrFilter[self::NAME]) && \is_array($arrFilter[self::NAME]) ? $arrFilter[self::NAME] : [];
        $this->filter['notName'] = !empty($arrFilter[self::NOT_NAME]) && \is_array($arrFilter[self::NOT_NAME]) ? $arrFilter[self::NOT_NAME] : [];
        $this->filter['depth'] = !empty($arrFilter[self::DEPTH]) && \is_array($arrFilter[self::DEPTH]) ? $arrFilter[self::DEPTH] : [];
    }

    public function copyResource(): void
    {
        $filesystem = new Filesystem();

        if (null === $this->strOriginAbsolute || false === realpath($this->strOriginAbsolute)) {
            $this->io->write(sprintf('<error>Could not find the absolute path for the source "%s" inside the package "%s". Maybe it does not exist or the package "%s" is not installed!</error>', $this->strOrigin, $this->package->getName(), $this->package->getName()));

            return;
        }

        if (!$filesystem->isAbsolutePath($this->strTargetAbsolute)) {
            $this->io->write(sprintf('<error> Target "%s" is not an absolute path. Copy process aborted.</error>', $this->strTargetAbsolute));

            return;
        }

        if (is_file($this->strOriginAbsolute)) {
            // Only copy file if should not get merged.
            if (!$this->performMergeJob($filesystem, $this->strOriginAbsolute, $this->strTargetAbsolute)) {
                $this->copyFile($filesystem, $this->strOriginAbsolute, $this->strTargetAbsolute);
            }
        } elseif (is_dir($this->strOriginAbsolute)) {
            if (empty($this->filter['name']) && empty($this->filter['notName']) && empty($this->filter['depth'])) {
                // If no filter is set use Filesystem::mirror().
                try {
                    $filesystem->mirror($this->strOriginAbsolute, $this->strTargetAbsolute, null, $this->options);

                    $this->io->write(
                        sprintf(
                            'Added the <comment>%s</comment> folder %s.',
                            $this->strTargetAbsolute,
                            !empty($this->arrFlags) ? ' ['.implode(', ', $this->arrFlags).']' : '',
                        ),
                    );
                } catch (\Exception $e) {
                    $this->io->write(sprintf('<error>Copy process aborted with error "%s".</error>', $e->getMessage()));
                }
            } else {
                // Disable the delete option

                $this->options['delete'] = false;
                $finder = new Finder();

                $finder->in($this->strOriginAbsolute);
                $finder->files();

                if (!empty($this->filter['depth'])) {
                    $finder->depth($this->filter['depth']);
                }

                if (!empty($this->filter['name'])) {
                    $finder->name($this->filter['name']);
                }

                if (!empty($this->filter['notName'])) {
                    $finder->notName($this->filter['notName']);
                }

                $results = [];

                // check if there are any search results
                if ($finder->hasResults()) {
                    foreach ($finder as $file) {
                        $results[$file->getRealPath()] = $file->getRelativePathname();
                    }
                }

                foreach ($results as $absolutePath => $relativePath) {
                    $targetPathAbsolute = $this->strTargetAbsolute.\DIRECTORY_SEPARATOR.$relativePath;
                    // Only copy file if should not get merged.
                    if (!$this->performMergeJob($filesystem, $absolutePath, $targetPathAbsolute)) {
                        $this->copyFile($filesystem, $absolutePath, $this->strTargetAbsolute);
                    }
                }
            }
        }
    }

    /**
     * Perform the actual file copy with option to override if exists.
     */
    protected function copyFile(Filesystem $filesystem, string $originPath, string $targetPath)
    {
        try {
            $filesystem->copy($originPath, $targetPath, $this->options['override']);

            $this->io->write(
                sprintf(
                    'Added the <comment>%s</comment> file.',
                    $targetPath,
                ),
            );
        } catch (\Exception $e) {
            $this->io->write(sprintf('<error>Copy process aborted with error "%s" for source file "%s".</error>', $e->getMessage(), $originPath));
        }
    }

    /**
     * Performs a merge job if conditions are met.
     */
    protected function performMergeJob(Filesystem $filesystem, string $originPath, string $targetPath): bool
    {
        try {
            $mergeJob = new MergeJob($originPath, $targetPath, $this->options['merge']);
            if ($mergeJob->shouldMerge($filesystem) && $mergeJob->checkSupportedExtension()) {
                $mergeJob->mergeResource($filesystem);

                $this->io->write(
                    sprintf(
                        'Merged the <comment>%s</comment> file.',
                        $targetPath,
                    ),
                );

                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->io->write(sprintf('<error>File merging process aborted with error "%s" for source file "%s".</error>', $e->getMessage(), $originPath));
        }
    }

    /**
     * Get the root dir from vendor-dir.
     *
     * @throws \Exception
     */
    protected function getRootDir(): string
    {
        $rootDir = realpath(\dirname($this->composer->getConfig()->get('vendor-dir')));

        if (false === $rootDir) {
            throw new \Exception('Could not determine the root dir.');
        }

        return $rootDir;
    }

    /**
     * Returns the canonicalized absolute path of the source
     * e.g. /home/customer_x/public_html/domain.ch/vendor/code4nix/super-package/data/foo.bar.
     */
    protected function getAbsolutePathForSource(string $originPath, string $packageName): string|null
    {
        if (Path::isAbsolute($originPath)) {
            return $originPath;
        }

        // Get the installation path of the package that includes the source
        $packageInstallPath = realpath((string) InstalledVersions::getInstallPath($packageName));

        if (empty($packageInstallPath)) {
            return null;
        }

        return Path::makeAbsolute($originPath, $packageInstallPath);
    }

    /**
     * Returns the canonicalized absolute path of the source
     * e.g. /home/customer_x/public_html/domain.ch/vendor/code4nix/super-package/data/foo.bar.
     */
    protected function getAbsolutePathForTarget(string $targetPath, string $rootDir): string
    {
        if (!Path::isAbsolute($targetPath)) {
            $targetPath = Path::makeAbsolute($targetPath, $rootDir);
        }

        return $targetPath;
    }
}
