<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\Constraint;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Speicher210\FunctionalTestBundle\Constraint\ResponseStatusCodeSame;
use Symfony\Component\HttpFoundation\Response;

final class ResponseStatusCodeSameTest extends TestCase
{
    public function testEvaluateReturnsNullForTheSameStatusCode(): void
    {
        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getStatusCode')->willReturn(200);

        $constraint = new ResponseStatusCodeSame(200);

        self::assertNull($constraint->evaluate($response));
    }

    public function testEvaluateThrowsExceptionForDifferentStatusCode(): void
    {
        $response = $this->createMock(Response::class);
        $response->expects(self::exactly(2))->method('getStatusCode')->willReturn(201);

        $constraint = new ResponseStatusCodeSame(200);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that response code "201" matches expected "200".');

        $constraint->evaluate($response);
    }

    public function testEvaluateReturnsTrueForTheSameStatusCodeWithReturnResultSetToTrue(): void
    {
        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getStatusCode')->willReturn(200);

        $constraint = new ResponseStatusCodeSame(200);

        self::assertTrue($constraint->evaluate($response, '', true));
    }

    public function testEvaluateReturnsFalseForDifferentStatusCodeWithReturnResultSetToTrue(): void
    {
        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getStatusCode')->willReturn(201);

        $constraint = new ResponseStatusCodeSame(200);

        self::assertFalse($constraint->evaluate($response, '', true));
    }
}
