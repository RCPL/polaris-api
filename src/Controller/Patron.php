<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;

class Patron extends ControllerBase {

  private $request;

  private $updateable = [];

  public $barcode;

  private $holdRequestController;

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

  public function __set($key, $value) {
    if ($this->isUpdateable($key)) {
      $this->updateable[$key] = $value;
    }
    return FALSE;
  }

  public function __get($key) {
    if ($this->isUpdateable($key) && isset($this->updateable[$key])) {
      return $this->updateable[$key];
    }
    if (strtolower($key) == 'holdrequest') {
      return $this->getHoldRequestController();
    }
    return FALSE;
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
      ->path($endpoint)
      ->config($config)
      ->post()
      ->send();
  }

  public function search($patron_barcode) {
    return $this->client->request()
      ->protected()
      ->token()
      ->get()
      ->path('search/patrons/boolean')
      ->query(['q' => 'PATB=' . $patron_barcode])
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

  private function getHoldRequestController() {
    if (!isset($this->holdRequestController)) {
      $this->holdRequestController = $this->client->holdrequest->init($this);
    }
    return $this->holdRequestController;
  }

  public function holdRequests($type = 'all') {
    return $this->getHoldRequestController()->getByType($type);
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

  public function titleListCreate($list_name) {
    $endpoint = 'patron/' . $this->barcode . '/patronaccountcreatetitlelist';
    $config = [
      'json' => [
        'RecordStoreName' => $list_name,
      ],
    ];
    return $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->config($config)
      ->post()
      ->send();
  }

  public function titleListDelete($list_id) {
    $endpoint = 'patron/' . $this->barcode . '/patronaccountdeletetitlelist';
    $query = [
      'list' => $list_id
    ];
    return $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->query($query)
      ->delete()
      ->send();
  }

  public function fines() {
    $endpoint = 'patron/' . $this->barcode  . '/account/outstanding';
    return $this->request->path($endpoint)->simple('PatronAccountGetRows')->send();
  }

  public function update() {
    $values = array_filter(array_merge($this->updateable(), $this->updateable));
    $endpoint = 'patron/' . $this->barcode;
    return $this->client->request()
      ->public()
      ->path($endpoint)
      ->staff()
      ->config([
        'json' => $values,
      ])
      ->put()
      ->send();
  }

  private function isUpdateable($key) {
    return array_key_exists($key, $this->updateable());
  }

  private function updateable() {
    return [
      'LogonBranchID'                     => 1,
      'LogonUserID'                       => 1,
      'LogonWorkstationID'                => 1,
      'ReadingListFlag'                   => NULL,
      'EmailFormat'                       => NULL,
      'DeliveryOptionID'                  => NULL,
      'EmailAddress'                      => NULL,
      'PhoneVoice1'                       => NULL,
      'Password'                          => NULL,
      'AltEmailAddress'                   => NULL,
      'EnableSMS'                         => NULL,
      'PhoneVoice2'                       => NULL,
      'PhoneVoice3'                       => NULL,
      'Phone1CarrierID'                   => NULL,
      'Phone2CarrierID'                   => NULL,
      'Phone3CarrierID'                   => NULL,
      'TxtPhoneNumber'                    => NULL,
      'EReceiptOptionID'                  => NULL,
      'ExcludeFromAlmostOverdueAutoRenew' => NULL,
      'ExcludeFromPatronRecExpiration'    => NULL,
      'ExcludeFromInactivePatron'         => NULL,
      'ExpirationDate'                    => NULL,
      'AddrCheckDate'                     => NULL,
      'PatronCode'                        => NULL,
      'AddressID'                         => NULL,
      'FreeTextLabel'                     => NULL,
      'StreetOne'                         => NULL,
      'State'                             => NULL,
      'County'                            => NULL,
      'PostalCode'                        => NULL,
      'Country'                           => NULL,
      'AddressTypeID'                     => NULL,
    ];
  }
}
