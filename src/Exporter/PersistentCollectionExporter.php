<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Exporter;

use Doctrine\ORM\PersistentCollection;
use Override;
use Psl\Str;
use SebastianBergmann\Exporter\ExportContext;
use SebastianBergmann\Exporter\Exporter;
use SebastianBergmann\Exporter\ObjectExporter;

use function assert;
use function spl_object_id;

/**
 * Exports a PersistentCollection as its elements: by default PHPUnit walks into the entity manager it holds and never finishes.
 * PHPUnit also exports the values of comparisons that pass, so the elements are only named and an unloaded collection stays unloaded.
 *
 * Object exporters need sebastian/exporter 8.2 or later, which comes with PHPUnit 13.3.
 */
final readonly class PersistentCollectionExporter implements ObjectExporter
{
    #[Override]
    public function handles(object $object): bool
    {
        return $object instanceof PersistentCollection;
    }

    #[Override]
    public function export(object $object, Exporter $exporter, int $indentation, ExportContext $context): string
    {
        assert($object instanceof PersistentCollection);
        assert($indentation >= 0);

        $header = $object::class . ' Object #' . spl_object_id($object);
        if (! $object->isInitialized()) {
            return $header . ' (not loaded)';
        }

        $elements = $object->unwrap()->toArray();
        if ($elements === []) {
            return $header . ' ()';
        }

        // Same format as Exporter::exportObject(), so that nested exports are indented the same way.
        $whitespace = Str\repeat(' ', 4 * $indentation);
        $buffer     = '';
        foreach ($elements as $key => $element) {
            $buffer .= Str\format("%s    %s => %s,\n", $whitespace, $exporter->export($key), $exporter->shortenedExport($element));
        }

        return $header . " (\n" . $buffer . $whitespace . ')';
    }
}
