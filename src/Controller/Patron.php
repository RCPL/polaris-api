<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;
use RCPL\Polaris\Entity\Patron as Entity;

class Patron extends ControllerBase {

  private $updateable = [];

  public function get($patron_barcode) {
    return $this->validate($patron_barcode) ? new Entity($this, $patron_barcode) : FALSE;
  }

  public function validate($patron_barcode) {
    $request = $this->client->request()
      ->public()
      ->get()
      ->staff()
      ->path('patron/' . $patron_barcode)
      ->send();
    // TODO: Initialize Patron object with PatronID.
    return $request->ValidPatron ? $request->PatronID : FALSE;
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
}
