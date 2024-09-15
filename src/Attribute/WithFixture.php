<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Attribute;

use Attribute;
use Doctrine\Common\DataFixtures\FixtureInterface;

/**
 * Specify the fixture to load before the test runs.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class WithFixture
{
    /**
     * @param class-string<FixtureInterface> $fixture
     */
    public function __construct(
        public readonly string $fixture,
    ) {
    }
}
