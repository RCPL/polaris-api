<?php

namespace RCPL\Polaris\Entity;

use RCPL\Polaris\Controller\ControllerBase;

class EntityBase {

  /**
   * @var ControllerBase
   */
  protected $controller;

  /**
   * @var \RCPL\Polaris\Client
   */
  protected $client;

  /**
   * @var array
   */
  protected $data = [];

  /**
   * EntityBase constructor.
   *
   * @param ControllerBase $controller
   * @param array $data
   */
  public function __construct(ControllerBase $controller, array $data = []) {
    $this->controller = $controller;
    $this->client = $this->controller->client();
    $this->data = $data;
  }

  /**
   * @return string
   */
  public function url() {
    return $this->controller->url();
  }

  /**
   * @return object
   */
  public function getData() {
    return (object) $this->data;
  }

}
