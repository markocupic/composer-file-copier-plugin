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

use Composer\IO\IOInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

class FileCopier
{
    public function __construct(
        private readonly IOInterface $io,
    ) {
    }

    public function copyFile(string $sourcePathAbsolute, string $targetPathAbsolute, CopyConfig $copyConfig): void
    {
        try {
            $filesystem = new Filesystem();
            $filesystem->copy($sourcePathAbsolute, $targetPathAbsolute, $copyConfig->shouldOverride());
            $this->io->write(
                \sprintf(
                    'Added the <comment>%s</comment> file.',
                    $targetPathAbsolute,
                ),
            );
        } catch (FileNotFoundException $e) {
            $this->io->write(\sprintf('<error>Origin File %s does not exist. Copy process aborted with error "%s".</error>', $sourcePathAbsolute, $e->getMessage()));
        } catch (\Exception $e) {
            $this->io->write(\sprintf('<error>Copy process aborted with error "%s".</error>', $e->getMessage()));
        }
    }

    public function mirrorDirectory(string $sourcePathAbsolute, string $targetPathAbsolute, CopyConfig $copyConfig): void
    {
        // If no filter is set, we use Filesystem::mirror().
        try {
            $filesystem = new Filesystem();
            $options = ['override' => $copyConfig->shouldOverride()];
            $filesystem->mirror($sourcePathAbsolute, $targetPathAbsolute, null, $options);
            $this->io->write(
                \sprintf(
                    'Added the <comment>%s</comment> folder.',
                    $targetPathAbsolute,
                ),
            );
        } catch (\Exception $e) {
            $this->io->write(\sprintf('<error>Copy process aborted with error "%s".</error>', $e->getMessage()));
        }
    }

    public function copyDirectory(string $sourcePathAbsolute, string $targetPathAbsolute, CopyConfig $copyConfig, FilterConfig $filterConfig): void
    {
        $results = $this->buildFileResultsArray($sourcePathAbsolute, $targetPathAbsolute, $filterConfig);
        $filesystem = new Filesystem();

        foreach ($results as $item) {
            try {
                $filesystem->copy($item['source'], $item['target'], $copyConfig->shouldOverride());
                $this->io->write(
                    \sprintf(
                        'Added the <comment>%s</comment> file.',
                        $item['target'],
                    ),
                );
            } catch (FileNotFoundException $e) {
                $this->io->write(\sprintf('<error>Origin File %s does not exist. Copy process aborted with error "%s".</error>', $item['source'], $e->getMessage()));
            } catch (\Exception $e) {
                $this->io->write(\sprintf('<error>Copy process aborted with error "%s".</error>', $e->getMessage()));
            }
        }
    }

    private function buildFileResultsArray(string $sourcePathAbsolute, string $targetPathAbsolute, FilterConfig $filterConfig): array
    {
        $finder = new Finder();
        $finder->in($sourcePathAbsolute);
        $finder->files();

        if (!empty($filterConfig->getDepth())) {
            $finder->depth($filterConfig->getDepth());
        }

        if (!empty($filterConfig->getName())) {
            $finder->name($filterConfig->getName());
        }

        if (!empty($filterConfig->getNotName())) {
            $finder->notName($filterConfig->getNotName());
        }

        $results = [];
        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $results[] = [
                    'source' => $file->getRealPath(),
                    'target' => Path::join($targetPathAbsolute, $file->getRelativePathname()),
                ];
            }
        }

        return $results;
    }
}
