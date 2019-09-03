<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\Constraint;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Speicher210\FunctionalTestBundle\Constraint\JsonResponseContentMatches;
use Symfony\Component\HttpFoundation\Response;

final class JsonResponseContentMatchesTest extends TestCase
{
    /**
     * @return mixed[]
     */
    public static function dataProviderTestEvaluateTheSameJsonContent() : array
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
    public function testEvaluateReturnsNullForTheSameJsonContent(string $expected, string $content) : void
    {
        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getContent')->willReturn($content);

        $constraint = new JsonResponseContentMatches($expected);

        self::assertNull($constraint->evaluate($response));
    }

    public function testEvaluateThrowsExceptionForDifferentJsonContent() : void
    {
        $response = $this->createMock(Response::class);
        $response->expects(self::exactly(3))->method('getContent')->willReturn('{"test":"1"}');

        $constraint = new JsonResponseContentMatches('{"test":1}');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'Failed asserting that "{
    "test": "1"
}" matches JSON string "{
    "test": 1
}".
"1" does not match "1".'
        );

        $constraint->evaluate($response);
    }

    public function testEvaluateReturnsTrueForTheSameJsonContentWithReturnResultSetToTrue() : void
    {
        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getContent')->willReturn('{"some":"json"}');

        $constraint = new JsonResponseContentMatches('{"some":"json"}');

        self::assertTrue($constraint->evaluate($response, '', true));
    }

    public function testEvaluateReturnsFalseForDifferentJsonContentWithReturnResultSetToTrue() : void
    {
        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getContent')->willReturn('{"some":"json"}');

        $constraint = new JsonResponseContentMatches('{"some":"json2"}');

        self::assertFalse($constraint->evaluate($response, '', true));
    }
}
