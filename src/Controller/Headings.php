<?php

namespace RCPL\Polaris\Controller;

class Headings extends ControllerBase {
  public function search($qualifierName = 'SU', array $params = []) {
    $defaults = [
      'startpoint' => '1',
      'numterms' => '50',
      'preferredpos' => '1'
    ];
    return $this->client->request()
      ->public()
      ->path('search/headings/' . $qualifierName)
      ->query(array_merge($defaults, $params))
      ->get()
      ->send();
  }
}
