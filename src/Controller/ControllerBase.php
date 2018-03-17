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

    public function __construct(Client $client) {
      $this->client = $client;
    }

    protected function public() {
      return $this->client->public($this);
    }

    protected function protected() {
      return $this->client->protected($this);
    }
}
