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

use Composer\Composer;
use Composer\InstalledVersions;
use Composer\IO\IOInterface;
use Composer\Package\BasePackage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

class CopyJob
{
    /**
     * The absolute and canonicalized path to the source located inside the package
     * install path.
     */
    protected string|null $strOriginAbsolute;

    /**
     * The absolute path to the destination.
     */
    protected string $strTargetAbsolute;

    public function __construct(
        protected readonly string $strOrigin,
        protected readonly string $strTarget,
        protected readonly CopyConfig $copyConfig,
        protected readonly FilterConfig $filterConfig,
        protected readonly BasePackage $package,
        protected readonly Composer $composer,
        protected readonly IOInterface $io,
    ) {
        $this->strOriginAbsolute = $this->getAbsolutePathForSource($strOrigin, $this->package->getName());
        $this->strTargetAbsolute = $this->getAbsolutePathForTarget($strTarget, $this->getRootDir());
    }

    public function copyResource(): void
    {
        $filesystem = new Filesystem();

        if (null === $this->strOriginAbsolute || false === realpath($this->strOriginAbsolute)) {
            $this->io->write(\sprintf('<error>Could not find the absolute path for the source "%s" inside the package "%s". Maybe it does not exist or the package "%s" is not installed!</error>', $this->strOrigin, $this->package->getName(), $this->package->getName()));

            return;
        }

        if (!$filesystem->isAbsolutePath($this->strTargetAbsolute)) {
            $this->io->write(\sprintf('<error> Target "%s" is not an absolute path. Copy process aborted.</error>', $this->strTargetAbsolute));

            return;
        }

        if (is_file($this->strOriginAbsolute)) {
            try {
                $filesystem->copy($this->strOriginAbsolute, $this->strTargetAbsolute, $this->copyConfig->shouldDelete());

                $this->io->write(
                    \sprintf(
                        'Added the <comment>%s</comment> file.',
                        $this->strTargetAbsolute,
                    ),
                );
            } catch (\Exception $e) {
                $this->io->write(\sprintf('<error>Copy process aborted with error "%s".</error>', $e->getMessage()));
            }
        } elseif (is_dir($this->strOriginAbsolute)) {
            if (empty($this->filterConfig->getName()) && empty($this->filterConfig->getNotName()) && empty($this->filterConfig->getDepth())) {
                // If no filter is set, use Filesystem::mirror().
                try {
                    $options = ['override' => $this->copyConfig->shouldOverride()];
                    $filesystem->mirror($this->strOriginAbsolute, $this->strTargetAbsolute, null, $options);

                    $this->io->write(
                        \sprintf(
                            'Added the <comment>%s</comment> folder %s.',
                            $this->strTargetAbsolute,
                            !empty($this->arrFlags) ? ' ['.implode(', ', $this->arrFlags).']' : '',
                        ),
                    );
                } catch (\Exception $e) {
                    $this->io->write(\sprintf('<error>Copy process aborted with error "%s".</error>', $e->getMessage()));
                }
            } else {
                $finder = new Finder();

                $finder->in($this->strOriginAbsolute);
                $finder->files();

                if (!empty($this->filterConfig->getDepth())) {
                    $finder->depth($this->filterConfig->getDepth());
                }

                if (!empty($this->filterConfig->getName())) {
                    $finder->name($this->filterConfig->getName());
                }

                if (!empty($this->filterConfig->getNotName())) {
                    $finder->notName($this->filterConfig->getNotName());
                }

                $results = [];

                // check if there are any search results
                if ($finder->hasResults()) {
                    foreach ($finder as $file) {
                        $results[$file->getRealPath()] = $file->getRelativePathname();
                    }
                }

                foreach ($results as $absolutePath => $relativePath) {
                    try {
                        $targetPathAbsolute = Path::join($this->strTargetAbsolute, $relativePath);
                        $filesystem->copy($absolutePath, $targetPathAbsolute, $this->copyConfig->shouldOverride());

                        $this->io->write(
                            \sprintf(
                                'Added the <comment>%s</comment> file.',
                                $targetPathAbsolute,
                            ),
                        );
                    } catch (\Exception $e) {
                        $this->io->write(\sprintf('<error>Copy process aborted with error "%s".</error>', $e->getMessage()));
                    }
                }
            }
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
     * Returns the canonicalized absolute path of the source e.g.
     * /home/customer_x/public_html/domain.ch/vendor/code4nix/super-package/data/foo.bar.
     */
    protected static function getAbsolutePathForSource(string $originPath, string $packageName): string|null
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
     * Returns the canonicalized absolute path of the source e.g.
     * /home/customer_x/public_html/domain.ch/vendor/code4nix/super-package/data/foo.bar.
     */
    protected static function getAbsolutePathForTarget(string $targetPath, string $rootDir): string
    {
        if (!Path::isAbsolute($targetPath)) {
            $targetPath = Path::makeAbsolute($targetPath, $rootDir);
        }

        return $targetPath;
    }
}
