<?php

namespace Vk;

use Psr\Http\Message\ResponseInterface;

class ApiResponse {
  protected $request;
  protected $response = null;
  protected $executeErrors = null;
  protected $rawResponse = null;
  protected $message = null;
  protected $code = 0;
  protected $captchaSig = null;
  protected $captchaImg = null;
  /**
   * Признак того что ответ получен и это именно шибка от апи
   * а не ошибка сети или что-то иное
   * @var bool
   */
  protected bool $isApiError = false;

  public function __construct(ApiRequest $request) {
    $this->request = $request;
  }

  /**
   * @return ApiRequest
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * @param ApiRequest $request
   */
  public function setRequest($request) {
    $this->request = $request;
  }

  /**
   * @return mixed
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * @param mixed $response
   */
  public function setResponse($response) {
    $this->response = $response;
  }

  /**
   * @return mixed
   */
  public function getData() {
    return $this->response;
  }

  /**
   * @return mixed
   */
  public function getRawResponse() {
    return $this->rawResponse;
  }

  /**
   * @param mixed $raw
   */
  public function setRawResponse(?ResponseInterface $raw) {
    $header = [
      '> method: ' . $this->request->getMethod(),
      '> params: ' . $this->request->getParamsAsStringSafety(),

    ];

    if ($raw) {
      $header[] = '< status: ' . $raw->getStatusCode();
      $header[] = '< protocol: ' . $raw->getProtocolVersion();
      foreach ($raw->getHeaders() as $name => $value) {
        $header[] = '< ' . $name . ": " . implode(', ', $value);
      }
      $payload  = $raw->getBody()->getContents();
      $header[] = '< ' . $payload;
    }
    $this->rawResponse = implode(" \n", $header);
  }

  /**
   * @return mixed
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * @param mixed $code
   */
  public function setCode($code) {
    $this->code = $code;
  }

  /**
   * @return string
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * @param string $message
   */
  public function setMessage($message) {
    $this->message = $message;
  }

  public function isSuccess() {
    return $this->code === 200;
  }

  /**
   * @return null|array
   */
  public function getExecuteErrors() {
    return $this->executeErrors;
  }

  /**
   * @param null $executeErrors
   */
  public function setExecuteErrors($executeErrors) {
    $this->executeErrors = $executeErrors;
  }

  public function hasExecuteErrors() {
    return $this->executeErrors !== null;
  }

  public function setCaptchaSig($value) {
    $this->captchaSig = $value;
  }

  public function setCaptchaImg($value) {
    $this->captchaImg = $value;
  }

  /**
   * @return bool
   */
  public function isApiError(): bool {
    return $this->isApiError;
  }

  /**
   * @param bool $isApiError
   */
  public function setIsApiError(bool $isApiError): void {
    $this->isApiError = $isApiError;
  }

  public function canBeRetry($with5xx = false): bool {
    if (Executor::isSoftErrorCode($this->code)) {
      return true;
    }
    if ($with5xx && $this->code >= 500 && $this->code <= 599) {
      return true;
    }
    return false;
  }

}
