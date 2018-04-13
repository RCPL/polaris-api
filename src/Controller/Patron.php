<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;
use RCPL\Polaris\Entity\Patron as Entity;

class Patron extends ControllerBase {

  private $updateable = [];

  private $createable = [];

  public function get($patron_barcode) {
    return new Entity($this, ['barcode' => $patron_barcode]);
  }

  public function setup() {
    return new Entity($this, NULL);
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

  public function search($patron_barcode) {
    return $this->client->request()
      ->protected()
      ->token()
      ->get()
      ->path('search/patrons/boolean')
      ->query(['q' => 'PATB=' . $patron_barcode])
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
    ];
  }
}
