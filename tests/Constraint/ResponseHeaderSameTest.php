<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\Constraint;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Speicher210\FunctionalTestBundle\Constraint\ResponseHeaderSame;
use Symfony\Component\HttpFoundation\Response;

final class ResponseHeaderSameTest extends TestCase
{
    public function testEvaluateReturnsNullForTheSameHeader(): void
    {
        $response = new Response(headers: ['header-name' => 'some_value']);

        $constraint = new ResponseHeaderSame('header-name', 'some_value');

        self::assertNull($constraint->evaluate($response));
    }

    public function testEvaluateThrowsExceptionForDifferentHeader(): void
    {
        $response = new Response(headers: ['header-name' => 'some_value']);

        $constraint = new ResponseHeaderSame('header-name', 'some_value1');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'Failed asserting that the response has header "header-name" with value "some_value1".',
        );

        $constraint->evaluate($response);
    }

    public function testEvaluateReturnsTrueForTheSameHeaderWithReturnResultSetToTrue(): void
    {
        $response = new Response(headers: ['header-name' => 'some_value']);

        $constraint = new ResponseHeaderSame('header-name', 'some_value');

        self::assertTrue($constraint->evaluate($response, '', true));
    }

    public function testEvaluateReturnsFalseForDifferentHeaderWithReturnResultSetToTrue(): void
    {
        $response = new Response(headers: ['header-name' => 'some_value']);

        $constraint = new ResponseHeaderSame('header-name', 'some_value1');

        self::assertFalse($constraint->evaluate($response, '', true));
    }
}
