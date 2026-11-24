<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Bootstrap;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\Persistence\ConnectionRegistry;
use Psl\File;
use Psl\Str;
use Psl\Type;
use RuntimeException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Executes SQL files in the given order, for what is not part of the Doctrine metadata, such as views.
 *
 * Put a single statement in each file: not all database drivers accept several, or report the errors of all of them.
 */
final readonly class ExecuteSqlFiles
{
    /**
     * @param list<non-empty-string> $files
     * @param non-empty-string|null  $connection The name of the connection, the default one if null.
     */
    public function __construct(private array $files, private string|null $connection = null)
    {
    }

    public function __invoke(KernelInterface $kernel): void
    {
        $doctrine   = Type\instance_of(ConnectionRegistry::class)->coerce($kernel->getContainer()->get('doctrine'));
        $connection = Type\instance_of(Connection::class)->coerce($doctrine->getConnection($this->connection));

        foreach ($this->files as $file) {
            try {
                $connection->executeStatement(File\read($file));
            } catch (DBALException $exception) {
                throw new RuntimeException(
                    Str\format('Executing the SQL file "%s" failed: %s', $file, $exception->getMessage()),
                    previous: $exception,
                );
            }
        }
    }
}
