<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

final class VfsStreamSetup
{
    private static vfsStreamDirectory|null $root = null;

    private function __construct()
    {
    }

    /**
     * Replaces the root directory with an empty one.
     */
    public static function initialize(): void
    {
        self::$root = vfsStream::setup();
    }

    /**
     * Gets the root directory, which is created on first use.
     */
    public static function getRoot(): vfsStreamDirectory
    {
        return self::$root ??= vfsStream::setup();
    }
}
