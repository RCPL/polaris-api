<?php

namespace RCPL\Polaris\Controller;

class Organization extends ControllerBase {

  public function get($type = 'all') {
    return $this->client->request()
      ->public()
      ->path('organizations/' . $type)
      ->get()
      ->send();
  }

}
