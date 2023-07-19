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
    public const COPY_FLAGS = [
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

                foreach ($arrSources as $arrSource) {
                    if (empty($arrSource['source'])) {
                        throw new \InvalidArgumentException(sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The source key must contain a file or folder path.', $this->package->getName()));
                    }

                    $origin = $arrSource['source'];

                    if (empty($arrSource['target'])) {
                        throw new \InvalidArgumentException(sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The target key must contain a file or folder path.', $this->package->getName()));
                    }

                    $target = $arrSource['target'];

                    $arrOptions = !empty($arrSource['options']) ? explode(',', (string) $arrSource['options']) : [];
                    $arrOptions = array_filter(array_unique(array_map('strtoupper', $arrOptions)));

                    if (!empty($arrOptions)) {
                        if (!$this->checkOptions($arrOptions)) {
                            throw new \InvalidArgumentException(sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The options key may contain a comma separated string with one ore more of these values: "%s".', $this->package->getName(), implode(',', self::COPY_FLAGS)));
                        }
                    }

                    $copyJob = new CopyJob($origin, $target, $arrOptions, $this->package, $this->composer, $this->io);
                    $copyJob->copyResource();
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

        if (!empty($extra) && !empty($extra['composer-file-copier-plugin'])) {
            if (!\is_array($extra['composer-file-copier-plugin'])) {
                throw new \InvalidArgumentException(sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The key must contain an array with a configuration object.', $this->package->getName()));
            }

            return $extra['composer-file-copier-plugin'];
        }

        return [];
    }

    protected function checkOptions(array $arrCOPY_FLAGS): bool
    {
        return \count(array_intersect($arrCOPY_FLAGS, self::COPY_FLAGS)) === \count($arrCOPY_FLAGS);
    }
}
