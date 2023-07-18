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
use Symfony\Component\Filesystem\Filesystem;

class CopyJob
{
    public const OVERRIDE = 'OVERRIDE';
    public const DELETE = 'DELETE';

    protected array $options = [
        'override' => false,
        'delete' => false,
    ];

    protected bool $override = false;
    protected bool $delete = false;

    public function __construct(
        protected string $strOrigin,
        protected string $strTarget,
        protected readonly array $arrFlags,
        protected readonly BasePackage $package,
        protected readonly Composer $composer,
        protected readonly IOInterface $io,
    ) {
        $this->strOrigin = $this->getRootDir().\DIRECTORY_SEPARATOR.$this->getPackageDistUrl().\DIRECTORY_SEPARATOR.$this->strOrigin;
        $this->strTarget = $this->getRootDir().\DIRECTORY_SEPARATOR.$this->strTarget;

        // Set options from flags
        foreach ($this->arrFlags as $flag) {
            if (isset($this->options[strtolower($flag)])) {
                $this->options[strtolower($flag)] = true;
            }
        }
    }

    public function copyResource(): void
    {
        $filesystem = new Filesystem();

        if (!$filesystem->isAbsolutePath($this->strOrigin)) {
            $this->io->write(sprintf('<error> Origin "%s" is not an absolute path. Copy process aborted.</error>', $this->strOrigin));

            return;
        }

        if (!$filesystem->isAbsolutePath($this->strTarget)) {
            $this->io->write(sprintf('<error> Target "%s" is not an absolute path. Copy process aborted.</error>', $this->strTarget));

            return;
        }

        if (!$this->sourceExists($this->strOrigin)) {
            $this->io->write(sprintf('<error>Copy Folder from %s to %s aborted. Source does not exist.</error>', $this->strOrigin, $this->strTarget));

            return;
        }

        if (is_file($this->strOrigin)) {
            try {
                $filesystem->copy($this->strOrigin, $this->strTarget, $this->options['override']);

                $this->io->write(
                    sprintf(
                        'Added the <comment>%s</comment> file.',
                        $this->strTarget,
                    ),
                );
            } catch (\Exception $e) {
                $this->io->write(sprintf('<error>Copy process aborted with error "%s".</error>', $e->getMessage()));
            }
        } elseif (is_dir($this->strOrigin)) {
            try {
                $filesystem->mirror($this->strOrigin, $this->strTarget, null, $this->options);

                $this->io->write(
                    sprintf(
                        'Added the <comment>%s</comment> folder %s.',
                        $this->strTarget,
                        !empty($this->arrFlags) ? ' ['.implode(', ', $this->arrFlags).']' : '',
                    ),
                );
            } catch (\Exception $e) {
                $this->io->write(sprintf('<error>Copy process aborted with error "%s".</error>', $e->getMessage()));
            }
        }
    }

    protected function sourceExists(string $strPath): bool
    {
        return false !== realpath($strPath);
    }

    /**
     * Get the project dir.
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

    protected function getPackageDistUrl(): string
    {
        return $this->package->getDistUrl();
    }
}
