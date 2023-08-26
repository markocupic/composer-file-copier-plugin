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

/**
 * File processor.
 */
class Processor
{
    public const COPY_OPTIONS = [
        CopyJob::OVERRIDE,
        CopyJob::MERGE,
        CopyJob::DELETE,
    ];

    public const FILTERS = [
        CopyJob::NOT_NAME,
        CopyJob::NAME,
        CopyJob::DEPTH,
    ];

    /**
     * List of excluded composer types to not process by default.
     *
     * Can be overridden in config by defining "composer-file-copier-excluded" under extra.
     */
    protected array $excludedTypes = [
        'library',
        'metapackage',
        'composer-plugin',
        'project',
    ];

    public function __construct(
        protected readonly BasePackage $package,
        protected readonly Composer $composer,
        protected readonly IOInterface $io,
    ) {
        $this->excludedTypes = $this->getExcludedComposerTypes();
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

                    $arrOptions = $arrSource['options'] ?? [];
                    $this->checkOptions($arrOptions);

                    $arrFilter = $arrSource['filter'] ?? [];
                    $this->checkFilters($arrFilter);

                    // Call the copy class
                    $copyJob = new CopyJob($origin, $target, $arrOptions, $arrFilter, $this->package, $this->composer, $this->io);
                    $copyJob->copyResource();
                }

                $this->io->write('');
            }
        }
    }

    protected function supports(): bool
    {
        if (\in_array(strtolower($this->package->getType()), $this->excludedTypes, true)) {
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

    /**
     * Retrieves the list of excluded composer package types if overridden in config.
     */
    protected function getExcludedComposerTypes(): array
    {
        $extra = $this->package->getExtra();

        if (!empty($extra) && !empty($extra['composer-file-copier-excluded'])) {
            if (!\is_array($extra['composer-file-copier-excluded'])) {
                throw new \InvalidArgumentException(sprintf('Found an invalid extra.composer-file-copier-excluded configuration inside composer.json of package "%s". The key must contain an array with a configuration object.', $this->package->getName()));
            }

            return $extra['composer-file-copier-excluded'];
        }

        return $this->excludedTypes;
    }

    protected function checkOptions(array $arrOptions): bool
    {
        if (isset($arrOptions[CopyJob::OVERRIDE])) {
            if (!\is_bool($arrOptions[CopyJob::OVERRIDE])) {
                throw new \InvalidArgumentException(sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The option.OVERRIDE must be of type boolean (true or false) %s given.', $this->package->getName(), \gettype($arrOptions[CopyJob::OVERRIDE])));
            }
        }

        if (isset($arrOptions[CopyJob::DELETE])) {
            if (!\is_bool($arrOptions[CopyJob::DELETE])) {
                throw new \InvalidArgumentException(sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The option.DELETE must be of type boolean (true or false) %s given.', $this->package->getName(), \gettype($arrOptions[CopyJob::DELETE])));
            }
        }

        if (isset($arrOptions[CopyJob::MERGE])) {
            if (!\is_string($arrOptions[CopyJob::MERGE])) {
                throw new \InvalidArgumentException(sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The option.MERGE must be of type string, %s given.', $this->package->getName(), \gettype($arrOptions[CopyJob::MERGE])));
            }
            if (!\in_array($arrOptions[CopyJob::MERGE], MergeJob::MERGE_METHODS)) {
                throw new \InvalidArgumentException(sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The option.MERGE must be a supported value: %s', $this->package->getName(), join(',', MergeJob::MERGE_METHODS)));
            }
        }

        foreach (array_keys($arrOptions) as $key) {
            if (!\in_array($key, self::COPY_OPTIONS, true)) {
                throw new \InvalidArgumentException(sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The option.%s is not allowed.', $this->package->getName(), $key));
            }
        }

        return true;
    }

    protected function checkFilters(array $arrFilter): bool
    {
        if (isset($arrFilter[CopyJob::NAME])) {
            if (!\is_array($arrFilter[CopyJob::NAME]) || empty($arrFilter[CopyJob::NAME])) {
                throw new \InvalidArgumentException(sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The filter.NAME entry must contain an array of strings.', $this->package->getName()));
            }
        }

        if (isset($arrFilter[CopyJob::NOT_NAME])) {
            if (!\is_array($arrFilter[CopyJob::NOT_NAME]) || empty($arrFilter[CopyJob::NOT_NAME])) {
                throw new \InvalidArgumentException(sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The filter.NOT_NAME entry must contain an array of strings.', $this->package->getName()));
            }
        }

        if (isset($arrFilter[CopyJob::DEPTH])) {
            if (!\is_array($arrFilter[CopyJob::DEPTH]) || empty($arrFilter[CopyJob::DEPTH])) {
                throw new \InvalidArgumentException(sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The filter.DEPTH entry must contain an array of strings.', $this->package->getName()));
            }
        }

        foreach (array_keys($arrFilter) as $key) {
            if (!\in_array($key, self::FILTERS, true)) {
                throw new \InvalidArgumentException(sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The filter.%s is not allowed.', $this->package->getName(), $key));
            }
        }

        return true;
    }
}
