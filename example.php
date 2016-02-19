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

include('pac_polaris.inc');

// Try pulling a list of 25 titles.
$query = 'q=' . urlencode('Harry Potter') . '&bibsperpage=25';
$result = PolarisAPI::searchBibs($query);

if (function_exists('krumo')) {
  krumo($result);
}
else if (function_exists('d')) {
  Kint::dump($result);
}
else {
  var_dump($result);
}
