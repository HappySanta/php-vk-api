<?php

use PHPUnit\Framework\TestCase;

class PassiveTest extends TestCase {
  public function testUsersGet() {
    $executor = new \Vk\Executor();
    $response = $executor->execute(new \Vk\ApiRequest('users.get', [
      'user_ids' => '6492,2050',
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

//    public function testSnippetUpload() {
//        $accessToken = '6128472c6172c3b5eb1c6b5f7d9714632527a';
//        $ownerId = 165679022;
//        $path = 'sn.png';
//
//        $result = \Vk\SnippetImageUploader::upload($accessToken, $ownerId, $path);
//        print_r($result);
//    }

//    public function testImageUpload() {
//        $token = "270b2d972f25cc0a7893.....26eb44957c610ed1402725a5a2ae3";
//
//        $uploader = new \Vk\GroupImageUploader($token);
//
//        $file = "/Users/i.nedzvetskiy/Downloads/xUvX2Ktzu18.jpg";
//
//        $id = $uploader->uploadImage($file);
//
//        $this->assertTrue( is_string($id) && mb_strlen($id) >=1 );
//
//        $url = $uploader->getImageUrl($id, "50x50");
//
//        $this->assertTrue( is_string($url) && mb_strlen($url) >=20 );
//    }
}
