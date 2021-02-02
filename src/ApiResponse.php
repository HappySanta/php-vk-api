<?php

namespace Vk;

class ApiResponse {
  protected $request;
  protected $response = null;
  protected $executeErrors = null;
  protected $rawResponse = null;
  protected $message = null;
  protected $code = 0;
  protected $captchaSig = null;
  protected $captchaImg = null;

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
   * @deprecated
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
  public function setRawResponse($raw) {
    $this->rawResponse = $raw;
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

}
