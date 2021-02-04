<?php

use PHPUnit\Framework\TestCase;

class PassiveTest extends TestCase {
  public function testUsersGet() {
    $accessToken = getenv('TEST_ACCESS_TOKEN');
    if (!$accessToken) {
      $this->markTestSkipped("pass TEST_ACCESS_TOKEN to run this test");
      return;
    }

    $executor = new \Vk\Executor();
    $response = $executor->execute(new \Vk\ApiRequest('users.get', [
      'user_ids'     => '6492,2050',
      'access_token' => $accessToken,
    ]));
    if ($response->isSuccess()) {
      $list = $response->getResponse();
      if (is_array($list) && count($list) == 2) {
        foreach ($list as $user) {
          if (!in_array($user['id'], [6492, 2050])) {
            throw new \Exception("Bad user id " . $response->getRawResponse());
          }
        }
        $this->assertTrue(true);
      } else {
        throw new \Exception("Bad response " . $response->getRawResponse());
      }
    } else {
      throw new \Exception($response->getRawResponse(), $response->getCode());
    }
  }

  public function testBachExecute() {
    $userIds = [
      1568,
      2050,
      3422,
      24512,
      25046,
      50435,
      66748,
      75791,
      92933,
      104246,
      106773,
      145488,
      162447,
      168850,
      216004,
      241945,
      261621,
      274123,
      382170,
      419200,
    ];

    $accessToken = getenv('TEST_ACCESS_TOKEN');
    if (!$accessToken) {
      $this->markTestSkipped("pass TEST_ACCESS_TOKEN to run this test");
      return;
    }

    $executor = new \Vk\Executor($accessToken);

    $results = $executor->executeBatch(array_map(function($userId) {
      return new \Vk\ApiRequest("users.get", ['user_ids' => $userId]);
    }, $userIds));

    foreach ($results as $index => $res) {
      if (!($res instanceof \Vk\ApiResponse)) {
        throw new Exception("res not ApiResponse");
      }
      $this->assertTrue($res->isSuccess(), "$index $userIds[$index] not success " . $res->getMessage());

      $expectUserId = $userIds[$index];
      $this->assertEquals($expectUserId, $res->getData()[0]['id'], $index . " not equal id");
    }

  }

  public function testResponseIsHtml() {
    $executor = new \Vk\Executor("", "5.126", "ru", 1, "https://yandex.com");
    $res      = $executor->call("users.get", []);
    $this->assertFalse($res->isSuccess());
    $this->assertEquals(500, $res->getCode());
  }

  public function testNetworkTimeout() {
    $executor = new \Vk\Executor("", "5.126", "ru", 1, "https://yandex.com:4444");

    $res = $executor->call("users.get", []);

    $this->assertFalse($res->isSuccess());
    $this->assertEquals(500, $res->getCode());
  }
}
