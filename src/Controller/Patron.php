<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;

class Patron extends ControllerBase {
  public function __construct(Client $client, $patron_barcode = NULL) {
    $this->barcode = $patron_barcode;
    parent::__construct($client);
  }

  public function get($patron_barcode) {
    return new static($this->client, $patron_barcode);
  }

  public function data() {
    $endpoint = 'patron/' . $this->barcode . '/basicdata?addresses=1';
    //return self::getCallAPI($endpoint, NULL, FALSE, FALSE, TRUE);
    return $this->client->staff->public()->get($endpoint);
  }

  public function holdRequests($type = 'all') {
    $endpoint = 'patron/' . $this->barcode  . '/holdrequests/' . $type;
    // return self::getCallAPI($endpoint, NULL, NULL, FALSE, TRUE);
    return $this->client->staff->public()->get($endpoint);
  }

  public function itemsOut($type = 'all') {
    $endpoint = 'patron/' . $this->barcode  . '/itemsout/' . $type;
    // return self::getCallAPI($endpoint, NULL, NULL, FALSE, TRUE);
    return $this->client->staff->public()->get($endpoint);
  }

  public function preferences() {
    $endpoint = 'patron/' . $this->barcode . '/preferences';
    // return self::getCallAPI($endpoint, NULL, NULL, FALSE, TRUE);
    return $this->client->staff->public()->get($endpoint);
  }

  public function account() {
    $endpoint = 'patron/' . $this->barcode  . '/account/outstanding';
    // return self::getCallAPI($endpoint, NULL, NULL, FALSE, TRUE);
    return $this->client->staff->public()->get($endpoint);
  }

  public function titleLists() {
    $endpoint = 'patron/' . $this->barcode . '/patronaccountgettitlelists';
    // return self::getCallAPI($endpoint, NULL, NULL, FALSE, TRUE, TRUE);
    return $this->client->staff->public()->get($endpoint);
  }
}
