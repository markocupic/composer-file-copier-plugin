<?php

declare(strict_types=1);

use Contao\EasyCodingStandard\Set\SetList;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;

return ECSConfig::configure()
	->withSets([SetList::CONTAO])
	->withPaths([
		__DIR__ . '/../../src',
	])
	->withSkip([])
	->withRootFiles()
	->withParallel()
	->withSpacing(Option::INDENTATION_SPACES, "\n")
	->withConfiguredRule(HeaderCommentFixer::class, [
        'header' => "This file is part of Composer File Copier Plugin.\n\n(c) Marko Cupic <m.cupic@gmx.ch>\n@license MIT\nFor the full copyright and license information,\nplease view the LICENSE file that was distributed with this source code.\n@link https://github.com/markocupic/composer-file-copier-plugin",
	])
	->withCache(sys_get_temp_dir() . '/ecs/markocupic/composer-file-copier-plugin');
