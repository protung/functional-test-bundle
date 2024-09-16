<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Attribute;

use Attribute;
use Doctrine\Common\DataFixtures\FixtureInterface;

/**
 * Specify the fixture to load before the test runs for a particular test in the test case.
 * This is meant to be used when the test is in the parent, but the fixtures are controlled from the child class.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class WithFixtureForTest
{
    /**
     * @param class-string<FixtureInterface> $fixture
     */
    public function __construct(
        public readonly string $fixture,
        public readonly string $testName,
    ) {
    }
}
