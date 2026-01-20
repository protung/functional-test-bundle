<?php

declare(strict_types=1);

use Speicher210\FunctionalTestBundle\Command\TestStubCreateCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->set(TestStubCreateCommand::class)
        ->tag('console.command');
};
