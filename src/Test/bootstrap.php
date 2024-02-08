<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Speicher210\FunctionalTestBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

$method = new ReflectionMethod(KernelTestCase::class, 'createKernel');

/** @var KernelInterface $kernel */
$kernel = $method->invoke(null);
$kernel->boot();

/** @var EntityManagerInterface $entityManager */
$entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');

$schemaTool = new SchemaTool($entityManager);
$schemaTool->dropDatabase();

$schemaTool->createSchema(
    $entityManager->getMetadataFactory()->getAllMetadata(),
);
