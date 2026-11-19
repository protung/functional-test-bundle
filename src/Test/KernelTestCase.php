<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as SymfonyKernelTestCase;

abstract class KernelTestCase extends SymfonyKernelTestCase
{
    use Concern\FunctionalTest;

    protected function setUp(): void
    {
        self::backupLocale();

        parent::setUp();

        static::bootKernel();

        $this->mockServices();
        $this->loadTestFixtures();
    }

    protected function tearDown(): void
    {
        $this->closeDatabaseConnections();

        parent::tearDown();

        self::restoreLocale();

        $this->cleanupPHPUnit();
    }
}
