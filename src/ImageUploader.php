<?php

namespace Vk;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use Vk\Exceptions\FileNotFoundException;
use Vk\Exceptions\VkException;

abstract class ImageUploader {
  /**
   * @param $path
   * @return mixed
   * @throws FileNotFoundException
   * @throws VkException
   * @throws GuzzleException
   */
  public function uploadImage($path) {
    $this->validate($path);
    $imageType       = $this->getVkImageType($path);
    $uploadServer    = $this->getUploadServer($imageType);
    $saveImageParams = $this->sendImageToVk($uploadServer, $path);
    return $this->saveImageAtVk($saveImageParams);
  }

  /**
   * @param $path
   * @throws FileNotFoundException
   */
  private function validate($path) {
    if (!file_exists($path)) {
      throw new FileNotFoundException();
    }
  }

  protected function getVkImageType($path) {
    $imageSize = getimagesize($path);
    $size      = $imageSize[0] / 3 . 'x' . $imageSize[1] / 3;
    return $size;
  }

  /**
   * @param $imageType
   * @return mixed
   * @throws VkException
   */
  protected function getUploadServer($imageType) {
    return $this->getUploadServerRequest([
      'access_token' => $this->getAccessToken(),
      'image_type'   => $imageType,
    ]);
  }

  /**
   * @param $params
   * @return mixed
   * @throws VkException
   */
  protected function getUploadServerRequest($params) {
    $response = Executor::api($this->getUploadServerMethod(), $params);
    if (!$response->isSuccess()) {
      throw new VkException($response->getMessage(), $response->getCode());
    } else {
      $response = $response->getData();
    }
    if (empty($response['upload_url'])) {
      throw new VkException('Empty upload_url ' . $this->getUploadServerMethod() . ' ' . json_encode($response->getRawResponse()));
    }
    return $response['upload_url'];
  }

  abstract protected function getUploadServerMethod();

  abstract protected function getAccessToken();

  /**
   * @param $uploadServer
   * @param $path
   * @return mixed
   * @throws VkException
   * @throws GuzzleException
   */
  private function sendImageToVk($uploadServer, $path) {
    $client    = new Client();
    $multipart = new MultipartStream([
      [
        'name'     => $this->getFileNameInPostRequest(),
        'contents' => fopen($path, 'r'),
      ],
    ]);
    $request   = new Request(
      'POST',
      $uploadServer,
      [],
      $multipart
    );
    $response  = $client->send($request);
    $response  = json_decode($response->getBody()->getContents(), true);
    if (empty($response['hash'])) {
      throw new VkException('Empty hash on image uploading response');
    }
    if (empty($response['image']) && empty($response['photo'])) {
      throw new VkException('Empty image or photo on uploading response');
    }
    return $response;
  }

  protected function getFileNameInPostRequest() {
    return 'file';
  }

  /**
   * @param $saveImageParams
   * @return mixed
   * @throws VkException
   */
  protected function saveImageAtVk($saveImageParams) {
    $response = $this->saveImage($saveImageParams);
    return $response['id'];
  }

  /**
   * @param $saveImageParams
   * @return mixed
   * @throws VkException
   */
  protected function saveImage($saveImageParams) {
    $saveImageParams['access_token'] = $this->getAccessToken();
    $result                          = Executor::api($this->getImageSaveMethod(), $saveImageParams);
    if (!$result->isSuccess()) {
      throw new VkException($result->getMessage(), $result->getCode());
    } else {
      $response = $result->getData();
      return $response;
    }
  }

  abstract protected function getImageSaveMethod();
}
