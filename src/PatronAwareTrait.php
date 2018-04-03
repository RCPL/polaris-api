<?php

namespace RCPL\Polaris;

use RCPL\Polaris\Entity\Patron;


trait PatronAwareTrait {

  /**
   * @var Patron;
   */
  private $patron;

  /**
   * @param Patron $patron
   * @return mixed
   */
  public function init(Patron $patron) {
    $instance = new static($this->client);
    return $instance->setPatron($patron);
  }

  /**
   * @param Patron $patron
   * @return $this
   */
  public function setPatron(Patron $patron) {
    $this->patron = $patron;
    return $this;
  }

  /**
   * @return Patron;
   */
  public function getPatron() {
    return $this->patron;
  }
}
