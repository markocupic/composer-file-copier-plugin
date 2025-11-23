<?php

declare(strict_types=1);

/*
 * This file is part of the Composer File Copier Plugin.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/composer-file-copier-plugin
 */

namespace Markocupic\Composer\Plugin\Library;

use Composer\IO\IOInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class FileCopyTask
{
    public function __construct(
        private readonly string $packageName,
        private readonly string $packageInstallPath,
        private readonly string $rootDir,
        private readonly IOInterface $io,
    ) {
    }

    public function copyResource(string $pathOrigin, string $pathTarget, CopyConfig $copyConfig, FilterConfig $filterConfig): void
    {
        $pathOriginAbsolute = $this->buildAbsolutePathForSource($pathOrigin, $this->packageInstallPath);
        $pathTargetAbsolute = $this->buildAbsolutePathForTarget($pathTarget, $this->rootDir);

        $filesystem = new Filesystem();

        if (null === $pathOriginAbsolute || false === realpath($pathOriginAbsolute)) {
            $this->io->write(\sprintf('<error>Could not find the absolute path for the source "%s" inside the package "%s". Maybe it does not exist or the package "%s" is not installed!</error>', $pathOrigin, $this->packageName, $this->packageName));

            return;
        }

        if (!$filesystem->isAbsolutePath($pathTargetAbsolute)) {
            $this->io->write(\sprintf('<error> Target "%s" is not an absolute path. Copy process aborted.</error>', $pathTargetAbsolute));

            return;
        }

        $fileCopier = new FileCopier($this->io);

        // Copy file.
        if (is_file($pathOriginAbsolute)) {
            $fileCopier->copyFile($pathOriginAbsolute, $pathTargetAbsolute, $copyConfig);

            return;
        }

        // Copy directory.
        if (is_dir($pathOriginAbsolute)) {
            if (empty($filterConfig->getName()) && empty($filterConfig->getNotName()) && empty($filterConfig->getDepth())) {
                // Use Filesystem::mirror() if no filter is set.
                $fileCopier->mirrorDirectory($pathOriginAbsolute, $pathTargetAbsolute, $copyConfig);

                return;
            }

            // Filesystem::copy() if filters are set.
            $fileCopier->copyDirectory($pathOriginAbsolute, $pathTargetAbsolute, $copyConfig, $filterConfig);
        }
    }

    /**
     * Returns the canonicalized absolute path of the source e.g.
     */
    private function buildAbsolutePathForSource(string $originPath, string $packageInstallPath): string|null
    {
        if (Path::isAbsolute($originPath)) {
            return $originPath;
        }

        return Path::makeAbsolute($originPath, $packageInstallPath);
    }

    /**
     * Returns the canonicalized absolute path of the source e.g.
     */
    private function buildAbsolutePathForTarget(string $targetPath, string $rootDir): string
    {
        if (!Path::isAbsolute($targetPath)) {
            $targetPath = Path::makeAbsolute($targetPath, $rootDir);
        }

        return $targetPath;
    }
}
