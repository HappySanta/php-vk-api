<?php

namespace Vk;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Response;

class Executor {
  protected $version;
  protected $timeout;
  protected $language;
  protected $accessToken;
  protected Client $guzzleClient;

  public function __construct($accessToken = null, $version = '5.126', $language = 'ru', $timeout = 600, $baseUrl = 'https://api.vk.com/method/') {
    $client             = new Client([
      'base_uri' => $baseUrl,
      'timeout'  => $timeout,
    ]);
    $this->guzzleClient = $client;

    $this->version     = $version;
    $this->timeout     = $timeout;
    $this->language    = $language;
    $this->accessToken = $accessToken;
  }

  /**
   * @param string $method
   * @param array  $params
   * @return ApiResponse
   * @throws Exception
   */
  public static function api(string $method, array $params = []): ApiResponse {
    $e = new self();
    return $e->call($method, $params);
  }

  /**
   * @param string $method
   * @param array  $params
   * @return ApiResponse
   * @throws Exception
   */
  public function call(string $method, array $params = []): ApiResponse {
    return $this->execute(new ApiRequest($method, $params));
  }

  /**
   * @param array         $requestList
   * @param AttemptParams $params
   * @return array
   * @throws Exception
   */
  public function attemptExecuteBatch(array $requestList, AttemptParams $params): array {
    $results = $this->executeBatch($requestList);

    return array_map(function(ApiResponse $response) use ($params) {
      if ($response->isSuccess()) {
        return $response;
      }
      $exp = $params->startDelay;

      $attempt = 0;
      while ($attempt++ < $params->maxAttempt
        && !$response->isSuccess()
        && $response->canBeRetry($params->retryWith5xxError)) {
        if ($params->callbackOnRetry) {
          $fn = $params->callbackOnRetry;
          $fn($response);
        }
        usleep($exp);
        $response = $this->execute($response->getRequest());
        $exp      *= 2;
      }

      return $response;
    }, $results);
  }

  /**
   * @param ApiRequest[] $requestList
   * @return ApiResponse[]
   * @throws Exception
   */
  public function executeBatch(array $requestList): array {
    $promises = array_map(function(ApiRequest $request) {
      $params = $request->getParams();
      $params = $this->applyDefaultParams($params);
      return $this->guzzleClient->postAsync($request->getMethod(), [
        'form_params' => $params,
      ]);
    }, $requestList);

    $responses = Utils::settle($promises)->wait();

    $i = 0;
    return array_map(function($data) use (&$i, $requestList) {
      $index       = $i++;
      $request     = $requestList[$index];
      $apiResponse = new ApiResponse($request);
      if ($data['state'] === 'fulfilled') {
        $response = $data['value'];
        if (!($response instanceof Response)) {
          throw new \Exception("So, this is impossible exception #5456, but you got it write to vk.com/in for more info");
        }
        $status  = $response->getStatusCode();
        $payload = $response->getBody()->getContents();
        $apiResponse->setRawResponse($response);
        $apiResponse->setCode($status);
        return $this->processPayload($payload, $apiResponse);
      } else {
        $exception = $data['reason'];
        if ($exception instanceof RequestException) {
          $apiResponse->setCode(500);
          $apiResponse->setMessage($exception->getMessage());
          $res = $exception->getResponse();
          if ($res) {
            $apiResponse->setRawResponse($res);
          }
          return $apiResponse;
        } elseif ($exception instanceof \RuntimeException) {
          $apiResponse->setCode(500);
          $apiResponse->setMessage($exception->getMessage());
          $apiResponse->setRawResponse(null);
          return $apiResponse;
        } else {
          throw new \Exception("So, this is impossible exception #5457, but you got it write to vk.com/in for more info");
        }
      }
    }, $responses);
  }

  /**
   * @param ApiRequest $request
   * @return ApiResponse
   * @throws Exception
   */
  public function execute(ApiRequest $request): ApiResponse {
    return $this->executeBatch([$request])[0];
  }

  /**
   * @param ApiRequest    $request
   * @param AttemptParams $params
   * @return ApiResponse
   * @throws Exception
   */
  public function attemptExecute(ApiRequest $request, AttemptParams $params): ApiResponse {
    return $this->attemptExecuteBatch([$request], $params)[0];
  }

  private function applyDefaultParams($params): array {
    if (!isset($params['v'])) {
      $params['v'] = $this->version;
    }
    if (!isset($params['lang'])) {
      $params['lang'] = $this->language;
    }
    if (!isset($params['access_token']) && $this->accessToken) {
      $params['access_token'] = $this->accessToken;
    }
    return $params;
  }

  public static function getAccessToken($appId, $appSecret, $redirectUrl, $code): ApiResponse {
    $params  = [
      "client_id"     => $appId,
      "client_secret" => $appSecret,
      "redirect_uri"  => $redirectUrl,
      "code"          => $code,
    ];
    $data    = http_build_query($params);
    $opts    = ['http' =>
                  [
                    'method'        => 'GET',
                    'timeout'       => 60,
                    'ignore_errors' => true,
                  ],
    ];
    $context = stream_context_create($opts);
    try {
      $result = file_get_contents('https://oauth.vk.com/access_token?' . $data, false, $context);
    } catch (Exception $e) {
      $result = '';
    }
    $json     = json_decode($result, true);
    $response = new ApiResponse(new ApiRequest("auth", []));
    $response->setRawResponse(null);
    if (!$json && !is_array($json)) {
      $response->setCode(500);
      return $response;
    }
    if (isset($json['access_token'])) {
      $response->setCode(200);
      $response->setResponse($json);
    } elseif (isset($json['error'])) {
      $code    = $json['error'];
      $message = $json['error_description'] ?? "Unknown error";
      $response->setCode($code);
      $response->setMessage($message);
      $response->setCaptchaSig($json['error']['captcha_sid'] ?? null);
      $response->setCaptchaImg($json['error']['captcha_img'] ?? null);
    } else {
      $response->setCode(500);
    }
    return $response;
  }

  public function canRetryLaterWithCode($code) {
    return self::isSoftErrorCode($code);
  }

  public static function isSoftErrorCode($code) {
    return in_array($code, [
      0,
      1,
      6,
      9,
      10,
      18,
    ]);
  }

  public function processPayload(string $payload, ApiResponse $apiResponse): ApiResponse {
    $json = json_decode($payload, true);
    if (!$json && !is_array($json)) {
      $apiResponse->setCode(500);
      $apiResponse->setMessage("Response not exist or not array");
      return $apiResponse;
    }

    if (isset($json['response'])) {
      $apiResponse->setCode(200);
      $apiResponse->setResponse($json['response']);
      if (isset($json['execute_errors'])) {
        $apiResponse->setExecuteErrors($json['execute_errors']);
      }
    } elseif (isset($json['error'])) {
      $code    = $json['error']['error_code'];
      $message = $json['error']['error_msg'];
      $apiResponse->setIsApiError(true);
      $apiResponse->setCode((int)$code);
      $apiResponse->setMessage($message);
      $apiResponse->setCaptchaSig($json['error']['captcha_sid'] ?? null);
      $apiResponse->setCaptchaImg($json['error']['captcha_img'] ?? null);
    } else {
      $apiResponse->setCode(502);
      $apiResponse->setMessage("Response has invalid format");
    }
    return $apiResponse;
  }
}
