<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;
use RCPL\Polaris\Entity\Patron as Entity;
use Symfony\Component\Yaml\Yaml;

class Patron extends ControllerBase {

  private $updateable = [];

  private $createable = [];

  private $operator = 'AND';

  public function get($patron_barcode) {
    return new Entity($this, ['barcode' => $patron_barcode]);
  }

  /**
   * Create a new Patron entity.
   */
  public function create(array $data = []) {
    return new Entity($this, $data);
  }

  /**
   * @deprecated
   *
   * Use ::create()
   */
  public function setup() {
    return new Entity($this, []);
  }

  public function validate($patron_barcode) {
    // Not a valid username and will throw an API error anyway.
    if (str_replace(' ', '', $patron_barcode) !== $patron_barcode) {
      return FALSE;
    }
    $request = $this->client->request()
      ->public()
      ->get()
      ->staff()
      ->path(['patron', $patron_barcode])
      ->send();
    return $request->ValidPatron
      ? new Entity($this, (array) $request + ['barcode' => $request->PatronBarcode])
      : FALSE;
  }

  // Does not return barcode so cannot start a new patron object.
  public function authenticate($barcode, $password) {
    $endpoint = 'authenticator/patron';
    $config = [
      'json' => [
        'Barcode' => $barcode,
        'Password' => $password,
      ],
    ];

    return $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->config($config)
      ->post()
      ->simple('AccessToken')
      ->send();
  }

  protected function searchBase(array $query) {
    $q = $this->searchQuery($query);
    return $this->client->request()
      ->protected()
      ->token()
      ->get()
      ->path('search/patrons/boolean')
      ->query($q, PHP_QUERY_RFC3986);
  }

  protected function searchQuery(array $query) {
    array_walk($query, function(&$val, $key) {
      $val = "$key=$val";
    });
    return ['q' => join(" {$this->operator} ", $query)];
  }

  protected function or() {
    $this->operator = 'OR';
    return $this;
  }

  protected function and() {
    $this->operator = 'AND';
    return $this;
  }

  protected function validEntryPoint($key) {
    $parser = new \Symfony\Component\Yaml\Parser();
    $access_points = parseFile(__DIR__ . '/../../assets/search-access-points.yml');
    return $access_points;
  }

  public function searchOr(array $array) {
    return $this->or()->search($array);
  }

  public function searchAnd(array $array) {
    return $this->and()->search($array);
  }

  public function search(array $array) {
    return $this->searchBase($array)->send();
  }

  public function searchByBarcode($patron_barcode) {
    return $this->searchBase([
      'PATB' => $patron_barcode,
    ])->send();
  }

  public function searchByEmail($email) {
    return $this->searchBase([
      'EM' => $email,
    ])->send();
  }

  /**
   * @deprecated
   *
   * Use self::searchByEmail()
   */
  public function searchEmail($email) {
    return $this->client->request()
      ->protected()
      ->token()
      ->get()
      ->path('search/patrons/boolean')
      ->query(['q' => 'EM=' . $email])
      ->send();
  }

  public function searchDuplicate($dob, $fname, $phone) {
    // Note: The API returns an error whenever we try to use hyphens in the patron search, so avoid using those and try our best to find minimal matches.
    $phone_last_four = substr($phone, -4);
    $q = 'BD=*' . $dob . '* AND PATNF=' . $fname . '* AND PHONE=*' . $phone_last_four;
    return $this->client->request()
      ->protected()
      ->token()
      ->get()
      ->path('search/patrons/boolean')
      ->query(['q' => $q], PHP_QUERY_RFC3986)
      ->send();
  }

  public function isUpdateable($key) {
    return array_key_exists($key, $this->updateable());
  }

  public function updateable() {
    return [
      'LogonBranchID'                     => 1,
      'LogonUserID'                       => 1,
      'LogonWorkstationID'                => $this->client->params->get('WORKSTATION_ID'),
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
      'RequestPickupBranchID'             => NULL,
    ];
  }

  public function isCreateable($key) {
    return array_key_exists($key, $this->createable());
  }

  public function createable() {
    return [
      'LogonBranchID'           => 1,
      'LogonUserID'             => 1,
      'LogonWorkstationID'      => $this->client->params->get('WORKSTATION_ID'),
      'PatronBranchID'          => $this->client->params->get('DEFAULT_PATRON_BRANCH_ID'),
      'PostalCode'              => NULL,
      'ZipPlusFour'             => NULL,
      'City'                    => NULL,
      'State'                   => NULL,
      'County'                  => NULL,
      'CountryID'               => NULL,
      'StreetOne'               => NULL,
      'StreetTwo'               => NULL,
      'NameFirst'               => NULL,
      'NameLast'                => NULL,
      'NameMiddle'              => NULL,
      'LegalNameFirst'          => NULL,
      'LegalNameLast'           => NULL,
      'LegalNameMiddle'         => NULL,
      'User1'                   => NULL,
      'User2'                   => NULL,
      'User3'                   => NULL,
      'User4'                   => NULL,
      'User5'                   => NULL,
      'Gender'                  => NULL,
      'Birthdate'               => NULL,
      'PhoneVoice1'             => NULL,
      'PhoneVoice2'             => NULL,
      'EmailAddress'            => NULL,
      'LanguageID'              => NULL,
      'DeliveryOptionID'        => NULL,
      'UserName'                => NULL,
      'Password'                => NULL,
      'Password2'               => NULL,
      'AltEmailAddress'         => NULL,
      'PhoneVoice3'             => NULL,
      'Phone1CarrierID'         => NULL,
      'Phone2CarrierID'         => NULL,
      'Phone3CarrierID'         => NULL,
      'EnableSMS'               => NULL,
      'TxtPhoneNumber'          => NULL,
      'Barcode'                 => NULL,
      'EReceiptOptionID'        => NULL,
      'ExpirationDate'          => NULL,
      'AddrCheckDate'           => NULL,
      'PatronCode'              => NULL,
      'GenderID'                => NULL,
      'UseLegalNameOnNotices'   => NULL,
      'RequestPickupBranchID'   => NULL,
    ];
  }

  /**
   * @param string $deletedate
   *   Start date and time (records that have been deleted since this
   *   date/time). Format: MM/DD/YYYY HH:MM:SS.
   */
  public function getDeletedPatrons($deletedate) {
    $result = $this->client->request()
      ->protected()
      ->token()
      ->get()
      ->path('synch/patrons/deleted')
      ->query(['deletedate' => $deletedate], PHP_QUERY_RFC3986)
      ->send();
    return $result->BarcodeAndPatronIDRows;
  }
}
