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

  public function urlILL() {
    return $this->patron->url() . '/illrequests';
  }

  public function create(array $values = []) {
    if (empty($values['BibID'])) {
      throw new \Exception('BibID is required');
    }
    if (empty($values['PickupOrgID'])) {
      $values['PickupOrgID'] = 0;
    }
    $values = array_merge([
      'PatronID'        => $this->patron->data()->PatronID,
      'BibID'           => $values['BibID'],
      'ItemBarcode'     => '',
      'VolumeNumber'    => '',
      'Designation'     => '',
      'PickupOrgID'     => $values['PickupOrgID'],
      'IsBorrowByMail'  => 0,
      'PatronNotes'     => '',
      'ActivationDate'  => '/Date(' . ((int) round(microtime(TRUE) * 1000) - 18000000) . ')/',
      'Answer'          => '',
      'RequestID'       => '',
      'WorkstationID'   => $this->client->params->get('WORKSTATION_ID'),
      'UserID'          => 1,
      'RequestingOrgID' => 1,
      'TargetGUID'      => '',
    ], $values);
    return new Entity($this, $values);
  }

  public function createILL(array $values = []) {
    if (is_null($values['BibRecordID'])) {
      throw new \Exception('BibRecordID is required');
    }
    if (empty($values['PickupOrgID'])) {
      $values['PickupOrgID'] = 0;
    }
    // Let's rename a couple of portions of this so that it can be correctly
    // displayed alongside holds.
    $values['ActivationDate'] = $values['CreationDate'];
    $values['PickupByDate'] = $values['NeedByDate'];
    $values = array_merge([
      'PatronID'          => $this->patron->data()->PatronID,
      'BibID'             => $values['BibRecordID'],
      'ItemBarcode'       => '',
      'VolumeNumber'      => '',
      'Designation'       => '',
      'PickupOrgID'       => $values['PickupOrgID'],
      'IsBorrowByMail'    => 0,
      'PatronNotes'       => '',
      'Answer'            => '',
      'RequestID'         => '',
      'WorkstationID'     => $this->client->params->get('WORKSTATION_ID'),
      'UserID'            => 1,
      'RequestingOrgID'   => 1,
      'TargetGUID'        => '',
      'StatusID'          => 6, // Status 6 = held. Disallows changes.
      'StatusDescription' => $values['Status'],
      'HoldRequestID'     => $values['ILLRequestID'],
      'QueuePosition'     => 1,
      'QueueTotal'        => 1,
      'PickupBranchName'  => $values['PickupBranch'],
      'FormatDescription' => $values['Format'],
    ], $values);
    return new Entity($this, $values);
  }

  public function getByType($type = 'all') {
    if (!isset($this->data[$type])) {
      $result = $this->client->createRequest()
        ->staff()
        ->public()
        ->get()
        ->simple('PatronHoldRequestsGetRows')
        ->path($this->url() . '/' . $type)
        ->send();
      foreach ($result as $hold) {
        // Adjust to American spelling of "canceled".
        $hold->StatusDescription = str_replace('Cancelled', 'Canceled', $hold->StatusDescription);
        $this->data[$type][$hold->HoldRequestID] = $this->create((array) $hold);
      }
    }
    if (!isset($this->data[$type])) {
      return [];
    }
    return $this->data[$type];
  }

  public function getByTypeILL($type = 'all') {
    $this->data[$type] = []; // Empty this out otherwise we end up joining ILLs
    // ...and holds together here.
    $result = $this->client->createRequest()
      ->staff()
      ->public()
      ->get()
      ->simple('PatronILLRequestsGetRows')
      ->path($this->urlILL() . '/' . $type)
      ->send();
    foreach ($result as $hold) {
      // Adjust to American spelling of "canceled".
      $hold->Status = str_replace('Cancelled', 'Canceled', $hold->Status);
      $this->data[$type][$hold->ILLRequestID] = $this->createILL((array) $hold);
    }
    if (is_null($result) || empty($result)) {
      return [];
    }
    return $this->data[$type];
  }

  public function get($id, $type = 'all') {
    $data = $this->getByType($type);
    return isset($data[$id]) ? $data[$id] : FALSE;
  }

}
