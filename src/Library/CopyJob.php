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

class CopyJob
{
    public const OVERRIDE = 'OVERRIDE';
    public const DELETE = 'DELETE';

    protected array $options = [
        'override' => false,
        'delete' => false,
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
        protected readonly BasePackage $package,
        protected readonly Composer $composer,
        protected readonly IOInterface $io,
    ) {
        $this->strOriginAbsolute = $this->getAbsolutePathForSource($strOrigin, $this->package->getName());
        $this->strTargetAbsolute = $this->getAbsolutePathForTarget($strTarget, $this->getRootDir());

        // Set $this->options from $arrOption
        foreach ($arrOptions as $option) {
            if (isset($this->options[strtolower($option)])) {
                $this->options[strtolower($option)] = true;
            }
        }
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
            try {
                $filesystem->copy($this->strOriginAbsolute, $this->strTargetAbsolute, $this->options['override']);

                $this->io->write(
                    sprintf(
                        'Added the <comment>%s</comment> file.',
                        $this->strTargetAbsolute,
                    ),
                );
            } catch (\Exception $e) {
                $this->io->write(sprintf('<error>Copy process aborted with error "%s".</error>', $e->getMessage()));
            }
        } elseif (is_dir($this->strOriginAbsolute)) {
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
     *
     * @throws \Exception
     */
    protected function getAbsolutePathForSource(string $originPath, string $packageName): string|null
    {
        $packageInstallPath = realpath((string) InstalledVersions::getInstallPath($packageName));

        if (empty($packageInstallPath)) {
            return null;
        }

        return $packageInstallPath.\DIRECTORY_SEPARATOR.$originPath;
    }

    protected function getAbsolutePathForTarget(string $targetPath, string $rootDir): string
    {
        return $rootDir.\DIRECTORY_SEPARATOR.$targetPath;
    }
}
