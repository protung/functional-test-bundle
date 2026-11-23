<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\Exporter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Exporter\Exporter;
use Speicher210\FunctionalTestBundle\Exporter\DoctrineCollectionExporter;
use stdClass;

#[RequiresPhpunit('>= 13.3.0')]
final class DoctrineCollectionExporterTest extends TestCase
{
    public function testNamesTheElementsWhateverTheCollectionClass(): void
    {
        $element  = self::element('picture.jpg');
        $exporter = new Exporter(0, 40, DoctrineCollectionExporter::namingElements());
        $expected = <<<'EXPORT'
            Collection [
                0 => stdClass Object (...),
            ]
            EXPORT;

        self::assertSame($expected, $exporter->export(new ArrayCollection([$element])));
        self::assertSame($expected, $exporter->export(self::persistentCollection([$element])));
    }

    public function testLeavesOutTheKeys(): void
    {
        $exporter = new Exporter(0, 40, DoctrineCollectionExporter::namingElements());

        self::assertSame(
            <<<'EXPORT'
            Collection [
                0 => 'first',
                1 => 'second',
            ]
            EXPORT,
            $exporter->export(new ArrayCollection([3 => 'first', 7 => 'second'])),
        );
    }

    public function testExportsAnEmptyCollection(): void
    {
        $exporter = new Exporter(0, 40, DoctrineCollectionExporter::namingElements());

        self::assertSame('Collection []', $exporter->export(new ArrayCollection()));
    }

    public function testDoesNotLoadAnUnloadedCollection(): void
    {
        // A collection without an owner and association mapping never loads, so isInitialized() would stay false anyway.
        $ownerMetadata = new ClassMetadata(stdClass::class);
        $ownerMetadata->mapOneToMany(['fieldName' => 'pictures', 'targetEntity' => stdClass::class, 'mappedBy' => 'owner']);

        $collection = self::persistentCollection([self::element('picture.jpg')]);
        // ORM 3 types the mapping as AssociationMapping, ORM 2 as an array.
        $collection->setOwner(new stdClass(), $ownerMetadata->getAssociationMapping('pictures')); // @phpstan-ignore argument.type
        $collection->setInitialized(false);

        $exporter = new Exporter(0, 40, DoctrineCollectionExporter::exportingElementsInFull());

        self::assertSame('Collection (not loaded)', $exporter->export($collection));
        self::assertFalse($collection->isInitialized());
    }

    public function testExportsTheElementsInFullIndentedLikeAnArray(): void
    {
        $element           = self::element('picture.jpg');
        $element->variants = new ArrayCollection([self::element('thumbnail.jpg')]);
        $exporter          = new Exporter(0, 40, DoctrineCollectionExporter::exportingElementsInFull());

        self::assertStringMatchesFormat(
            <<<'EXPORT'
            Array &0 [
                'pictures' => Collection [
                    0 => stdClass Object #%d (
                        'name' => 'picture.jpg',
                        'variants' => Collection [
                            0 => stdClass Object #%d (
                                'name' => 'thumbnail.jpg',
                            ),
                        ],
                    ),
                ],
            ]
            EXPORT,
            $exporter->export(['pictures' => new ArrayCollection([$element])]),
        );
    }

    public function testRefersBackToACollectionItsElementsContain(): void
    {
        $element             = self::element('picture.jpg');
        $collection          = new ArrayCollection([$element]);
        $element->collection = $collection;
        $exporter            = new Exporter(0, 40, DoctrineCollectionExporter::exportingElementsInFull());

        self::assertStringMatchesFormat(
            <<<'EXPORT'
            Collection [
                0 => stdClass Object #%d (
                    'name' => 'picture.jpg',
                    'collection' => Doctrine\Common\Collections\ArrayCollection Object #%d,
                ),
            ]
            EXPORT,
            $exporter->export($collection),
        );
    }

    public function testShortensToOneLineWhenComparedAsPartOfAnotherValue(): void
    {
        $exporter = new Exporter(0, 40, DoctrineCollectionExporter::namingElements());

        self::assertSame(
            'Collection [    0 => stdClass ...(...),]',
            $exporter->shortenedExport(new ArrayCollection([self::element('picture.jpg')])),
        );
    }

    private static function element(string $name): stdClass
    {
        $element       = new stdClass();
        $element->name = $name;

        return $element;
    }

    /**
     * @param list<stdClass> $elements
     *
     * @return PersistentCollection<int, stdClass>
     */
    private static function persistentCollection(array $elements): PersistentCollection
    {
        // ORM 2 does not accept a null entity manager.
        return new PersistentCollection(
            self::createStub(EntityManagerInterface::class),
            new ClassMetadata(stdClass::class),
            new ArrayCollection($elements),
        );
    }
}
