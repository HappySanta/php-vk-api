<?php


namespace Vk;


class Executor
{
    protected $version;
    protected $timeout;
    protected $language;
    protected $accessToken;

    public static function api(string $method, array $params = []): ApiResponse
    {
        $e = new self();
        return $e->call($method, $params);
    }

    public static function getAccessToken($appId, $appSecret, $redirectUrl, $code): ApiResponse
    {
        $params = [
            "client_id" => $appId,
            "client_secret" => $appSecret,
            "redirect_uri" => $redirectUrl,
            "code" => $code
        ];
        $data = http_build_query($params);
        $opts = ['http' =>
            [
                'method' => 'GET',
                'timeout' => 60,
                'ignore_errors' => true
            ]
        ];
        $context = stream_context_create($opts);
        try {
            $result = file_get_contents('https://oauth.vk.com/access_token?' . $data, false, $context);
        } catch (\Exception $e) {
            $result = '';
        }
        $json = json_decode($result, true);
        $response = new ApiResponse(new ApiRequest("auth", []));
        $response->setRawResponse($result);
        if (!$json && !is_array($json)) {
            $response->setCode(500);
            return $response;
        }
        if (isset($json['access_token'])) {
            $response->setCode(200);
            $response->setResponse($json);
        } elseif (isset($json['error'])) {
            $code = $json['error'];
            $message = $json['error_description'] ?? "Unknow error";
            $response->setCode($code);
            $response->setMessage($message);
            $response->setCaptchaSig($json['error']['captcha_sid'] ?? null);
            $response->setCaptchaImg($json['error']['captcha_img'] ?? null);
        } else {
            $response->setCode(500);
        }
        return $response;
    }

    public function __construct($accessToken = null, $version = '5.126', $language = 'ru', $timeout = 600)
    {
        $this->version = $version;
        $this->timeout = $timeout;
        $this->language = $language;
        $this->accessToken = $accessToken;
    }

    public function call(string $method, array $params = []): ApiResponse
    {
        return $this->execute(new ApiRequest($method, $params));
    }

    public function execute(ApiRequest $request): ApiResponse
    {
        $params = $request->getParams();
        $params = $this->applyDefaultParams($params);
        $data = http_build_query($params);
        $opts = ['http' =>
            [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $data,
                'timeout' => $this->timeout,
                'ignore_errors' => true
            ]
        ];
        $context = stream_context_create($opts);
        $response = new ApiResponse($request);
        try {
            $result = file_get_contents('https://api.vk.com/method/' . $request->getMethod(), false, $context);
        } catch (\Exception $e) {
            $result = '';
            $response->setCode($e->getCode());
            $response->setMessage($e->getMessage());
        }
        $json = json_decode($result, true);
        $response->setRawResponse($result);
        if (!$json && !is_array($json)) {
            $response->setCode(501);
            $response->setMessage("Response not exist or not array");
            return $response;
        }
        if (isset($json['response'])) {
            $response->setCode(200);
            $response->setResponse($json['response']);
            if (isset($json['execute_errors'])) {
                $response->setExecuteErrors($json['execute_errors']);
            }
        } elseif (isset($json['error'])) {
            $code = $json['error']['error_code'];
            $message = $json['error']['error_msg'];
            $response->setCode((int)$code);
            $response->setMessage($message);
            $response->setCaptchaSig($json['error']['captcha_sid'] ?? null);
            $response->setCaptchaImg($json['error']['captcha_img'] ?? null);
        } else {
            $response->setCode(502);
            $response->setMessage("Response has invalid format");
        }
        return $response;
    }

    private function applyDefaultParams($params)
    {
        if (!isset($params['v'])) {
            $params['v'] = $this->version;
        }
        if (!isset($params['lang'])) {
            $params['lang'] = $this->language;
        }
        if (!isset($params['access_token']) && $this->accessToken) {
            $params['access_token'] = $this->language;
        }
        return $params;
    }

    public function canRetryLaterWithCode($code)
    {
        return self::isSoftErrorCode($code);
    }

    public static function isSoftErrorCode($code)
    {
        return in_array($code, [
            0,
            1,
            6,
            9,
            10,
            18
        ]);
    }
}