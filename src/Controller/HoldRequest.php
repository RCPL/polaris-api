<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;
use RCPL\Polaris\Entity\HoldRequest as Entity;
use RCPL\Polaris\PatronAwareTrait;

class HoldRequest extends ControllerBase {

  use PatronAwareTrait;

  private $data = [];

  public function __construct(Client $client) {
    parent::__construct($client);
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
      'WorkstationID'   => $this->client->params->get('WORKSTATION_ID'),
      'UserID'          => 1,
      'RequestingOrgID' => 1,
      'TargetGUID'      => '',
    ], $values);
    return new Entity($this, $values);
  }

  public function getByType($type = 'all') {
    if (!isset($this->data[$type])) {
      $result = $this->client->request()
        ->staff()
        ->public()
        ->get()
        ->simple('PatronHoldRequestsGetRows')
        ->path($this->url() . '/' . $type)
        ->send();
      foreach ($result as $hold) {
        $this->data[$type][$hold->HoldRequestID] = $this->create((array) $hold);
      }
    }
    return $this->data[$type];
  }

  public function get($id, $type = 'all') {
    return isset($this->data[$type][$id]) ? $this->data[$type][$id] : FALSE;
  }

}
