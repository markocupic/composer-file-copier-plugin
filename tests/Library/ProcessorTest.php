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

namespace Markocupic\Composer\Plugin\Tests\Library;

use Composer\Composer;
use Composer\Config;
use Composer\Installer\InstallationManager;
use Composer\IO\BufferIO;
use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use Markocupic\Composer\Plugin\Library\Processor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ProcessorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/file-copier-processor-'.bin2hex(random_bytes(4));

        $fs = new Filesystem();
        $fs->dumpFile($this->root.'/vendor/acme/package/data/foo.txt', 'foo');
        $fs->dumpFile($this->root.'/vendor/acme/package/data/dir/a.css', 'a');
        $fs->dumpFile($this->root.'/vendor/acme/package/data/dir/b.txt', 'b');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->root);

        parent::tearDown();
    }

    public function testCopiesTheConfiguredResources(): void
    {
        $io = new BufferIO();

        $processor = new Processor($this->createPackage([
            ['source' => 'data/foo.txt', 'target' => 'public/foo.txt'],
            ['source' => 'data/dir', 'target' => 'files/dir', 'filter' => ['NAME' => ['*.css']]],
            ['source' => 'data/missing', 'target' => 'files/missing'],
        ]), $this->createComposer(), $io);

        $processor->process();

        $this->assertFileExists($this->root.'/public/foo.txt');
        $this->assertFileExists($this->root.'/files/dir/a.css');
        $this->assertFileDoesNotExist($this->root.'/files/dir/b.txt');
        $this->assertFileDoesNotExist($this->root.'/files/missing');
        $this->assertStringContainsString('Could not find the absolute path for the source "data/missing"', $io->getOutput());
    }

    public function testSkipsExcludedPackageTypes(): void
    {
        $processor = new Processor($this->createPackage([['source' => 'data/foo.txt', 'target' => 'public/foo.txt']], 'library'), $this->createComposer(), new BufferIO());
        $processor->process();

        $this->assertFileDoesNotExist($this->root.'/public/foo.txt');
    }

    public function testHandlesPackagesWithoutInstallPath(): void
    {
        $io = new BufferIO();

        $processor = new Processor($this->createPackage([['source' => 'data/foo.txt', 'target' => 'public/foo.txt']]), $this->createComposer(null), $io);
        $processor->process();

        $this->assertFileDoesNotExist($this->root.'/public/foo.txt');
        $this->assertStringContainsString('is not installed', $io->getOutput());
    }

    public function testRejectsInvalidOptions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The option.FOO is not allowed.');

        $processor = new Processor($this->createPackage([['source' => 'data/foo.txt', 'target' => 'public/foo.txt', 'options' => ['FOO' => true]]]), $this->createComposer(), new BufferIO());
        $processor->process();
    }

    public function testRejectsNonBooleanOptions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be of type boolean');

        $processor = new Processor($this->createPackage([['source' => 'data/foo.txt', 'target' => 'public/foo.txt', 'options' => ['OVERRIDE' => 'yes']]]), $this->createComposer(), new BufferIO());
        $processor->process();
    }

    public function testRejectsInvalidEntries(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $processor = new Processor($this->createPackage(['data/foo.txt']), $this->createComposer(), new BufferIO());
        $processor->process();
    }

    private function createPackage(array $config, string $type = 'contao-bundle'): CompletePackage
    {
        $package = new CompletePackage('acme/package', '1.0.0.0', '1.0.0');
        $package->setType($type);
        $package->setExtra(['composer-file-copier-plugin' => $config]);

        return $package;
    }

    private function createComposer(string|false|null $installPath = false): Composer
    {
        $config = new Config(false, $this->root);
        $config->merge(['config' => ['vendor-dir' => $this->root.'/vendor']]);

        $installationManager = $this->createStub(InstallationManager::class);
        $installationManager
            ->method('getInstallPath')
            ->willReturnCallback(fn (PackageInterface $package): string|null => false === $installPath ? $this->root.'/vendor/'.$package->getName() : $installPath)
        ;

        $composer = new Composer();
        $composer->setConfig($config);
        $composer->setInstallationManager($installationManager);

        return $composer;
    }
}
