<?php

declare(strict_types=1);

namespace Inphest\Internal\Printer;

use Inphest\Internal\Console\Io\OutputInterface;
use Inphest\Internal\Result\FailingTest;
use Inphest\Internal\Result\TestResultInterface;
use Inphest\Internal\Result\TestSuiteResult;
use Inphest\Internal\TestCase;
use Inphest\Internal\TimeFormatter;

final class PrettyPrinter implements PrinterInterface
{
    private OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function test(TestCase $test): void
    {
        $name = $this->output->bold($test->getLabel());

        $this->output->writeln("\n{$name}");
    }

    public function success(TestResultInterface $result): void
    {
        $tick = $this->output->green('✔');
        $name = $result->getLabel();

        $this->output->writeln("  {$tick} {$name}");
    }

    public function failure(FailingTest $result): void
    {
        $cross = $this->output->red('✘');
        $name = $this->output->bold($result->getLabel());

        $this->output->writeln(
            <<<MESSAGE
              {$cross} {$name}
                  {$result->getFailureReason()->getMessage()}
            MESSAGE
        );
    }

    public function summary(int $timeTaken, TestSuiteResult $result): void
    {
        $time = TimeFormatter::format($timeTaken);
        $successOrFail = $result->hasFailures()
            ? $this->output->bold($this->output->red('FAIL'))
            : $this->output->bold($this->output->green('SUCCESS'));

        $this->output->writeln(
            <<<MESSAGE

            {$successOrFail}
            Ran {$result->count()} tests in {$time}
            MESSAGE
        );
    }
}
