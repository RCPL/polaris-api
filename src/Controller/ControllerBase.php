<?php

namespace RCPL\Polaris\Controller;

use Zend\Stdlib\Parameters;
use RCPL\Polaris\Client;

class ControllerBase {

  /**
   * Polaris Client.
   *
   * @var Client
   */
  protected $client;

  /**
   * Endpoint urls and parameters.
   *
   * @var Parameters;
   */
  protected $endpoints;

  /**
   * ControllerBase constructor.
   * @param Client $client
   */
  public function __construct(Client $client) {
    $this->client = $client;
  }

  /**
   * Return a url encoded string.
   *
   * @param $string
   * @return string
   */
  protected function encode($string) {
    return urlencode($string);
  }

}
