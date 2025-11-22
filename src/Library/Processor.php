<?php

declare(strict_types=1);

/*
 * This file is part of the Composer File Copier Plugin.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
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
        if (!$this->supports()) {
            return;
        }

        $arrSources = $this->getFileCopierSourcesFromExtra();

        if (empty($arrSources)) {
            return;
        }

        $this->io->write('');
        $this->io->write('<info>markocupic/composer-file-copier-plugin:</info>');
        $this->io->write(
            \sprintf(
                'Package: <comment>%s</comment>',
                $this->package->getName(),
            ),
        );

        foreach ($arrSources as $arrSource) {
            if (empty($arrSource['source'])) {
                throw new \InvalidArgumentException(\sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The source key must contain a file or folder path.', $this->package->getName()));
            }

            $origin = $arrSource['source'];

            if (empty($arrSource['target'])) {
                throw new \InvalidArgumentException(\sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The target key must contain a file or folder path.', $this->package->getName()));
            }

            $target = $arrSource['target'];

            // Validate copy options
            $arrOptions = $arrSource['options'] ?? [];
            $this->validateCopyOptions($arrOptions);

            // Validate filter options
            $arrFilter = $arrSource['filter'] ?? [];
            $this->validateFilterOptions($arrFilter);

            // Build the copy config
            $copyConfig = new CopyConfig();

            if (!empty($arrOptions[CopyConfig::OVERRIDE]) && true === $arrOptions[CopyConfig::OVERRIDE]) {
                $copyConfig = $copyConfig->withOverride(true);
            }

            if (!empty($arrOptions[CopyConfig::DELETE]) && true === $arrOptions[CopyConfig::DELETE]) {
                $copyConfig = $copyConfig->withDelete(true);
            }

            // Build the filter config
            $filterConfig = new FilterConfig();

            if (!empty($arrFilter[FilterConfig::NAME])) {
                $filterConfig = $filterConfig->withName($arrFilter[FilterConfig::NAME]);
            }

            if (!empty($arrFilter[FilterConfig::NOT_NAME])) {
                $filterConfig = $filterConfig->withNotName($arrFilter[FilterConfig::NOT_NAME]);
            }

            if (!empty($arrFilter[FilterConfig::DEPTH])) {
                $filterConfig = $filterConfig->withDepth($arrFilter[FilterConfig::DEPTH]);
            }

            // Run the copy job
            $copyJob = new CopyJob($origin, $target, $copyConfig, $filterConfig, $this->package, $this->composer, $this->io);
            $copyJob->copyResource();
        }

        $this->io->write('');
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
                throw new \InvalidArgumentException(\sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The key must contain an array with a configuration object.', $this->package->getName()));
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
                throw new \InvalidArgumentException(\sprintf('Found an invalid extra.composer-file-copier-excluded configuration inside composer.json of package "%s". The key must contain an array with a configuration object.', $this->package->getName()));
            }

            return $extra['composer-file-copier-excluded'];
        }

        return $this->excludedTypes;
    }

    protected function validateCopyOptions(array $options): bool
    {
        $packageName = $this->package->getName();

        $this->validateBooleanOption($options, CopyConfig::OVERRIDE, $packageName);
        $this->validateBooleanOption($options, CopyConfig::DELETE, $packageName);

        // Validate keys
        foreach (array_keys($options) as $key) {
            if (!\in_array($key, CopyConfig::COPY_OPTIONS, true)) {
                throw new \InvalidArgumentException(\sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The option.%s is not allowed.', $packageName, $key));
            }
        }

        return true;
    }

    protected function validateFilterOptions(array $arrFilter): bool
    {
        $arrOptions = [FilterConfig::NAME, FilterConfig::NOT_NAME, FilterConfig::DEPTH];

        foreach ($arrOptions as $option) {
            if (isset($arrFilter[$option])) {
                $this->validateFilterArrayOption($arrFilter[$option], $option);
            }
        }

        $this->validateAllowedFilterKeys($arrFilter);

        return true;
    }

    private function validateBooleanOption(array $options, string $optionKey, string $packageName): void
    {
        if (isset($options[$optionKey]) && !\is_bool($options[$optionKey])) {
            throw new \InvalidArgumentException(\sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The option.%s must be of type boolean (true or false) %s given.', $packageName, $optionKey, \gettype($options[$optionKey])));
        }
    }

    private function validateAllowedFilterKeys(array $arrFilter): void
    {
        foreach (array_keys($arrFilter) as $key) {
            if (!\in_array($key, FilterConfig::FILTERS, true)) {
                throw new \InvalidArgumentException(\sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The filter.%s is not allowed.', $this->package->getName(), $key));
            }
        }
    }

    private function validateFilterArrayOption($value, string $optionName): void
    {
        if (!\is_array($value) || empty($value)) {
            throw new \InvalidArgumentException(\sprintf('Found an invalid extra.composer-file-copier-plugin configuration inside composer.json of package "%s". The filter.%s entry must contain an array of strings.', $this->package->getName(), $optionName));
        }
    }
}
