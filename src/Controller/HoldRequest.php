<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;
use RCPL\Polaris\Entity\HoldRequest as Entity;
use RCPL\Polaris\Entity\Patron as Patron;

class HoldRequest extends ControllerBase {

  private $controllerData = [];

  private $patron;

  public function __construct(Client $client) {
    parent::__construct($client);
  }

  public function init(Patron $patron) {
    $instance = new static($this->client);
    return $instance->setPatron($patron);
  }

  public function setPatron(Patron $patron) {
    $this->patron = $patron;
    return $this;
  }

  public function url() {
    return $this->patron->url() . '/holdrequests';
  }

  public function create(array $values = []) {
    if (empty($values['BibID'])) {
      throw new \Exception('BibIB is required');
    }
    $values = array_merge([
      'PatronID'        => $this->patron->data()->PatronID,
      'BibID'           => $values['BibID'],
      'ItemBarcode'     => '',
      'VolumeNumber'    => '',
      'Designation'     => '',
      'PickupOrgID'     => 0,
      'IsBorrowByMail'  => 0,
      'PatronNotes'     => '',
      'ActivationDate'  => '/Date(' . date('Y-m-d\T') . '00:00:00.00)/',
      'Answer'          => '',
      'RequestID'       => '',
      'WorkstationID'   => 1,
      'UserID'          => 1,
      'RequestingOrgID' => 1,
      'TargetGUID'      => '',
    ], $values);
    return new Entity($this, $values);
  }

  public function getByType($type = 'all') {
    if (!isset($this->controllerData[$type])) {
      $result = $this->client->request()
        ->staff()
        ->public()
        ->get()
        ->simple('PatronHoldRequestsGetRows')
        ->path($this->url() . '/' . $type)
        ->send();
      foreach ($result as $hold) {
        $this->controllerData[$type][$hold->HoldRequestID] = $this->create((array) $hold);
      }
    }
    return $this->controllerData[$type];
  }

  public function get($id, $type = 'all') {
    return isset($this->controllerData[$type][$id]) ? $this->controllerData[$type][$id] : FALSE;
  }

}
