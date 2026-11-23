<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Exporter;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Collection;
use Override;
use Psl\Str;
use SebastianBergmann\Exporter\ExportContext;
use SebastianBergmann\Exporter\Exporter;
use SebastianBergmann\Exporter\ObjectExporter;

use function assert;

/**
 * Exporting a PersistentCollection property by property drags in the entity manager it references, which can take forever;
 * only the elements are exported, under the same header whatever the collection class, so a diff shows the elements that differ.
 * Keys are left out, as the comparator ignores them: a loaded collection numbers its elements afresh.
 * An unloaded collection stays unloaded, since exporting must never query the database.
 */
final readonly class DoctrineCollectionExporter implements ObjectExporter
{
    private function __construct(private bool $elementsInFull)
    {
    }

    /**
     * For registering with PHPUnit: comparisons that pass still export every value they compared, shortened to one line,
     * so a full export there would walk every entity graph on every assertion.
     *
     * Register it in PHPUnit's own registry, not as the comparator factory's exporter:
     * only the registry also reaches the failure messages PHPUnit writes itself.
     */
    public static function namingElements(): self
    {
        return new self(false);
    }

    /** For the diff of collections that differ, where an element named but not shown would hide what differs. */
    public static function exportingElementsInFull(): self
    {
        return new self(true);
    }

    #[Override]
    public function handles(object $object): bool
    {
        return $object instanceof Collection;
    }

    #[Override]
    public function export(object $object, Exporter $exporter, int $indentation, ExportContext $context): string
    {
        assert($object instanceof Collection);
        assert($indentation >= 0);

        if ($object instanceof AbstractLazyCollection && ! $object->isInitialized()) {
            return 'Collection (not loaded)';
        }

        $elements = $object->getValues();
        if ($elements === []) {
            return 'Collection []';
        }

        // The layout of Exporter::exportArray(), so that whatever is nested inside is indented to match.
        $whitespace = Str\repeat(' ', 4 * $indentation);
        $buffer     = '';
        foreach ($elements as $position => $element) {
            $exported = $this->elementsInFull
                ? $exporter->export($element, $indentation + 1, $context)
                : $exporter->shortenedExport($element);
            $buffer  .= Str\format("%s    %d => %s,\n", $whitespace, $position, $exported);
        }

        return "Collection [\n" . $buffer . $whitespace . ']';
    }
}
