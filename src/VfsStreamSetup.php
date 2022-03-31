<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle;

use LogicException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

final class VfsStreamSetup
{
    /** @var vfsStreamDirectory|null $root */
    private static $root = null;

    private function __construct()
    {
        self::$root = vfsStream::setup();
    }

    public static function initialize(): void
    {
        new self();
    }

    public static function getRoot(): vfsStreamDirectory
    {
        if (self::$root === null) {
            throw new LogicException(
                \sprintf(
                    'vsf stream was never initialized. Make sure you call %s::initialize() in your PHPUnit bootstrap',
                    __CLASS__
                )
            );
        }

        return self::$root;
    }
}
