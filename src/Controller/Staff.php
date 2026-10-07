<?php

namespace RCPL\Polaris\Controller;

class Staff extends ControllerBase {

  private $auth;

  public function auth(array $config = []) {
    if (!isset($this->auth)) {
      $this->auth = $this->client->createRequest()
        ->protected()
        ->path('authenticator/staff')
        ->post()
        ->config($config)
        ->send();
    }
    return $this->auth;
  }

}
