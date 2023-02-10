<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Command;

use Psl;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function count;
use function end;
use function explode;
use function implode;
use function sprintf;
use function ucfirst;

use const PHP_EOL;

/**
 * Command to create necessary files and directories for a REST functional test.
 */
class TestStubCreateCommand extends Command
{
    /** @var class-string */
    private string $fixtureLoaderExtendClass;

    /**
     * @param class-string $fixtureLoaderExtendClass
     */
    public function __construct(string $fixtureLoaderExtendClass)
    {
        $this->fixtureLoaderExtendClass = $fixtureLoaderExtendClass;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('sp210:test:stub:create')
            ->setDescription('Create necessary files and directories for a REST functional test.')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'The path to the directory of the test case.',
            )
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'The name of the test.',
            )
            ->addArgument(
                'number-of-expected',
                InputArgument::OPTIONAL,
                'The number of expected files to generate.',
                '1',
            )
            ->addOption(
                'custom-loader',
                'l',
                InputOption::VALUE_NONE,
                'Flag if a custom loader class for the test should be created.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $this->getTestDirectoryPath(Psl\Type\non_empty_string()->coerce($input->getArgument('path')));
        $namespace = $this->getNamespace($directory);
        $name      = Psl\Type\string()->coerce($input->getArgument('name'));

        $customLoader = (bool) $input->getOption('custom-loader');

        $fileSystem = new Filesystem();

        if (! $fileSystem->exists($directory)) {
            $output->writeln(
                sprintf('Invalid directory <info>%s</info>', $directory),
            );

            return 1;
        }

        $numberOfExpected = Psl\Type\int()->coerce($input->getArgument('number-of-expected'));
        for ($i = 1; $i <= $numberOfExpected; $i++) {
            $expectedFilename = $directory . '/Expected/' . $name . '-' . $i . '.json';
            if ($fileSystem->exists($expectedFilename)) {
                $output->writeln(
                    sprintf('Expected file <info>%s</info> already exists.', $expectedFilename),
                );
            } else {
                $fileSystem->dumpFile($expectedFilename, '{}');
                $output->writeln(
                    sprintf('Added Expected file: <info>%s</info>', $expectedFilename),
                );
            }
        }

        $fixturesFilename = $directory . '/Fixtures/' . $name . '.php';
        if ($fileSystem->exists($fixturesFilename)) {
            $output->writeln(
                sprintf('Fixtures file <info>%s</info> already exists.', $fixturesFilename),
            );
        } else {
            $fileSystem->dumpFile($fixturesFilename, $this->getFixturesContent($namespace, $name, $customLoader));
            $output->writeln(
                sprintf('Added Fixtures file: <info>%s</info>', $fixturesFilename),
            );
        }

        if (! $customLoader) {
            return 0;
        }

        $fixturesLoaderFilename = $directory . '/Fixtures/Loaders/' . ucfirst($name) . '.php';
        if ($fileSystem->exists($fixturesLoaderFilename)) {
            $output->writeln(
                sprintf('Fixtures Loader file <info>%s</info> already exists.', $fixturesLoaderFilename),
            );
        } else {
            $fileSystem->dumpFile(
                $fixturesLoaderFilename,
                $this->getFixturesLoaderContent($namespace, ucfirst($name)),
            );
            $output->writeln(
                sprintf('Added Fixtures Loader file: <info>%s</info>', $fixturesLoaderFilename),
            );
        }

        return 0;
    }

    /**
     * Get the namespace for the codes.
     */
    private function getNamespace(string $path): string
    {
        $finder = Finder::create()->in($path)->depth(0)->files()->name('*Test.php');
        if (count($finder) === 0) {
            throw new RuntimeException('No test case found in ' . $path);
        }

        $namespace = '';
        foreach ($finder as $splFileInfo) {
            $matches = Psl\Regex\first_match($splFileInfo->getContents(), '/(^|\s)namespace(.*?)\s*;/i');
            if ($matches !== null) {
                $namespace = Psl\Str\trim($matches[2]);
                break;
            }
        }

        return $namespace . '\Fixtures\Loaders';
    }

    /**
     * @param non-empty-string $path
     */
    private function getTestDirectoryPath(string $path): string
    {
        if (! Psl\Filesystem\is_directory($path)) {
            $path = Psl\Env\current_dir() . $path;
        }

        return Psl\Type\string()->coerce(Psl\Filesystem\canonicalize($path));
    }

    private function getFixturesContent(string $namespace, string $name, bool $customLoader): string
    {
        $content   = [];
        $content[] = '<?php';
        $content[] = null;
        $content[] = 'declare(strict_types=1);';
        $content[] = null;

        if ($customLoader) {
            $content[] = 'use ' . $namespace . '\\' . ucfirst($name) . ';';
            $content[] = null;
            $content[] = 'return [' . ucfirst($name) . '::class];';
            $content[] = null;
        } else {
            $content[] = 'return [];';
            $content[] = null;
        }

        return implode(PHP_EOL, $content);
    }

    private function getFixturesLoaderContent(string $namespace, string $name): string
    {
        $loaderParentAlias = explode('\\', $this->fixtureLoaderExtendClass);

        $content   = [];
        $content[] = '<?php';
        $content[] = null;
        $content[] = 'declare(strict_types=1);';
        $content[] = null;
        $content[] = 'namespace ' . $namespace . ';';
        $content[] = null;
        $content[] = 'use Generator;';
        $content[] = 'use ' . $this->fixtureLoaderExtendClass . ';';
        $content[] = null;
        $content[] = 'final class ' . $name . ' extends ' . end($loaderParentAlias);
        $content[] = '{';
        $content[] = '    protected function doLoad(): Generator';
        $content[] = '    {';
        $content[] = '    }';
        $content[] = '}';
        $content[] = null;

        return implode(PHP_EOL, $content);
    }
}
