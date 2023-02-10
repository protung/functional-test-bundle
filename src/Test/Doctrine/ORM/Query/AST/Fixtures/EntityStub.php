<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Doctrine\ORM\Query\AST\Fixtures;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'entity_stub_table_name')]
final class EntityStub
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    public int $id;

    /** @var array<mixed> */
    #[ORM\Column(type: 'json')]
    public array $json;

    #[ORM\Column(type: 'string')]
    public string $string;

    /**
     * @param array<mixed> $json
     */
    public function __construct(int $id, array $json, string $string)
    {
        $this->id     = $id;
        $this->json   = $json;
        $this->string = $string;
    }
}
