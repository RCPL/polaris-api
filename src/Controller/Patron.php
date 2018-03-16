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

  public function authenticate($password) {
    $endpoint = 'authenticator/patron';
    // TODO: REFACTOR
    $config = [
      'json' => [
        'Barcode' => $this->barcode,
        'Password' => $password,
      ],
    ];

    return $this->client->request()
      ->public()
      ->staff()
      ->path('authenticator/patron')
      ->config($config)
      ->post()
      ->send();
  }

  public function data() {
    $endpoint = 'patron/' . $this->barcode . '/basicdata';
    $query = [
      'addresses' => 1
    ];
    return $this->request($endpoint, $query);
  }

  public function holdRequests($type = 'all') {
    $endpoint = 'patron/' . $this->barcode  . '/holdrequests/' . $type;
    return $this->request($endpoint);
  }

  public function itemsOut($type = 'all') {
    $endpoint = 'patron/' . $this->barcode  . '/itemsout/' . $type;
    return $this->request($endpoint);
  }

  public function preferences() {
    $endpoint = 'patron/' . $this->barcode . '/preferences';
    return $this->request($endpoint);
  }

  public function account() {
    $endpoint = 'patron/' . $this->barcode  . '/account/outstanding';
    return $this->request($endpoint);
  }

  public function titleLists() {
    $endpoint = 'patron/' . $this->barcode . '/patronaccountgettitlelists';
    return $this->request($endpoint);
  }

  private function request($endpoint, array $query = []) {
    return $this->client->request()
      ->staff()
      ->public()
      ->path($endpoint)
      ->query($query)
      ->get()
      ->send();
  }

}
