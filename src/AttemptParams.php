<?php

namespace Vk;

class AttemptParams {
  public int $startDelay = 100;
  public int $maxAttempt = 5;
  public bool $retryWith5xxError = false;
  public $callbackOnRetry;

  public static function defaultParams(): self {
    return new self();
  }

  /**
   * @param int $startDelay
   * @return AttemptParams
   */
  public function setStartDelay(int $startDelay): AttemptParams {
    $this->startDelay = $startDelay;
    return $this;
  }

  /**
   * @param int $maxAttempt
   * @return AttemptParams
   */
  public function setMaxAttempt(int $maxAttempt): AttemptParams {
    $this->maxAttempt = $maxAttempt;
    return $this;
  }

  /**
   * @param bool $retryWith5xxError
   * @return AttemptParams
   */
  public function setRetryWith5xxError(bool $retryWith5xxError): AttemptParams {
    $this->retryWith5xxError = $retryWith5xxError;
    return $this;
  }

  /**
   * @param mixed $callbackOnRetry
   * @return AttemptParams
   */
  public function setCallbackOnRetry(callable $callbackOnRetry) {
    $this->callbackOnRetry = $callbackOnRetry;
    return $this;
  }
}
