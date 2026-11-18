<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\Exporter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psl\Str;
use SebastianBergmann\Exporter\Exporter;
use Speicher210\FunctionalTestBundle\Exporter\PersistentCollectionExporter;
use stdClass;

use function spl_object_id;

#[RequiresPhpunit('>= 13.3.0')]
final class PersistentCollectionExporterTest extends TestCase
{
    public function testExportsTheElementsOfALoadedCollection(): void
    {
        $element       = new stdClass();
        $element->name = 'picture.jpg';
        $collection    = self::createPersistentCollection([$element]);

        $exporter = new Exporter(0, 40, new PersistentCollectionExporter());

        self::assertSame(
            Str\format(
                <<<'EXPORT'
                Array &0 [
                    'pictures' => Doctrine\ORM\PersistentCollection Object #%d (
                        0 => stdClass Object (...),
                    ),
                ]
                EXPORT,
                spl_object_id($collection),
            ),
            $exporter->export(['pictures' => $collection]),
        );
    }

    public function testDoesNotLoadAnUnloadedCollection(): void
    {
        // A collection without an owner and association mapping never loads, so isInitialized() would stay false anyway.
        $ownerMetadata = new ClassMetadata(stdClass::class);
        $ownerMetadata->mapOneToMany(['fieldName' => 'pictures', 'targetEntity' => stdClass::class, 'mappedBy' => 'owner']);

        $collection = self::createPersistentCollection([new stdClass()]);
        // ORM 3 types the mapping as AssociationMapping, ORM 2 as an array.
        $collection->setOwner(new stdClass(), $ownerMetadata->getAssociationMapping('pictures')); // @phpstan-ignore argument.type
        $collection->setInitialized(false);

        $exporter = new Exporter(0, 40, new PersistentCollectionExporter());

        self::assertSame(
            Str\format('Doctrine\ORM\PersistentCollection Object #%d (not loaded)', spl_object_id($collection)),
            $exporter->export($collection),
        );
        self::assertFalse($collection->isInitialized());
    }

    public function testNamesTheCollectionWhenComparedWithAnotherCollectionClass(): void
    {
        $element    = new stdClass();
        $collection = self::createPersistentCollection([$element]);

        $this->registerObjectExporter(new PersistentCollectionExporter());

        try {
            self::assertEquals(new ArrayCollection([$element]), $collection);
        } catch (ExpectationFailedException $e) {
            self::assertSame(
                Str\format(
                    <<<'MESSAGE'
                    Doctrine\ORM\PersistentCollection Object #%d (
                        0 => stdClass Object (),
                    ) is not instance of expected class "Doctrine\Common\Collections\ArrayCollection".
                    MESSAGE,
                    spl_object_id($collection),
                ),
                $e->getMessage(),
            );

            return;
        }

        self::fail('The collections are expected to differ.');
    }

    /**
     * @param list<stdClass> $elements
     *
     * @return PersistentCollection<int, stdClass>
     */
    private static function createPersistentCollection(array $elements): PersistentCollection
    {
        // ORM 2 does not accept a null entity manager.
        return new PersistentCollection(
            self::createStub(EntityManagerInterface::class),
            new ClassMetadata(stdClass::class),
            new ArrayCollection($elements),
        );
    }
}
