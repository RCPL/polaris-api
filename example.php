<?php

require_once('vendor/autoload.php');

use RCPL\Polaris\Client;

$client = new Client([
  'ACCESS_ID'      => '< your info here >',
  'ACCESS_KEY'     => '< your info here >',
  'HOST'           => '< your info here >',
  'STAFF_DOMAIN'   => '< your info here >',
  'STAFF_ID'       => '< your info here >',
  'STAFF_USERNAME' => '< your info here >',
  'STAFF_PASSWORD' => '< your info here >',
]);


/**
 * Begin examples.
 */
print '<h1>Polaris API Example Calls</h1>';

// Try pulling a list of 25 titles.
print '<h2>1) Pulling a list of 25 titles w/ keyword "Harry Potter" using ' .
'BibSearch</h2>';
$result = $client->bibliography->search('Harry Potter', ['bibsperpage' => 25]);
Kint::dump($result);

// Get a list of hold requests for a customer.
print '<h2>2) Get a list of hold requests for a customer using PatronHoldRequestsGet</h2>';
$patron_barcode = 20080104020258; // Customer's library card number
$patron = $client->patron->get($patron_barcode);
$result = $patron->holdRequests();
Kint::dump($result);

// Get a list of branch locations and other patron details
print '<h2>3) Get the customer\'s preferred branch location & "patron ID" using PatronSearch</h2>';
$patron_barcode = 20080104020258; // Customer's library card number
$result = $client->patron->search($patron_barcode);
Kint::dump($result);

// Try putting something on hold.
/*print '<h2>4) Put a copy of Harry Potter on request using HoldRequestCreate</h2>';
$bib_id = 682052; // The bib id for the item in question. e.g., see $result->BibSearchRows[0]->ControlNumber from example 1.
$location_code = 3; // Branch location to send the item to. See $result->PatronSearchRows[0]->OrganizationID from example 3.
$patron_id = 172338; // Customer's "patron ID". See $result->PatronSearchRows[0]->PatronID from example 3.
$result = PolarisAPI::createHoldRequest($bib_id, $location_code, $patron_id);
display_result($result);*/
