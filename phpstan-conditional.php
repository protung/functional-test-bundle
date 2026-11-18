<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// PersistentCollectionExporter needs PHPUnit 13.3 (sebastian/exporter 8.2), which the lowest dependencies don't have.
if (method_exists(TestCase::class, 'registerObjectExporter')) {
    return [];
}

return [
    'parameters' => [
        'excludePaths' => [
            'analyse' => [
                __DIR__ . '/src/Exporter/PersistentCollectionExporter.php',
                __DIR__ . '/tests/Exporter/PersistentCollectionExporterTest.php',
            ],
        ],
    ],
];
