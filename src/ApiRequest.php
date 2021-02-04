<?php

namespace Vk;

class ApiRequest {
  protected $method = '';
  protected $params = [];

  public function __construct(string $method = '', array $params = []) {
    $this->method = $method;
    $this->params = $params;
  }

  /**
   * @return string
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * @param string $method
   */
  public function setMethod($method) {
    $this->method = $method;
  }

  /**
   * @return array
   */
  public function getParams() {
    return $this->params;
  }

  /**
   * @param array $params
   */
  public function setParams($params) {
    $this->params = $params;
  }

  public function getParamsAsStringSafety(): string {
    $params = $this->getParams();
    if (!is_array($params)) {
      return 'PARAMS NOT SET';
    }
    $exclude = [
      'access_token' => true,
    ];
    $result  = [];
    foreach ($params as $key => $value) {
      if (!$exclude[$key]) {
        $result[] = $key . "=" . (string)$value;
      }
    }
    return implode($result);
  }

}
