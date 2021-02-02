<?php

namespace Vk;

class AppImageUploader extends ImageUploader {
  protected $token;

  public function __construct($token) {
    $this->token = $token;
  }

  public function getUploadServerMethod() {
    return 'appWidgets.getAppImageUploadServer';
  }

  public function getImageSaveMethod() {
    return 'appWidgets.saveAppImage';
  }

  protected function getAccessToken() {
    return $this->token;
  }
}
