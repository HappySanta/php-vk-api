<?php

use PHPUnit\Framework\TestCase;

class NegativeTest extends TestCase {
  public function testMessagesSend() {
    $executor = new \Vk\Executor();
    $response = $executor->execute(new \Vk\ApiRequest('messages.send', ['message' => 'test']));
    if ($response->isSuccess()) {
      throw new \Exception("Wtf? correct response? " . $response->getRawResponse());
    } else {
      if ($response->getCode() === 5) {
        $this->assertTrue(true);
      } else {
        throw new \Exception($response->getRawResponse());
      }
    }
  }

  public function testAuthError() {
    $appId       = 43251123;
    $appSecret   = "AFwetrvasfawer";
    $redirectUrl = "https://mysite.com/auth";
    $code        = "caefrvrtsvakmcaoer";

    $response = \Vk\Executor::getAccessToken($appId, $appSecret, $redirectUrl, $code);
    if ($response->isSuccess()) {
      throw new \Exception("Wtf? correct response? " . $response->getRawResponse());
    } else {
      $code    = $response->getCode(); //int
      $message = $response->getMessage(); //string
      $this->assertEquals($message, "client_id is blocked");
      $this->assertEquals($code, "invalid_client");
    }
  }
}
