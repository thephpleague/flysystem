<?php

declare(strict_types=1);

namespace League\Flysystem\AdapterTestUtilities;

use Throwable;

use const PHP_EOL;
use const STDOUT;

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

    protected function retryOnException(string $className, int $timout): void
    {
        $this->exceptionTypeToRetryOn = $className;
        $this->timeoutForExceptionRetry = $timout;
    }

    public function runTest(): void
    {
        $firstTryAt = \time();
        $lastTryAt = $firstTryAt + 5;

        if ($this->exceptionTypeToRetryOn === null) {
            parent::runTest();
            return;
        }

        while (time() <= $lastTryAt) {
            try {
                /* @phpstan-ignore-next-line */
                parent::runTest();
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
