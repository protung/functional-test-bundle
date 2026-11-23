<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Comparator;

use Doctrine\Common\Collections\Collection;
use Override;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Exporter\Exporter;
use Speicher210\FunctionalTestBundle\Exporter\DoctrineCollectionExporter;

use function array_pop;
use function assert;
use function in_array;

/**
 * A built entity holds an ArrayCollection and a persisted one a PersistentCollection;
 * PHPUnit's object comparator refuses the two on their class alone, without looking at the elements.
 * Keys are ignored: a loaded collection numbers its elements afresh, while a built one keeps the keys of its history.
 * Order is not: a collection compared here needs an order-by in its mapping, or it loads in whatever order the query plan returns.
 */
final class DoctrineCollection extends Comparator
{
    /**
     * The object comparator's own recursion guard starts over below every collection,
     * so a bidirectional association would come back to the same pair forever without this one.
     *
     * @var list<array{Collection<array-key, mixed>, Collection<array-key, mixed>}>
     */
    private array $inProgress = [];

    private readonly Exporter $failureExporter;

    public function __construct()
    {
        $this->failureExporter = new Exporter(0, 40, DoctrineCollectionExporter::exportingElementsInFull());
    }

    #[Override]
    public function accepts(mixed $expected, mixed $actual): bool
    {
        return $expected instanceof Collection && $actual instanceof Collection;
    }

    #[Override]
    public function assertEquals(
        mixed $expected,
        mixed $actual,
        float $delta = 0.0,
        bool $canonicalize = false,
        bool $ignoreCase = false,
    ): void {
        assert($expected instanceof Collection);
        assert($actual instanceof Collection);

        $pair = [$expected, $actual];
        if (in_array($pair, $this->inProgress, true)) {
            return;
        }

        $this->inProgress[] = $pair;

        try {
            $equal = $this->valuesAreEqual($expected->getValues(), $actual->getValues(), $delta, $canonicalize, $ignoreCase);
        } finally {
            array_pop($this->inProgress);
        }

        if ($equal) {
            return;
        }

        // The exporter registered with PHPUnit names the elements without showing them, which would hide what differs.
        throw new ComparisonFailure(
            $expected,
            $actual,
            $this->failureExporter->export($expected),
            $this->failureExporter->export($actual),
            'Failed asserting that two Doctrine collections are equal.',
            $this->contextLines(),
        );
    }

    private function valuesAreEqual(mixed $expected, mixed $actual, float $delta, bool $canonicalize, bool $ignoreCase): bool
    {
        try {
            $this->factory()
                ->getComparatorFor($expected, $actual)
                ->assertEquals($expected, $actual, $delta, $canonicalize, $ignoreCase);
        } catch (ComparisonFailure) {
            return false;
        }

        return true;
    }
}
