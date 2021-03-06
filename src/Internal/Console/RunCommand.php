<?php

declare(strict_types=1);

namespace Inphest\Internal\Console;

use FilesystemIterator;
use Inphest\Assert;
use Inphest\Internal\Console\Io\InputInterface;
use Inphest\Internal\Console\Io\OutputInterface;
use Inphest\Internal\Printer\PrinterFactory;
use Inphest\Internal\TestRegistry;
use Inphest\Internal\TestRunner;
use InvalidArgumentException;
use RecursiveDirectoryIterator;

final class RunCommand
{
    private const SUCCESS = 0;
    private const FAILURE = 1;

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument(1);

        if (!is_string($path) || $path === '') {
            $path = 'tests';
        }

        // Make the path absolute if it's not already
        if ($path[0] !== '/') {
            $path = getcwd() . "/{$path}";
        }

        if (!file_exists($path) || !is_dir($path)) {
            throw new InvalidArgumentException(
                "The given directory '{$path}' does not exist"
            );
        }

        $version = \Inphest\VERSION;
        $output->writeln("Inphest v{$version}");

        $iterator = new RecursiveDirectoryIterator(
            $path,
            FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
        );

        /** @var string $file */
        foreach ($iterator as $file) {
            // Convert the absolute path to be relative from $path
            $relativePath = substr($file, strlen($path));

            TestRegistry::setFile(basename($relativePath, '.php'));

            /** @psalm-suppress UnresolvableInclude */
            require $file;
        }

        // Fail if we didn't find any tests to run
        if (TestRegistry::isEmpty()) {
            $output->writeln(<<<MESSAGE

            No tests found in directory:
            {$path}

            {$output->bold($output->red('FAIL'))}
            MESSAGE);

            return self::FAILURE;
        }

        $printer = PrinterFactory::create($input->getOption('format'), $output);

        $runner = new TestRunner($printer, new Assert());
        $result = $runner->run(TestRegistry::iterate());

        $printer->summary($result);

        if ($result->hasFailures()) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
