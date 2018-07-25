<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Iterator\FilenameFilterIterator;

/**
 * Command to create necessary files and directories for a REST functional test.
 */
class TestStubCreateCommand extends ContainerAwareCommand
{
    protected function configure() : void
    {
        $this
            ->setName('sp210:test:stub:create')
            ->setDescription('Create necessary files and directories for a REST functional test.')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'The path to the directory of the test case.'
            )
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'The name of the test.'
            )
            ->addArgument(
                'number-of-expected',
                InputArgument::OPTIONAL,
                'The number of expected files to generate.',
                1
            )
            ->addOption(
                'custom-loader',
                'l',
                InputOption::VALUE_NONE,
                'Flag if a custom loader class for the test should be created.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) : void
    {
        $directory = $this->getTestDirectoryPath($input->getArgument('path'));
        $namespace = $this->getNamespace($directory);
        $name      = $input->getArgument('name');

        $customLoader = (bool) $input->getOption('custom-loader');

        $fileSystem = new Filesystem();

        if (! $fileSystem->exists($directory)) {
            $output->writeln(
                \sprintf('Invalid directory <info>%s</info>', $directory)
            );

            return;
        }

        for ($i = 1; $i <= $input->getArgument('number-of-expected'); $i++) {
            $expectedFilename = $directory . '/Expected/' . $name . '-' . $i . '.json';
            if ($fileSystem->exists($expectedFilename)) {
                $output->writeln(
                    \sprintf('Expected file <info>%s</info> already exists.', $expectedFilename)
                );
            } else {
                $fileSystem->dumpFile($expectedFilename, '{}');
                $output->writeln(
                    \sprintf('Added Expected file: <info>%s</info>', $expectedFilename)
                );
            }
        }

        $fixturesFilename = $directory . '/Fixtures/' . $name . '.php';
        if ($fileSystem->exists($fixturesFilename)) {
            $output->writeln(
                \sprintf('Fixtures file <info>%s</info> already exists.', $fixturesFilename)
            );
        } else {
            $fileSystem->dumpFile($fixturesFilename, $this->getFixturesContent($namespace, $name, $customLoader));
            $output->writeln(
                \sprintf('Added Fixtures file: <info>%s</info>', $fixturesFilename)
            );
        }

        if (! $customLoader) {
            return;
        }

        $fixturesLoaderFilename = $directory . '/Fixtures/Loaders/' . \ucfirst($name) . '.php';
        if ($fileSystem->exists($fixturesLoaderFilename)) {
            $output->writeln(
                \sprintf('Fixtures Loader file <info>%s</info> already exists.', $fixturesLoaderFilename)
            );
        } else {
            $fileSystem->dumpFile(
                $fixturesLoaderFilename,
                $this->getFixturesLoaderContent($namespace, \ucfirst($name))
            );
            $output->writeln(
                \sprintf('Added Fixtures Loader file: <info>%s</info>', $fixturesLoaderFilename)
            );
        }
    }

    /**
     * Get the namespace for the codes.
     */
    private function getNamespace(string $path) : string
    {
        /** @var FilenameFilterIterator|\Countable $finder */
        $finder = Finder::create()->in($path)->depth(0)->files()->name('*Test.php');
        if (\count($finder) === 0) {
            throw new \RuntimeException('No test case found in ' . $path);
        }

        $namespace = '';
        foreach ($finder as $splFileInfo) {
            $matches = [];
            if (\preg_match('/(^|\s)namespace(.*?)\s*;/i', $splFileInfo->getContents(), $matches)) {
                $namespace = \trim($matches[2]);
                break;
            }
        }

        return $namespace . '\Fixtures\Loaders';
    }

    private function getTestDirectoryPath(string $path) : string
    {
        if (! \is_dir($path)) {
            $path = \getcwd() . $path;
        }

        return \realpath($path);
    }

    private function getFixturesContent(string $namespace, string $name, bool $customLoader) : string
    {
        $content   = [];
        $content[] = '<?php';
        $content[] = null;
        $content[] = 'declare(strict_types=1);';
        $content[] = null;

        if ($customLoader) {
            $content[] = 'use ' . $namespace . '\\' . \ucfirst($name) . ';';
            $content[] = null;
            $content[] = 'return [' . \ucfirst($name) . '::class];';
            $content[] = null;
        } else {
            $content[] = 'return [];';
            $content[] = null;
        }

        return \implode(\PHP_EOL, $content);
    }

    private function getFixturesLoaderContent(string $namespace, string $name) : string
    {
        $loaderParent      = $this->getContainer()->getParameter('sp210.functional_test.fixture.loader.extend_class');
        $loaderParentAlias = \explode('\\', $loaderParent);

        $content   = [];
        $content[] = '<?php';
        $content[] = null;
        $content[] = 'declare(strict_types=1);';
        $content[] = null;
        $content[] = 'namespace ' . $namespace . ';';
        $content[] = null;
        $content[] = 'use ' . $loaderParent . ';';
        $content[] = null;
        $content[] = 'final class ' . $name . ' extends ' . \end($loaderParentAlias);
        $content[] = '{';
        $content[] = '    public function doLoad() : void';
        $content[] = '    {';
        $content[] = '    }';
        $content[] = '}';
        $content[] = null;

        return \implode(\PHP_EOL, $content);
    }
}
