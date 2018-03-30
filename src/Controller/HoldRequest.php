<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;

class HoldRequest extends ControllerBase {

  private $controllerData = [];

  private $patron;

  private $holdData = [];

  public function __construct(Client $client, Patron $patron = NULL, array $values = []) {
    parent::__construct($client);
    if (!is_null($patron)) {
      $this->patron = $patron;
    }
    if (!empty($values)) {
      $this->holdData = $values;
    }
  }

  public function init(Patron $patron, array $values = []) {
    return new static($this->client, $patron, $values);
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
    return new static($this->client, $this->patron, $values);
  }

  public function getByType($type = 'all') {
    if (!isset($this->controllerData[$type])) {
      $endpoint = 'patron/' . $this->patron->barcode  . '/holdrequests/' . $type;
      $result = $this->client->request()
        ->staff()
        ->public()
        ->get()
        ->simple('PatronHoldRequestsGetRows')
        ->path($endpoint)
        ->send();
      foreach ($result as $hold) {
        $this->controllerData[$type][$hold->HoldRequestID] = $this->init($this->patron, (array) $hold);
      }
    }
    return $this->controllerData[$type];
  }

  public function get($id, $type = 'all') {
    return isset($this->controllerData[$type][$id]) ? $this->controllerData[$type][$id] : FALSE;
  }

  public function suspendUntil($int = 'P1D', array $values = []) {
    $date = new \DateTime();
    $date->add(new \DateInterval($int));
    $this->suspend($date, $values);
  }

  public function activate(array $values = []) {
    $date = new \DateTime();
    $date->add(new \DateInterval('PT1S'));
    return $this->changeStatus('active', $date, $values); 
  }

  public function suspend(\DateTime $date, array $values = []) {
    return $this->changeStatus('inactive', $date, $values);
  }

  private function changeStatus($status, \DateTime $date, array $values = []) {
    $endpoint = 'patron/' . $this->patron->barcode 
      . '/holdrequests/' 
      . $this->holdData['HoldRequestID'] 
      . '/' . $status;
    return $this->client->request()
      ->public()
      ->staff()
      ->config([
        'json' => [
          'UserID' => 1,
          'ActivationDate' => $date->format(\DateTime::ISO8601),
        ]
      ])
      ->path($endpoint)
      ->put()
      ->send();
  }

  public function save() {
    if (!empty($this->holdData['HoldRequestID'])) {
      return;
    }
    return $this->client->request()
      ->config(['json' => $this->holdData])
      ->public()
      ->post()
      ->path('holdrequest')
      ->staff()
      ->send();
  }
}
