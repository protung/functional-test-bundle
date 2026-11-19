<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Concern;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psl\File;
use Psl\Filesystem;
use Psl\Json;
use Psl\Str;
use Psl\Type;
use ReflectionObject;
use RuntimeException;
use Speicher210\FunctionalTestBundle\Constraint\JsonContentMatches;
use Speicher210\FunctionalTestBundle\SnapshotUpdater;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\DriverConfigurator;

use function file_exists;

/**
 * Finds the payload and expected files of a test, next to the test class.
 *
 * @phpstan-require-extends TestCase
 */
trait UsesTestFiles
{
    /**
     * Array with the number of request payload content files per test.
     *
     * @var array<string,int>
     */
    private array $payloadContentFiles = [];

    /**
     * Array with the number of assertions against expected files per test.
     *
     * @var array<string,int>
     */
    private array $assertionExpectedFiles = [];

    private function getTestNameForDataFiles(bool $withDataName): string
    {
        if ($withDataName) {
            return $this->name() . ($this->dataName() !== '' ? '-' . $this->dataName() : '');
        }

        return $this->name();
    }

    /**
     * @return array<mixed>
     */
    protected function getPayloadFromPhpFile(): array
    {
        return require $this->getPayloadContentFile('php');
    }

    /**
     * @param non-empty-string $type The file type (php, txt, yaml, etc.).
     *
     * @return non-empty-string
     */
    protected function getPayloadContentFile(string $type): string
    {
        $testNameWithDataName = $this->getTestNameForDataFiles(true);

        if (isset($this->payloadContentFiles[$testNameWithDataName])) {
            $this->payloadContentFiles[$testNameWithDataName]++;
        } else {
            $this->payloadContentFiles[$testNameWithDataName] = 1;
        }

        $payloadFile = $this->getCurrentPayloadContentFile($type, true);
        if (file_exists($payloadFile)) {
            return $payloadFile;
        }

        $testNameWithoutDataName = $this->getTestNameForDataFiles(false);

        if ($testNameWithDataName === $testNameWithoutDataName) {
            throw new RuntimeException(
                Str\format(
                    'Could not find payload content file for test: %s. Looked for file %s',
                    $testNameWithDataName,
                    $payloadFile,
                ),
            );
        }

        if (isset($this->payloadContentFiles[$testNameWithoutDataName])) {
            $this->payloadContentFiles[$testNameWithoutDataName]++;
        } else {
            $this->payloadContentFiles[$testNameWithoutDataName] = 1;
        }

        return $this->getCurrentPayloadContentFile($type, false);
    }

    /**
     * @param non-empty-string $type
     *
     * @return non-empty-string
     */
    public function getCurrentPayloadContentFile(string $type, bool $withDataName = true): string
    {
        $testName        = $this->getTestNameForDataFiles($withDataName);
        $payloadFileName = $testName . '-' . ($this->payloadContentFiles[$testName] ?? 1);

        return $this->getTestDirectory() . '/Fixtures/data/' . $payloadFileName . '.' . $type;
    }

    /**
     * Get the expected response content file.
     *
     * @param non-empty-string $type The file type (txt, yaml, etc.).
     *
     * @return non-empty-string
     */
    protected function getExpectedContentFile(string $type): string
    {
        $testName = $this->getTestNameForDataFiles(true);
        if (isset($this->assertionExpectedFiles[$testName])) {
            $this->assertionExpectedFiles[$testName]++;
        } else {
            $this->assertionExpectedFiles[$testName] = 1;
        }

        return $this->getCurrentExpectedResponseContentFile($type);
    }

    /**
     * @param non-empty-string $type
     *
     * @return non-empty-string
     */
    public function getCurrentExpectedResponseContentFile(string $type): string
    {
        $testName         = $this->getTestNameForDataFiles(true);
        $expectedFileName = $testName . '-' . ($this->assertionExpectedFiles[$testName] ?? 1);

        return $this->getTestDirectory() . '/Expected/' . $expectedFileName . '.' . $type;
    }

    protected function getTestDirectory(): string
    {
        $reflection = new ReflectionObject($this);
        $fileName   = Type\non_empty_string()->coerce($reflection->getFileName());

        return Filesystem\get_directory($fileName);
    }

    /**
     * @param array<mixed> $actual
     */
    protected function assertArrayMatchesExpectedJson(array $actual): void
    {
        $expectedFile = $this->getExpectedContentFile('json');

        try {
            $expectedFileContent = File\read($expectedFile);
        } catch (File\Exception\NotFoundException $exception) {
            if (! DriverConfigurator::isOutputUpdaterEnabled()) {
                throw $exception;
            }

            $expectedFileContent = '{}';
            File\write($expectedFile, $expectedFileContent);
        }

        try {
            static::assertThat(Json\encode($actual), new JsonContentMatches($expectedFileContent));
        } catch (ExpectationFailedException $e) {
            $comparisonFailure = $e->getComparisonFailure();
            if ($comparisonFailure !== null && DriverConfigurator::isOutputUpdaterEnabled()) {
                SnapshotUpdater::updateJson(
                    $comparisonFailure,
                    $expectedFile,
                );
            }

            throw $e;
        }
    }
}
