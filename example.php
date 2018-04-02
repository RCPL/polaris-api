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
  'WORKSTATION_ID' => '< your info here >'
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
print '<h2>4) Put a copy of Harry Potter on request using HoldRequestCreate</h2>';
$bib_id = 682052; // The bib id for the item in question. e.g., see $result->BibSearchRows[0]->ControlNumber from example 1.
$location_code = 3; // Branch location to send the item to. See $result->PatronSearchRows[0]->OrganizationID from example 3.
$patron_id = 172338; // Customer's "patron ID". See $result->PatronSearchRows[0]->PatronID from example 3.

$patron = $client->patron->get($patron_id);

// Get list of all hold request objects keyed by request ID.
Kint::dump($patron->holdRequests('all'));

// Load a specific hold request object by request ID.
$hold = $patron->holdrequest->get('2966399');

// Activate a currently suspended hold request.
$hold->activate();

// Suspend a current active hold request until [php date time period experession http://php.net/manual/fr/dateinterval.construct.php].
$hold->suspendUntil('P20D');

// Create a new hold request.
$hold = $patron->holdrequest->create(['BibID' => $bib_id]);
$hold->save();

// Update Patron information.
print '<h2>5) Update Patron information</h2>';

$patron_id = 172338;
$patron = $client->patron->get($patron_id);
$patron->PhoneVoice2 = '123-456-7890';
$patron->update();

// Get a list of fines for a customer.
print '<h2>6) Get a list of fines for a customer using PatronAccountGet</h2>';
$patron_barcode = 20080104020258; // Customer's library card number
$patron = $client->patron->get($patron_barcode);
$result = $patron->fines();
Kint::dump($result);

// Get a list of items out for a customer.
print '<h2>7) Get a list of items out for a customer using PatronAccountGet</h2>';
$patron_barcode = 20080104020258; // Customer's library card number
$patron = $client->patron->get($patron_barcode);
$result = $patron->itemsOut();
Kint::dump($result);

// Get a list of lists for a customer.
print '<h2>8) Get a list of lists for a customer using PatronAccountGetTitleLists</h2>';
$patron_barcode = 20080104020258; // Customer's library card number
$patron = $client->patron->get($patron_barcode);
$result = $patron->titlelist->getLists();
Kint::dump($result);

// Create a list for a customer.
print '<h2>9) Create a list for a customer using PatronAccountCreateTitleList</h2>';
$patron_barcode = 20080104020258; // Customer's library card number
$patron = $client->patron->get($patron_barcode);
$result = $patron->titlelist->create('testmjarrell-' . strtotime('now'));
Kint::dump($result);

// Delete a list for a customer.
print '<h2>10) Delete a list for a customer using PatronAccountDeleteTitleList</h2>';
$patron_barcode = 20080104020258; // Customer's library card number
$list_id = 159834;
$patron = $client->patron->get($patron_barcode);
$result = $patron->titlelist->delete($list_id);
Kint::dump($result);

// Get a list of titles in a list for a customer.
print '<h2>11) Get a list of titles in a list for a customer using PatronTitleListGetTitles</h2>';
$patron_barcode = 20080104020258; // Customer's library card number
$patron = $client->patron->get($patron_barcode);
$result = $patron->titlelist->getTitles(1776);
Kint::dump($result);

// Add an item to a list for a customer.
print '<h2>12) Add an item to a list for a customer using PatronTitleListAddTitle</h2>';
$patron_barcode = 20080104020258; // Customer's library card number
$patron = $client->patron->get($patron_barcode);
$result = $patron->titlelist->addTitle(1776, 1475253);
Kint::dump($result);

// Delete an item from a list for a customer.
print '<h2>13) Delete an item from a list for a customer using PatronTitleListDeleteTitle</h2>';
$position_id = 6; // Position of the item in the list. See $result[0]->Position from example 11.
$patron_barcode = 20080104020258; // Customer's library card number
$patron = $client->patron->get($patron_barcode);
$result = $patron->titlelist->deleteTitle(1776, $position_id);
Kint::dump($result);

// Delete all titles from a list for a customer.
print '<h2>14) Delete all titles from a list for a customer using PatronTitleListDeleteAllTitles</h2>';
$patron_barcode = 20080104020258; // Customer's library card number
$list_id = 9634;
$patron = $client->patron->get($patron_barcode);
$result = $patron->titlelist->deleteAllTitles($list_id);
Kint::dump($result);

// Move a title from one list to another.
print '<h2>15) Move a title from one list to another using PatronTitleListMoveTitle</h2>';
$patron_barcode = 20080104020258; // Customer's library card number
$patron = $client->patron->get($patron_barcode);
$result = $patron->titlelist->moveTitle(1776, 5, 159835);
Kint::dump($result);

// Copy a title from one list to another.
print '<h2>16) Copy a title from one list to another using PatronTitleListCopyTitle</h2>';
$patron_barcode = 20080104020258; // Customer's library card number
$patron = $client->patron->get($patron_barcode);
$result = $patron->titlelist->copyTitle(159835, 1, 1776);
Kint::dump($result);

// Copy all titles from one list to another.
print '<h2>17) Copy all titles from one list to another using PatronTitleListCopyAllTitles</h2>';
$patron_barcode = 20080104020258; // Customer's library card number
$patron = $client->patron->get($patron_barcode);
$result = $patron->titlelist->copyAllTitles(1776, 159835);
Kint::dump($result);
