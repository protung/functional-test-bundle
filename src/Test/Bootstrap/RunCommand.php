<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Bootstrap;

use Psl\Str;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

/**
 * Runs a console command of the application, for example to migrate the database.
 *
 * The command runs non-interactively and its output is only shown when it fails.
 */
final readonly class RunCommand
{
    /**
     * @param non-empty-string               $command
     * @param array<non-empty-string, mixed> $arguments The arguments and options of the command. Ex: ['version' => 'latest', '--allow-no-migration' => true]
     */
    public function __construct(private string $command, private array $arguments = [])
    {
    }

    public function __invoke(KernelInterface $kernel): void
    {
        $input = new ArrayInput(['command' => $this->command, ...$this->arguments]);
        $input->setInteractive(false);

        $output = new BufferedOutput();

        try {
            // run() would change environment variables like COLUMNS for the whole test run.
            $exitCode = (new Application($kernel))->doRun($input, $output);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                Str\format('Running the command "%s" failed: %s%s%s', $this->command, $exception->getMessage(), "\n", $output->fetch()),
                previous: $exception,
            );
        }

        if ($exitCode !== Command::SUCCESS) {
            throw new RuntimeException(
                Str\format('Running the command "%s" failed with exit code %d:%s%s', $this->command, $exitCode, "\n", $output->fetch()),
            );
        }
    }
}
