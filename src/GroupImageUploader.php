<?php

namespace Vk;

use Vk\Exceptions\VkException;

class GroupImageUploader extends ImageUploader {
  protected $token;

  public function __construct($token) {
    $this->token = $token;
  }

  public function getUploadServerMethod() {
    return 'appWidgets.getGroupImageUploadServer';
  }

  public function getImageSaveMethod() {
    return 'appWidgets.saveGroupImage';
  }

  /**
   * @param $id
   * @param $imageType
   * @return mixed
   * @throws VkException
   */
  public function getImageUrl($id, $imageType) {
    $result = Executor::api('appWidgets.getGroupImages', [
      'access_token' => $this->getAccessToken(),
      'image_type'   => $imageType,
      'count'        => 100,
    ]);
    if (!$result->isSuccess()) {
      throw new VkException($result->getMessage(), $result->getCode());
    } else {
      $response = $result->getResponse();
    }
    foreach ($response['items'] as $image) {
      if ($image['id'] === $id) {
        $i = array_pop($image['images']);
        return $i['url'];
      }
    }
    throw new VkException('Image not found');
  }

  protected function getAccessToken() {
    return $this->token;
  }
}
