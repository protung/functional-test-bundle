<?php

declare(strict_types=1);

use Speicher210\FunctionalTestBundle\Test\Bootstrap\RecreateSchemaFromDoctrineMetadata;
use Speicher210\FunctionalTestBundle\Test\Bootstrap\TestEnvironment;

TestEnvironment::prepare(new RecreateSchemaFromDoctrineMetadata());
