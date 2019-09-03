<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use SebastianBergmann\Exporter\Exporter;

// Needed for compatibility with phpunit < 8.0
trait ConstraintExporter
{
    protected function exporter() : Exporter
    {
        if (\method_exists(Constraint::class, 'exporter')) {
            return parent::exporter();
        }

        return new Exporter();
    }
}
