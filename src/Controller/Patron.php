<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;

class Patron extends ControllerBase {

  private $request;

  public function __construct(Client $client, $patron_barcode = NULL) {
    $this->barcode = $patron_barcode;
    parent::__construct($client);
    $this->request = $this->client->request()
      ->staff()
      ->public()
      ->get();
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

  /**
   * @alias for self::data()
   *
   * Aligns with the naming convention of the API.
   */
  public function basicData() {
    return $this->data();
  }

  public function data() {
    $endpoint = 'patron/' . $this->barcode . '/basicdata';
    $query = [
      'addresses' => 1
    ];
    return $this->request->path($endpoint)->query($query)->simple('PatronBasicData')->send();
  }

  public function holdRequests($type = 'all') {
    $endpoint = 'patron/' . $this->barcode  . '/holdrequests/' . $type;
    return $this->request->path($endpoint)->simple('PatronHoldRequestsGetRows')->send();
  }

  public function itemsOut($type = 'all') {
    $endpoint = 'patron/' . $this->barcode  . '/itemsout/' . $type;
    return $this->request->path($endpoint)->simple('PatronItemsOutGetRows')->send();
  }

  public function preferences() {
    $endpoint = 'patron/' . $this->barcode . '/preferences';
    return $this->request->path($endpoint)->simple('PatronPreferences')->send();
  }

  public function account() {
    $endpoint = 'patron/' . $this->barcode  . '/account/outstanding';
    return $this->request->path($endpoint)->simple('PatronAccountGetRows')->send();
  }

  public function titleLists() {
    $endpoint = 'patron/' . $this->barcode . '/patronaccountgettitlelists';
    return $this->request->path($endpoint)->simple('PatronAccountTitleListsRows')->send();
  }

}
