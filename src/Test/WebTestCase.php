<?php

namespace Speicher210\FunctionalTestBundle\Test;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Liip\FunctionalTestBundle\Test\WebTestCase as LiipWebTestCase;
use Speicher210\FunctionalTestBundle\DependencyInjection\MockerContainer;

/**
 * Abstract class for test cases.
 */
abstract class WebTestCase extends LiipWebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = array())
    {
        $options['debug'] = false;

        return parent::createKernel($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $emDefault = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->resetDatabaseSchema($emDefault);

        $this->postFixtureSetup();

        $this->prepareTestFixtures();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $container = $this->getContainer();
        if ($container instanceof MockerContainer) {
            $container->unmockAll();
        }

        $connections = $container->get('doctrine')->getConnections();
        foreach ($connections as $connection) {
            $connection->close();
        }
    }

    /**
     * Mock a container service.
     *
     * @param string $idService The service ID.
     * @param \PHPUnit_Framework_MockObject_MockObject $mock The mock.
     */
    protected function mockContainerService($idService, \PHPUnit_Framework_MockObject_MockObject $mock)
    {
        $container = $this->getContainer();
        if (!$container instanceof MockerContainer) {
            throw new \RuntimeException('Container must be an instance of ' . MockerContainer::class);
        }

        $container->mock($idService, $mock);
    }

    /**
     * Prepare the text fixtures and the expected content file.
     *
     * @return string
     */
    protected function prepareTestFixtures()
    {
        $reflection = new \ReflectionObject($this);

        $fixturesFile = dirname($reflection->getFileName()) . '/Fixtures/' . $this->getName() . '.php';

        $alwaysLoadingFixtures = $this->getAlwaysLoadingFixtures();

        if (file_exists($fixturesFile)) {
            $this->loadFixtures(array_merge($alwaysLoadingFixtures, require($fixturesFile)));
        } elseif (count($alwaysLoadingFixtures) > 0) {
            $this->loadFixtures($alwaysLoadingFixtures);
        }
    }

    /**
     * Get the fixtures to always load when preparing the test fixtures.
     *
     * @return array
     */
    protected function getAlwaysLoadingFixtures()
    {
        return array();
    }

    /**
     * Reset the database schema.
     *
     * @param EntityManagerInterface $em The entity manager.
     *
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    protected function resetDatabaseSchema(EntityManagerInterface $em)
    {
        $metaData = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        if (!empty($metaData)) {
            $schemaTool->createSchema($metaData);
        }
    }

    /**
     * Get the object manager.
     *
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}
