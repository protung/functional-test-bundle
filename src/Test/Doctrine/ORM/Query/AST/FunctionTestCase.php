<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Doctrine\ORM\Query\AST;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use PHPUnit\Framework\TestCase;

abstract class FunctionTestCase extends TestCase
{
    /**
     * @return iterable<non-empty-string, class-string<FunctionNode>>
     */
    abstract protected function registeredStringFunctions(): iterable;

    protected function getEntityManager(): EntityManager
    {
        $configuration = new Configuration();
        $configuration->setProxyDir(__DIR__ . '/Fixtures/Proxies');
        $configuration->setProxyNamespace(__NAMESPACE__ . '\Proxy');
        $configuration->setAutoGenerateProxyClasses(true);
        $configuration->setMetadataDriverImpl(new AttributeDriver([__DIR__ . '/Fixtures']));
        foreach ($this->registeredStringFunctions() as $name => $class) {
            $configuration->addCustomStringFunction($name, $class);
        }

        return new EntityManager(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            $configuration,
        );
    }
}
