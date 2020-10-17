<?php

declare(strict_types=1);

namespace League\Flysystem\AdapterTestUtilities;

use const PHP_EOL;
use const STDOUT;
use Throwable;

trait RetryOnTestException
{
    /**
     * @var string
     */
    protected $exceptionTypeToRetryOn;

    /**
     * @var int
     */
    protected $timeoutForExceptionRetry = 0;

    protected function retryOnException(string $className, int $timout = 1): void
    {
        $this->exceptionTypeToRetryOn = $className;
        $this->timeoutForExceptionRetry = $timout;
    }

    protected function retryScenarioOnException(string $className, callable $scenario, int $timeout = 1): void
    {
        $this->retryOnException($className, $timeout);
        $this->runTestScenario($scenario);
    }

    protected function dontRetryOnException(): void
    {
        $this->exceptionTypeToRetryOn = null;
    }

    public function runTest(): void
    {
        if ($this->exceptionTypeToRetryOn === null) {
            parent::runTest();

            return;
        }

        $this->runTestScenario(
            function () {
                /* @phpstan-ignore-next-line */
                parent::runTest();
            }
        );
    }

    private function runTestScenario(callable $scenario): void
    {
        $firstTryAt = \time();
        $lastTryAt = $firstTryAt + 5;

        while (time() <= $lastTryAt) {
            try {
                $scenario();

                return;
            } catch (Throwable $exception) {
                if (get_class($exception) !== $this->exceptionTypeToRetryOn) {
                    throw $exception;
                }
                fwrite(STDOUT, 'Retrying ...' . PHP_EOL);
                sleep($this->timeoutForExceptionRetry);
            }
        }

        $this->exceptionTypeToRetryOn = null;

        if (isset($exception) && $exception instanceof Throwable) {
            throw $exception;
        }
    }
}
