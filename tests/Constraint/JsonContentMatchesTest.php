<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\Constraint;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Speicher210\FunctionalTestBundle\Constraint\JsonContentMatches;

final class JsonContentMatchesTest extends TestCase
{
    /**
     * @return mixed[]
     */
    public static function dataProviderTestEvaluateTheSameJsonContent(): array
    {
        return [
            ['{}', '[]'],
            ['[]', '{}'],
            ['[]', '[]'],
            ['{"test"}', '{"test"}'],
            ['{"test":1}', '{"test":1}'],
        ];
    }

    /**
     * @dataProvider dataProviderTestEvaluateTheSameJsonContent
     */
    public function testEvaluateReturnsNullForTheSameJsonContent(string $expected, string $content): void
    {
        $constraint = new JsonContentMatches($expected);

        self::assertNull($constraint->evaluate($content));
    }

    public function testEvaluateThrowsExceptionForDifferentJsonContent(): void
    {
        $constraint = new JsonContentMatches('{"test":1}');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            <<<'MSG'
            Failed asserting that "{
                "test": "1"
            }" matches JSON string "{
                "test": 1
            }".
            MSG,
        );

        $constraint->evaluate('{"test":"1"}');
    }

    public function testEvaluateReturnsTrueForTheSameJsonContentWithReturnResultSetToTrue(): void
    {
        $constraint = new JsonContentMatches('{"some":"json"}');

        self::assertTrue($constraint->evaluate('{"some":"json"}', '', true));
    }

    public function testEvaluateReturnsFalseForDifferentJsonContentWithReturnResultSetToTrue(): void
    {
        $constraint = new JsonContentMatches('{"some":"json2"}');

        self::assertFalse($constraint->evaluate('{"some":"json"}', '', true));
    }
}
