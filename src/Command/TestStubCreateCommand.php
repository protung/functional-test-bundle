<?php

namespace Speicher210\FunctionalTestBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Command to create necessary files and directories for a REST functional test.
 */
class TestStubCreateCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
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
            ->addOption(
                'custom-loader',
                'l',
                InputOption::VALUE_NONE,
                'Flag if a custom loader class for the test should be created.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $directory = $this->getTestDirectoryPath($input->getArgument('path'));
        $namespace = $this->getNamespace($directory);
        $name = $input->getArgument('name');

        $customLoader = $input->getOption('custom-loader');

        $fileSystem = new Filesystem();

        if (!$fileSystem->exists($directory)) {
            $output->writeln(
                sprintf('Invalid directory <info>%s</info>', $directory)
            );

            return;
        }

        $expectedFilename = $directory . '/Expected/' . $name . '-1.json';
        if ($fileSystem->exists($expectedFilename)) {
            $output->writeln(
                sprintf('Expected file <info>%s</info> already exists.', $expectedFilename)
            );
        } else {
            $fileSystem->dumpFile($expectedFilename, '');
            $output->writeln(
                sprintf('Added Expected file: <info>%s</info>', $expectedFilename)
            );
        }

        $fixturesFilename = $directory . '/Fixtures/' . $name . '.php';
        if ($fileSystem->exists($fixturesFilename)) {
            $output->writeln(
                sprintf('Fixtures file <info>%s</info> already exists.', $fixturesFilename)
            );
        } else {
            $fileSystem->dumpFile($fixturesFilename, $this->getFixturesContent($namespace, $name, $customLoader));
            $output->writeln(
                sprintf('Added Fixtures file: <info>%s</info>', $fixturesFilename)
            );
        }

        if ($customLoader) {
            $fixturesLoaderFilename = $directory . '/Fixtures/Loaders/' . ucfirst($name) . '.php';
            if ($fileSystem->exists($fixturesLoaderFilename)) {
                $output->writeln(
                    sprintf('Fixtures Loader file <info>%s</info> already exists.', $fixturesLoaderFilename)
                );
            } else {
                $fileSystem->dumpFile(
                    $fixturesLoaderFilename,
                    $this->getFixturesLoaderContent($namespace, ucfirst($name))
                );
                $output->writeln(
                    sprintf('Added Fixtures Loader file: <info>%s</info>', $fixturesLoaderFilename)
                );
            }
        }
    }

    /**
     * Get the namespace for the code.s
     *
     * @param string $path
     *
     * @return string
     */
    private function getNamespace($path)
    {
        $finder = Finder::create()->in($path)->depth(0)->files()->name('*Test.php');
        if (count($finder) === 0) {
            throw new \RuntimeException('No test case found in ' . $path);
        }

        $namespace = '';
        foreach ($finder as $splFileInfo) {
            $matches = array();
            if (preg_match('/(^|\s)namespace(.*?)\s*;/i', $splFileInfo->getContents(), $matches)) {
                $namespace = $matches[2];
                break;
            }
        }

        return $namespace . '\Fixtures\Loaders';
    }

    /**
     * Get the directory of tests.
     *
     * @param string $path
     *
     * @return string
     */
    private function getTestDirectoryPath($path)
    {
        if (!is_dir($path)) {
            $path = getcwd() . $path;
        }

        return realpath($path);
    }

    /**
     * Get the fixtures file content.
     *
     * @param string $namespace
     * @param string $name
     * @param $customLoader
     *
     * @return string
     */
    private function getFixturesContent($namespace, $name, $customLoader)
    {
        $content = '<?php' . PHP_EOL . PHP_EOL;

        if ($customLoader) {
            $content .= 'use ' . $namespace . '\\' . ucfirst($name) . ';' . PHP_EOL;
            $content .= PHP_EOL;
            $content .= 'return array(' . PHP_EOL;
            $content .= '    ' . ucfirst($name) . '::class' . PHP_EOL;
            $content .= ');' . PHP_EOL;
        } else {
            $content .= PHP_EOL;
            $content .= 'return array();' . PHP_EOL;
        }

        return $content;
    }

    /**
     * Get the fixtures loader file content.
     *
     * @param string $namespace
     * @param string $name
     *
     * @return string
     */
    private function getFixturesLoaderContent($namespace, $name)
    {
        $content = '<?php' . PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'namespace ' . $namespace . ';' . PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'use Wingu\ApiBundle\Tests\Fixtures\Loaders\AbstractLoader;' . PHP_EOL;
        $content .= PHP_EOL;
        $content .= '/**' . PHP_EOL;
        $content .= ' * Load the fixtures.' . PHP_EOL;
        $content .= ' */' . PHP_EOL;
        $content .= 'class ' . $name . ' extends AbstractLoader' . PHP_EOL;
        $content .= '{' . PHP_EOL;
        $content .= '    /**' . PHP_EOL;
        $content .= '     * {@inheritDoc}' . PHP_EOL;
        $content .= '     */' . PHP_EOL;
        $content .= '    public function doLoad()' . PHP_EOL;
        $content .= '    {' . PHP_EOL;
        $content .= PHP_EOL;
        $content .= '    }' . PHP_EOL;
        $content .= '}' . PHP_EOL;

        return $content;
    }
}
