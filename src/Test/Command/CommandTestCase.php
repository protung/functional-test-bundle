<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Command;

use Psl\Env;
use Psl\Str;
use Speicher210\FunctionalTestBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends KernelTestCase
{
    /**
     * @param array<string|list<string>> $commandInput An array of command arguments and options
     * @param list<string>               $inputs       A list of strings representing each input passed to the command input stream
     */
    protected function assertCommand(
        string $commandName,
        array $commandInput,
        array $inputs = [],
        int $expectedStatusCode = Command::SUCCESS,
    ): CommandTester {
        $application = new Application(self::getKernel());

        $command       = $application->find($commandName);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs($inputs);

        $originalColumns = Env\get_var('COLUMNS');
        Env\set_var('COLUMNS', '100');

        $statusCode = $commandTester->execute($commandInput);

        $originalColumns === null ? Env\remove_var('COLUMNS') : Env\set_var('COLUMNS', $originalColumns);

        self::assertSame($expectedStatusCode, $statusCode);

        $this->assertCommandOutputEqualsFile($commandTester);

        return $commandTester;
    }

    protected function assertCommandOutputEqualsFile(CommandTester $commandTester): void
    {
        $actual = $this->normalizedCommandOutput($commandTester);

        $expectedFile = $this->getExpectedContentFile('txt');

//        file_put_contents($expectedFile, $actual);self::fail('Expected updated.');
        self::assertStringEqualsFile($expectedFile, $actual);
    }

    /**
     * Remove unnecessary extra lines that could be added to the output depending on the size of the window in which test is executed.
     */
    private function normalizedCommandOutput(CommandTester $commandTester): string
    {
        return Str\trim($commandTester->getDisplay(true));
    }
}
