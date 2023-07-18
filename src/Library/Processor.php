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

class Processor
{
    public const FLAGS = [
        CopyJob::OVERRIDE,
        CopyJob::DELETE,
    ];

    public function __construct(
        protected readonly BasePackage $package,
        protected readonly Composer $composer,
        protected readonly IOInterface $io,
    ) {
    }

    public function copyResources(): void
    {
        if ($this->supports()) {
            $arrSources = $this->getFileCopierSourcesFromExtra();

            if (!empty($arrSources)) {
                $this->io->write('');
                $this->io->write('<info>markocupic/composer-file-copier-plugin:</info>');
                $this->io->write(
                    sprintf(
                        'Package: <comment>%s</comment>',
                        $this->package->getName(),
                    ),
                );

                foreach ($arrSources as $origin => $target) {
                    if (\is_string($origin) && \is_string($target)) {
                        $arrTarget = explode('|', $target);
                        $target = $arrTarget[0];

                        // Clean array and get the appended flags
                        $arrFlags = array_filter(array_unique(array_map('strtoupper', \array_slice($arrTarget, 1))));

                        if (!empty($arrFlags)) {
                            if (!$this->checkFlags($arrFlags)) {
                                $this->io->write(
                                    sprintf(
                                        '<warning>The composer.json file of %s contains an invalid "extra.files-copier.source" flag. Do only use one of these: %s.<warning>',
                                        $this->package->getName(),
                                        implode(', ', self::FLAGS),
                                    ),
                                );
                                continue;
                            }
                        } else {
                            $arrFlags = [];
                        }

                        $copyJob = new CopyJob($origin, $target, $arrFlags, $this->package, $this->composer, $this->io);
                        $copyJob->copyResource();
                    } else {
                        $this->io->write(
                            sprintf(
                                '<warning>The composer.json file of %s contains an invalid "extra.files-copier.source" value.<warning>',
                                $this->package->getName(),
                            ),
                        );
                    }
                }

                $this->io->write('');
            }
        }
    }

    protected function supports(): bool
    {
        $exclude = ['library', 'project', 'metapackage', 'composer-plugin'];

        if (\in_array(strtolower($this->package->getType()), $exclude, true)) {
            return false;
        }

        return !empty($this->getFileCopierSourcesFromExtra());
    }

    protected function getRootDir(): string
    {
        return \dirname($this->composer->getConfig()->get('vendor-dir'));
    }

    protected function getFileCopierSourcesFromExtra(): array
    {
        $extra = $this->package->getExtra();

        if (!empty($extra) && !empty($extra['composer-file-copier-plugin']['sources'])) {
            return $extra['composer-file-copier-plugin']['sources'];
        }

        return [];
    }

    protected function checkFlags(array $arrFlags): bool
    {
        return \count(array_intersect($arrFlags, self::FLAGS)) === \count($arrFlags);
    }
}
