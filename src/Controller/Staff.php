<?php

namespace RCPL\Polaris\Controller;

class Staff extends ControllerBase {

  private $auth;

  public function auth() {
    if (!isset($this->auth)) {
      $this->auth = $this->client->protected()->post('authenticator/staff');
    }
    return $this->auth;
  }

}
