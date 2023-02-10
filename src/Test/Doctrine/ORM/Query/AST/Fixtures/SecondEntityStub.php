<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Doctrine\ORM\Query\AST\Fixtures;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'second_entity_stub_table_name')]
final class SecondEntityStub
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    public int $id = 1;
}
