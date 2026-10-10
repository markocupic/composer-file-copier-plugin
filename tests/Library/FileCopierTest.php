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

use Composer\IO\BufferIO;
use Markocupic\Composer\Plugin\Library\CopyConfig;
use Markocupic\Composer\Plugin\Library\FileCopier;
use Markocupic\Composer\Plugin\Library\FilterConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class FileCopierTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/file-copier-test-'.bin2hex(random_bytes(4));

        $fs = new Filesystem();
        $fs->dumpFile($this->dir.'/source/foo.txt', 'foo');
        $fs->dumpFile($this->dir.'/source/style.css', 'css');
        $fs->dumpFile($this->dir.'/source/sub/bar.txt', 'bar');
        $fs->dumpFile($this->dir.'/source/sub/deep/baz.css', 'baz');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);

        parent::tearDown();
    }

    public function testCopiesAFile(): void
    {
        $io = new BufferIO();

        (new FileCopier($io))->copyFile($this->dir.'/source/foo.txt', $this->dir.'/target/foo.txt', new CopyConfig());

        $this->assertFileExists($this->dir.'/target/foo.txt');
        $this->assertStringContainsString('Added the', $io->getOutput());
    }

    public function testDoesNotOverrideNewerFilesUnlessConfigured(): void
    {
        $fs = new Filesystem();
        $fs->dumpFile($this->dir.'/target/foo.txt', 'newer');
        touch($this->dir.'/target/foo.txt', time() + 3600);

        $copier = new FileCopier(new BufferIO());

        $copier->copyFile($this->dir.'/source/foo.txt', $this->dir.'/target/foo.txt', new CopyConfig());
        $this->assertSame('newer', file_get_contents($this->dir.'/target/foo.txt'));

        $copier->copyFile($this->dir.'/source/foo.txt', $this->dir.'/target/foo.txt', (new CopyConfig())->withOverride(true));
        $this->assertSame('foo', file_get_contents($this->dir.'/target/foo.txt'));
    }

    public function testMirrorsADirectory(): void
    {
        (new FileCopier(new BufferIO()))->mirrorDirectory($this->dir.'/source', $this->dir.'/target', new CopyConfig());

        $this->assertFileExists($this->dir.'/target/foo.txt');
        $this->assertFileExists($this->dir.'/target/sub/deep/baz.css');
    }

    public function testDeletesObsoleteFilesWhenMirroringIfConfigured(): void
    {
        $fs = new Filesystem();
        $fs->dumpFile($this->dir.'/target/obsolete.txt', 'obsolete');

        $copier = new FileCopier(new BufferIO());

        $copier->mirrorDirectory($this->dir.'/source', $this->dir.'/target', new CopyConfig());
        $this->assertFileExists($this->dir.'/target/obsolete.txt');

        $copier->mirrorDirectory($this->dir.'/source', $this->dir.'/target', (new CopyConfig())->withDelete(true));
        $this->assertFileDoesNotExist($this->dir.'/target/obsolete.txt');
        $this->assertFileExists($this->dir.'/target/foo.txt');
        $this->assertFileExists($this->dir.'/target/sub/deep/baz.css');
    }

    public function testCopiesAFilteredDirectory(): void
    {
        $filter = (new FilterConfig())->withName(['*.css'])->withDepth(['< 2']);

        (new FileCopier(new BufferIO()))->copyDirectory($this->dir.'/source', $this->dir.'/target', new CopyConfig(), $filter);

        $this->assertFileExists($this->dir.'/target/style.css');
        $this->assertFileDoesNotExist($this->dir.'/target/foo.txt');
        $this->assertFileDoesNotExist($this->dir.'/target/sub/bar.txt');
        $this->assertFileDoesNotExist($this->dir.'/target/sub/deep/baz.css');
    }

    public function testReportsAMissingSourceFile(): void
    {
        $io = new BufferIO();

        (new FileCopier($io))->copyFile($this->dir.'/source/missing.txt', $this->dir.'/target/missing.txt', new CopyConfig());

        $this->assertFileDoesNotExist($this->dir.'/target/missing.txt');
        $this->assertStringContainsString('does not exist', $io->getOutput());
    }
}
