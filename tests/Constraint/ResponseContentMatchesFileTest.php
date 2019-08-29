<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\Constraint;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Speicher210\FunctionalTestBundle\Constraint\ResponseContentMatchesFile;
use Symfony\Component\HttpFoundation\Response;

final class ResponseContentMatchesFileTest extends TestCase
{
    public function testEvaluateReturnsNullForTheResponseContentMatchingFile() : void
    {
        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getContent')->willReturn('some content');

        $root         = vfsStream::setup();
        $expectedFile = vfsStream::newFile('expected')->at($root)->setContent('some content');

        $constraint = new ResponseContentMatchesFile($expectedFile->url());

        self::assertNull($constraint->evaluate($response));
    }

    public function testEvaluateThrowsExceptionForResponseContentNotMatchingFile() : void
    {
        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getContent')->willReturn('some content');

        $root         = vfsStream::setup();
        $expectedFile = vfsStream::newFile('expected')->at($root)->setContent('some content1');

        $constraint = new ResponseContentMatchesFile($expectedFile->url());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            \sprintf(
                "%s\n%s",
                'Failed asserting that response content matches content of file "vfs://root/expected".',
                '"some content" does not match "some content1" pattern'
            )
        );

        $constraint->evaluate($response);
    }

    public function testEvaluateReturnsTrueForTheResponseContentMatchingFileWithReturnResultSetToTrue() : void
    {
        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getContent')->willReturn('some content');

        $root         = vfsStream::setup();
        $expectedFile = vfsStream::newFile('expected')->at($root)->setContent('some content');

        $constraint = new ResponseContentMatchesFile($expectedFile->url());

        self::assertTrue($constraint->evaluate($response, '', true));
    }

    public function testEvaluateReturnsFalseForResponseContentNotMatchingFileWithReturnResultSetToTrue() : void
    {
        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getContent')->willReturn('some content');

        $root         = vfsStream::setup();
        $expectedFile = vfsStream::newFile('expected')->at($root)->setContent('some content1');

        $constraint = new ResponseContentMatchesFile($expectedFile->url());

        self::assertFalse($constraint->evaluate($response, '', true));
    }
}
