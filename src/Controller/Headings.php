<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;
use RCPL\Polaris\Utility\Parameters;

class Headings extends ControllerBase {

  /**
   * @var Parameters
   */
  private $params;

  public function __construct(Client $client) {
    parent::__construct($client);
    $this->params = new Parameters([
      'numterms' => '50',
      'preferredpos' => '1'
    ]);
  }

  public function author($text, array $params = []) {
    return $this->text($text)->search('AU', $params);
  }

  public function series($text, array $params = []) {
    return $this->text($text)->search('SE', $params);
  }

  public function subject($text, array $params = []) {
    return $this->text($text)->search('SU', $params);
  }

  public function title($text, array $params = []) {
    return $this->text($text)->search('TI', $params);
  }

  public function limit($numterms) {
    $this->params->set('numterms', $numterms);
    return $this;
  }

  private function text($text) {
    $this->params->set('startpoint', $text);
    return $this;
  }

  public function search($qualifierName = 'SU', array $params = []) {
    $params = $this->params->merge($params);
    return $this->client->request()
      ->public()
      ->simple('HeadingsSearchRows')
      ->path('search/headings/' . $qualifierName)
      ->query($params->toArray())
      ->get()
      ->send();
  }

}
