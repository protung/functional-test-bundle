<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\Comparator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory;
use Speicher210\FunctionalTestBundle\Comparator\DoctrineCollection;
use stdClass;

#[RequiresPhpunit('>= 13.3.0')]
final class DoctrineCollectionTest extends TestCase
{
    public function testCollectionsOfDifferentClassesAndKeysWithEqualElementsAreEqual(): void
    {
        // ORM 2 does not accept a null entity manager.
        $persisted = new PersistentCollection(
            self::createStub(EntityManagerInterface::class),
            new ClassMetadata(stdClass::class),
            new ArrayCollection([self::element('first'), self::element('second')]),
        );

        self::comparator()->assertEquals(
            new ArrayCollection([3 => self::element('first'), 7 => self::element('second')]),
            $persisted,
        );

        $this->addToAssertionCount(1);
    }

    public function testCollectionsWithTheSameElementsInAnotherOrderDiffer(): void
    {
        $this->expectException(ComparisonFailure::class);

        self::comparator()->assertEquals(
            new ArrayCollection([self::element('first'), self::element('second')]),
            new ArrayCollection([self::element('second'), self::element('first')]),
        );
    }

    public function testFailureShowsTheElementsInFull(): void
    {
        try {
            self::comparator()->assertEquals(
                new ArrayCollection([self::element('first')]),
                new ArrayCollection([self::element('second')]),
            );
        } catch (ComparisonFailure $failure) {
            self::assertStringMatchesFormat(
                <<<'EXPORT'
                Collection [
                    0 => stdClass Object #%d (
                        'name' => 'second',
                    ),
                ]
                EXPORT,
                $failure->getActualAsString(),
            );

            return;
        }

        self::fail('The collections are expected to differ.');
    }

    public function testComparesABidirectionalAssociationWithoutLoopingForever(): void
    {
        $this->registerComparator(new DoctrineCollection());

        self::assertEquals(self::ownerWithElement('first'), self::ownerWithElement('first'));
    }

    public function testTheSameCollectionsDifferAgainWhenComparedAgain(): void
    {
        $comparator = self::comparator();
        $expected   = new ArrayCollection([self::element('first')]);
        $actual     = new ArrayCollection([self::element('second')]);

        try {
            $comparator->assertEquals($expected, $actual);
            self::fail('The collections are expected to differ.');
        } catch (ComparisonFailure) {
            // The recursion guard must not remember the pair once its comparison is over.
        }

        $this->expectException(ComparisonFailure::class);

        $comparator->assertEquals($expected, $actual);
    }

    public function testComparesTheElementsWithTheGivenOptions(): void
    {
        $comparator = self::comparator();

        $comparator->assertEquals(new ArrayCollection([1.0]), new ArrayCollection([1.05]), delta: 0.1);
        $comparator->assertEquals(new ArrayCollection([2, 1]), new ArrayCollection([1, 2]), canonicalize: true);
        $comparator->assertEquals(new ArrayCollection(['FIRST']), new ArrayCollection(['first']), ignoreCase: true);

        $this->addToAssertionCount(3);
    }

    private static function comparator(): DoctrineCollection
    {
        $comparator = new DoctrineCollection();
        $comparator->setFactory(Factory::getInstance());

        return $comparator;
    }

    private static function element(string $name): stdClass
    {
        $element       = new stdClass();
        $element->name = $name;

        return $element;
    }

    private static function ownerWithElement(string $name): stdClass
    {
        $owner           = new stdClass();
        $element         = self::element($name);
        $element->owner  = $owner;
        $owner->elements = new ArrayCollection([$element]);

        return $owner;
    }
}
