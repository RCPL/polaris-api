<?php

/* Important: Credentials below must be filled in! */
$GLOBALS['conf'] = array(
  'POLARIS_API_ACCESS_ID' => '', // Given to you by your Polaris Site Manager
  'POLARIS_API_ACCESS_KEY' => '', // Given to you by your Polaris Site Manager
  'POLARIS_API_HOST' => '', // The hostname (e.g., polaris.yourlibrary.com)
  'POLARIS_API_STAFF_DOMAIN' => '', // The network domain for protected methods
  'POLARIS_API_STAFF_ID' => '', // The Polaris account numeric id for protected methods
  'POLARIS_API_STAFF_USERNAME' => '', // The Polaris and matching network domain username
  'POLARIS_API_STAFF_PASSWORD' => '' // The Polaris and matching network domain password
);

// Look for optional local settings file. Helpful for presentation purposes. :)
if (file_exists('settings.local.php')) {
  include 'settings.local.php';
}

// Is Krumo or Kint present?
if (file_exists('krumo/class.krumo.php')) {
  include('krumo/class.krumo.php');
}
else if (file_exists('kint/Kint.class.php')) {
  include 'kint/Kint.class.php';
}

// Include the Polaris API PHP Class file
include('pac_polaris.inc');

/**
 * Reusable display function for results of an API call.
 *
 * @param obj $result
 * @return n/a
 */
function display_result($result) {
  if (function_exists('krumo')) {
    krumo($result);
  }
  else if (function_exists('d')) {
    Kint::dump($result);
  }
  else {
    var_dump($result);
  }
}

/**
 * Begin examples.
 */
print '<h1>Polaris API Example Calls</h1>';

// Try pulling a list of 25 titles.
print '<h2>1) Pulling a list of 25 titles w/ keyword "Harry Potter" using ' .
'BibSearch</h2>';
$query = 'q=' . urlencode('Harry Potter') . '&bibsperpage=25';
$result = PolarisAPI::searchBibs($query);
display_result($result);

// Get a list of hold requests for a customer.
/*print '<h2>2) Get a list of hold requests for a customer using PatronHoldRequestsGet</h2>';
$patron_barcode = 20080104020258; // Customer's library card number
$result = PolarisAPI::getPatronHoldRequests($patron_barcode);
display_result($result);*/

// Get a list of branch locations and other patron details
/*print '<h2>3) Get the customer\'s preferred branch location & "patron ID" using PatronSearch</h2>';
$patron_barcode = 20080104020258; // Customer's library card number
$result = PolarisAPI::patronSearch($patron_barcode);
display_result($result);*/

// Try putting something on hold.
/*print '<h2>4) Put a copy of Harry Potter on request using HoldRequestCreate</h2>';
$bib_id = 682052; // The bib id for the item in question. e.g., see $result->BibSearchRows[0]->ControlNumber from example 1.
$location_code = 3; // Branch location to send the item to. See $result->PatronSearchRows[0]->OrganizationID from example 3.
$patron_id = 172338; // Customer's "patron ID". See $result->PatronSearchRows[0]->PatronID from example 3.
$result = PolarisAPI::createHoldRequest($bib_id, $location_code, $patron_id);
display_result($result);*/
