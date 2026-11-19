<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Concern;

use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use ReflectionObject;
use Speicher210\FunctionalTestBundle\Test\Assert\Image;
use Speicher210\FunctionalTestBundle\Test\Intl\LocaleSensitiveTestCase;
use Speicher210\FunctionalTestBundle\Test\PHPUnitHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use function str_starts_with;

/**
 * Groups the helpers of the bundle's kernel test cases, so that test cases with another base class can use them too.
 *
 * The using class runs them from its own setUp() and tearDown(), like KernelTestCase does.
 *
 * @phpstan-require-extends KernelTestCase
 */
trait FunctionalTest
{
    use Image;
    use InteractsWithContainer;
    use InteractsWithDoctrine;
    use LoadsFixtures;
    use PHPMatcherAssertions;
    use PHPUnitHelper;
    use UsesTestFiles;
    use LocaleSensitiveTestCase {
        setUp as localeSensitiveTestCaseSetUp;
        tearDown as localeSensitiveTestCaseTearDown;
    }

    /**
     * Unset test case properties to speed up GC.
     */
    protected function cleanupPHPUnit(): void
    {
        $reflection = new ReflectionObject($this);
        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic() || str_starts_with($property->getDeclaringClass()->getName(), 'PHPUnit\\')) {
                continue;
            }

            if ($property->isReadOnly() || $property->isVirtual()) {
                continue;
            }

            if ($property->getType() !== null && ! $property->getType()->allowsNull()) {
                continue;
            }

            // Bypass property hooks, the value is only reset to free memory.
            $property->setRawValue($this, null);
        }
    }
}
