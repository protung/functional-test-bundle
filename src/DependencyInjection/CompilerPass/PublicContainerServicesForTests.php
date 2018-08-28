<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PublicContainerServicesForTests implements CompilerPassInterface
{
    public function process(ContainerBuilder $containerBuilder) : void
    {
        if (! $containerBuilder->hasDefinition('test.private_services_locator')) {
            return;
        }

        foreach ($containerBuilder->getAliases() as $id => $definition) {
            if ($definition->isPublic() === true) {
                continue;
            }

            $alias = new Alias($id);
            $alias->setPublic(true);
            $containerBuilder->setAlias('test.' . $id, $alias);
        }
        foreach ($containerBuilder->getDefinitions() as $id => $definition) {
            if ($definition->isPublic() === true) {
                continue;
            }

            $containerBuilder->setAlias('test.' . $id, $id);
        }
    }
}
