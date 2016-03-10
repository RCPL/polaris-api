<?php
/**
 * @file
 * Wrapper class for Polaris API calls.
 */

define('POLARIS_API_ACCESS_ID', $GLOBALS['conf']['POLARIS_API_ACCESS_ID']);
define('POLARIS_API_ACCESS_KEY', $GLOBALS['conf']['POLARIS_API_ACCESS_KEY']);
define('POLARIS_API_HOST', $GLOBALS['conf']['POLARIS_API_HOST']);
define('POLARIS_API_STAFF_DOMAIN', $GLOBALS['conf']['POLARIS_API_STAFF_DOMAIN']);
define('POLARIS_API_STAFF_ID', $GLOBALS['conf']['POLARIS_API_STAFF_ID']);
define('POLARIS_API_STAFF_USERNAME', $GLOBALS['conf']['POLARIS_API_STAFF_USERNAME']);
define('POLARIS_API_STAFF_PASSWORD', $GLOBALS['conf']['POLARIS_API_STAFF_PASSWORD']);

class PolarisAPI {

  // @todo These ought to be settings.
  protected static $accessID = POLARIS_API_ACCESS_ID;
  protected static $accessKey = POLARIS_API_ACCESS_KEY;
  protected static $scheme = 'https';
  protected static $server = POLARIS_API_HOST;
  protected static $baseUrlPublic = '/PAPIService/REST/public/v1/1033/100/1';
  protected static $baseUrlProtected = '/PAPIService/REST/protected/v1/1033/100/1';
  protected static $staffPolarisId = POLARIS_API_STAFF_ID;
  protected static $staffDomain = POLARIS_API_STAFF_DOMAIN;
  protected static $staffUsername = POLARIS_API_STAFF_USERNAME;
  protected static $staffPassword = POLARIS_API_STAFF_PASSWORD;

  /**
   * Build polaris signature for auth header.
   *
   * @param string $http_method
   * @param string $url
   * @param string $date
   * @param string $pass
   * @param string $access_secret
   * @return string
   */
  private static function buildSignature($http_method, $url, $date, $pass = '', $access_secret = '') {
    $signature = $http_method . $url . $date . $pass . $access_secret;
    return base64_encode(hash_hmac('sha1', $signature, self::$accessKey, TRUE));
  }

  /**
   * Get date formated to RFC-1123.
   *
   * @return string
   */
  private static function getPolarisDate() {
    return gmdate('r');
  }

  /**
   * Call API with GET.
   *
   * @param $endpoint
   * @param string $pass
   * @param bool|string $raw_json
   * @param bool $protected
   * @param bool $as_staff
   * @internal param string $api_call
   * @return mixed
   */
  protected static function getCallAPI($endpoint, $pass = '', $raw_json = FALSE, $protected = FALSE, $as_staff = FALSE) {
    return self::callAPI($endpoint, 'GET', $pass, NULL, $raw_json, $protected, $as_staff);
  }

  /**
   * Call API with POST.
   *
   * @param $endpoint
   * @param mixed $fields
   * @param string $pass
   * @param bool|string $raw_json
   * @param bool $protected
   * @param bool $as_staff
   * @internal param string $api_call
   * @internal param string $type
   * @return mixed
   */
  protected static function postCallAPI($endpoint, $fields, $pass = '', $raw_json = FALSE, $protected = FALSE, $as_staff = FALSE, $xml = FALSE) {
    return self::callAPI($endpoint, 'POST', $pass, $fields, $raw_json, $protected, $as_staff, $xml);
  }

  /**
   * Call API with PUT.
   *
   * @param $endpoint
   * @param mixed $fields
   * @param string $pass
   * @param bool|string $raw_json
   * @param bool $protected
   * @param bool $as_staff
   * @internal param string $api_call
   * @internal param string $type
   * @return mixed
   */
  protected static function putCallAPI($endpoint, $fields, $pass = '', $raw_json = FALSE, $protected = FALSE, $as_staff = FALSE, $xml = FALSE) {
    return self::callAPI($endpoint, 'PUT', $pass, $fields, $raw_json, $protected, $as_staff, $xml);
  }

  /**
   * Call API with DELETE.
   *
   * @param $endpoint
   * @param array $fields
   * @param string $pass
   * @param bool|string $raw_json
   * @internal param string $api_call
   * @internal param string $type
   * @return mixed
   */
  protected static function deleteCallAPI($endpoint, $fields, $pass = '', $raw_json = FALSE, $protected = FALSE, $as_staff = FALSE, $xml = FALSE) {
    return self::callAPI($endpoint, 'DELETE', $pass, $fields, $raw_json, $protected, $as_staff, $xml);
  }

  /**
   * Call the Polaris API with the specified parameters.
   *
   * @param string $endpoint
   *   The method endpoint.
   * @param string $method
   *   The HTTP method: GET, POST, PUT, etx.
   * @param string $pass
   *   The patron password.
   * @param object|string|array $fields
   *   Pass to cUrl as POSTFIELDS. Objects converted to JSON strings.
   * @param bool $raw_json
   *   Return a json object (FALSE) or string (TRUE).
   * @param bool $protected
   *   Execute method as part of the "protected" API.
   * @param bool $as_staff
   *   Executee public methods as a staff member in lieu of patron password.
   *
   * @throws Exception
   * @return mixed
   *   JSON object as stdClass or string.
   */
  protected static function callAPI($endpoint, $method = 'GET', $pass = '', $fields = array(), $raw_json = FALSE, $protected = FALSE, $as_staff = FALSE, $xml = FALSE) {
    // Construct the API URL.
    $url = self::$scheme . '://' . self::$server;
    // The secret is necessary for the signature of protected methods as well as
    // public methods accessed as a staff user.
    $secret = '';
    // Set up the request parameters for protected vs. public methods.
    $headers = array();
    if ($protected) {
      // Use the protected API URL.
      $url .= self::$baseUrlProtected;
      // Append access token to URL for methods besides 'authenticator'.
      if (!(strstr($endpoint, 'authenticator'))) {
        $authentication = self::getStaffAuthentication();
        $url .= '/' . $authentication->AccessToken;
        $secret = $authentication->AccessSecret;
      }
    }
    else {
      // Use the public API URL.
      $url .= self::$baseUrlPublic;
      // Add access token to the header if accessing as a staff member.
      if ($as_staff) {
        $authentication = self::getStaffAuthentication();
        if (is_object($authentication)) {
          $headers[] = 'X-PAPI-AccessToken: ' . $authentication->AccessToken;
          $secret = $authentication->AccessSecret;
        }
        else {
          $headers[] = 'X-PAPI-AccessToken: ' . '';
          $secret = '';
        }
      }
    }
    // Add the endpoint to the URL.
    $url .= $endpoint;

    // Get the Polaris-formatted date.
    $date = self::getPolarisDate();
    // Generate the API signature.
    $signature = self::buildSignature($method, $url, $date, $pass, $secret);
    // Add default request headers.
    if ($xml != TRUE) {
      $headers = array_merge(array(
        'PolarisDate: ' . $date,
        'Authorization: PWS ' . self::$accessID . ':' . $signature,
        'Content-Type: application/json',
        'Accept: application/json',
      ), $headers);
      // If $fields is an object, convert it to a JSON string.
      if (is_object($fields)) {
        $fields = json_encode($fields);
      }
    }
    else {
      $headers = array_merge(array(
        'PolarisDate: ' . $date,
        'Authorization: PWS ' . self::$accessID . ':' . $signature,
        'Content-Type: text/xml',
        'Accept: text/xml',
      ), $headers);
      // If $fields is an object, convert it to an XML string.
      if (is_object($fields)) {
        $fields = self::xml_encode($fields);
      }
    }

    // Set up CURL option by HTTP $method.
    $curl_opts = array();
    switch ($method) {
      case 'GET':
      case 'DELETE':
        $curl_opts[CURLOPT_HTTPGET] = 1;
        break;

      case 'POST':
        $curl_opts[CURLOPT_POST] = 1;
        $curl_opts[CURLOPT_POSTFIELDS] = $fields;
        break;

      case 'PUT':
        $curl_opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
        $curl_opts[CURLOPT_POSTFIELDS] = $fields;
        break;
    }
    // Set the cURL URL.
    $curl_opts[CURLOPT_URL] = $url;
    // Tell cURL to return the response instead of "print"'ing it out.
    $curl_opts[CURLOPT_RETURNTRANSFER] = 1;
    // Add custom HTTP headers to the request.
    $curl_opts[CURLOPT_HTTPHEADER] = $headers;
    // Ask for the HTTP response headers in the result.
    $curl_opts[CURLOPT_HEADER] = 1;

    // Initialize cURL connection object.
    $ch = curl_init();
    // Add options to the cURL request.
    curl_setopt_array($ch, $curl_opts);
    // Execute the external HTTP request and gather the response.
    $response = curl_exec($ch);
    // Get the length of the response headers so they can be parsed.
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    // Close the cURL request.
    curl_close($ch);
    // Populate the raw headers.
    $return_headers_raw = explode("\r\n", substr($response, 0, $header_size));
    // Populate the response body.
    $body = substr($response, $header_size);
    // Pull out the HTTP status code and message.
    list(, $code, $status_message) = explode(' ', trim(array_shift($return_headers_raw)), 3);
    // Parse the rest of the headers into an associative array.
    // Shamelessly stolen from drupal_http_request().
    // @see drupal_http_request()
    $return_headers = array();

    while ($line = trim(array_shift($return_headers_raw))) {
      list($name, $value) = explode(':', $line, 2);
      $name = strtolower($name);
      if (isset($return_headers[$name]) && $name == 'set-cookie') {
        // RFC 2109: the Set-Cookie response header comprises the token Set-
        // Cookie:, followed by a comma-separated list of one or more cookies.
        $return_headers[$name] .= ',' . trim($value);
      }
      else {
        $return_headers[$name] = trim($value);
      }
    }

    // Create a stdClass object from the response body.
    if ($xml != TRUE) {
      $json_body = json_decode($body);
    }
    else {
      $data = new SimpleXMLElement($body);
      $json_body = (object) drupal_json_decode(drupal_json_encode($data)); // Parse the XML into an object instead.
    }
    // If HTTP response is not 200 OK throw an error.
    if ($code != '200') {
      throw new Exception('Polaris HTTP Error: ' . $method . ' ' . $endpoint . ': ' . $status_message . "\r\n" . $body, intval($code));
    }
    // If Polaris API "ErrorCode" is less than zero throw an error.
    if ($json_body->PAPIErrorCode < 0) {
      throw new Exception('Polaris API Error: ' . $method . ' ' . $endpoint . ': ' . $json_body->ErrorMessage . "\r\n" . $body, intval($json_body->PAPIErrorCode));
    }
    // Return either the raw or decoded json.
    return $raw_json ? $body : $json_body;
  }

  /**
   * Search Bibs.
   *
   * @param string $query
   * @param string $type
   * @param bool $reset
   * @return mixed
   */
  public static function searchBibs($query = '', $type = 'keyword/au', $reset = FALSE) {
    static $bib_searches = array();

    $api_call = '/search/bibs/' . $type . '?' . $query;
    $key = md5($api_call);

    if (!isset($bib_searches[$key]) || $reset == TRUE) {
      $cid = 'PolarisAPI:searchBibs:' . $key;
      $cache = cache_get($cid);

      if (empty($cache) || $reset == TRUE) {
        try {
          $bib_searches[$key] = self::getCallAPI($api_call);
          cache_set($cid, $bib_searches[$key], 'cache', CACHE_TEMPORARY);
        }
        catch(Exception $e) {
          watchdog('PolarisAPI', 'There was an issue executing searchBibs: @exception',
            array('@exception' => $e->getMessage()),
            WATCHDOG_ERROR);
          cache_clear_all($cid, 'cache');
          return FALSE;
        }
      }
      else {
        $bib_searches[$key] = $cache->data;
      }
    }

    return $bib_searches[$key];
  }

  /**
   * Get specific bib.
   *
   * @param unknown $bib_id
   * @param bool $reset
   * @return mixed
   */
  public static function getBib($bib_id, $reset = FALSE) {
    static $bibs = array();

    if (!isset($bibs[$bib_id]) || $reset == TRUE) {
      $cid = 'PolarisAPI:getBib:' . $bib_id;
      $cache = cache_get($cid);

      if (empty($cache) || $reset == TRUE) {
        $api_call = '/bib/' . $bib_id;
        try {
          $bibs[$bib_id] = self::getCallAPI($api_call);
          cache_set($cid, $bibs[$bib_id], 'cache', CACHE_TEMPORARY);
        }
        catch(Exception $e) {
          watchdog('PolarisAPI', 'There was an issue executing getBib: @exception',
                   array('@exception' => $e->getMessage()),
                   WATCHDOG_ERROR);
          cache_clear_all($cid, 'cache');
          return FALSE;
        }
      }
      else {
        $bibs[$bib_id] = $cache->data;
      }
    }
    return $bibs[$bib_id];
  }

  /**
   * Get holdings information for a specified bibliographic record
   *
   * @param int $bib_id
   * @param bool $reset
   * @return bool
   */
  public static function getBibHoldings($bib_id, $reset = FALSE) {
    static $bib_holdings = array();
    if (!isset($bib_holdings[$bib_id]) || $reset == TRUE) {
      $cid = 'PolarisAPI:getBibHoldings:' . $bib_id;
      $cache = cache_get($cid);
      if (empty($cache) || $reset == TRUE) {
        $api_call = '/bib/' . $bib_id . '/holdings';
        try {
          $bib_holdings[$bib_id] = self::getCallAPI($api_call);
          cache_set($cid, $bib_holdings[$bib_id], 'cache', CACHE_TEMPORARY);
        }
        catch(Exception $e) {
          watchdog('PolarisAPI', 'There was an issue executing getBibHoldings: @exception',
                   array('@exception' => $e->getMessage()),
                   WATCHDOG_ERROR);
          cache_clear_all($cid, 'cache');
          return FALSE;
        }
      }
      else {
        $bib_holdings[$bib_id] = $cache->data;
      }
    }
    return $bib_holdings[$bib_id];
  }

  /**
   * Get list of hold requests placed by patron.
   *
   * @param string $patron_barcode
   * @param string $type
   */
  public static function getPatronHoldRequests($patron_barcode, $type = 'all') {
    try {
      $endpoint = '/patron/' . $patron_barcode  . '/holdrequests/' . $type;
      return self::getCallAPI($endpoint, NULL, FALSE, FALSE, TRUE);
    }
    catch(Exception $e) {
      watchdog('PolarisAPI', 'There was an issue executing getPatronHoldRequests: @exception',
        array('@exception' => $e->getMessage()),
        WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Get list of checkout out items for patron.
   *
   * @param string $patron_barcode
   * @param string $type
   */
  public static function getPatronItemsOut($patron_barcode, $type = 'all') {
    try {
      $endpoint = '/patron/' . $patron_barcode  . '/itemsout/' . $type;
      return self::getCallAPI($endpoint, NULL, NULL, FALSE, TRUE);
    }
    catch(Exception $e) {
      watchdog('PolarisAPI', 'There was an issue executing getPatronItemsOut: @exception',
          array('@exception' => $e->getMessage()),
          WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Get list of new and read patron messages.
   *
   * @param string $patron_barcode
   * @param bool $unread_only
   */
  public static function getPatronMessages($patron_barcode, $unread_only = false) {

  }

  /**
   * Mark message as read.
   *
   * @param string $patron_barcode
   * @param string $message_type
   * @param string $message_id
   */
  public static function updatePatronMessageStatus($patron_barcode, $message_type, $message_id) {

  }

  /**
   * Delete a specific patron message.
   *
   * @param string $patronBarcode
   * @param string $messageType
   * @param string $messageID
   */
  public static function deletePatronMessage($patronBarcode, $messageType, $messageID) {

  }

  /**
   * Create hold request
   *
   * @param $bib_id
   * @param $location_code
   * @param $patron_id
   *
   * @return array
   *   Returns an array with keys of status and message
   */
  public static function createHoldRequest($bib_id, $location_code, $patron_id) {
    $endpoint = '/holdrequest';
    $data = new stdClass();
    $data->PatronID = $patron_id;
    $data->BibID = $bib_id;
    $data->ItemBarcode = '';
    $data->VolumeNumber = '';
    $data->Designation = '';
    $data->PickupOrgID = $location_code;
    $data->IsBorrowByMail = 0;
    $data->PatronNotes = '';
    $data->ActivationDate = '/Date(' . date('Y-m-d\T') . '00:00:00.00)/';
    $data->Answer = '';
    $data->RequestID = '';
    $data->WorkstationID = 1;
    $data->UserID = 1;
    $data->RequestingOrgID = 1;
    $data->TargetGUID = '';
    try {
      $xml = TRUE; // Use XML instead of JSON.
      $results = self::postCallAPI($endpoint, $data, NULL, FALSE, FALSE, TRUE, $xml);

      switch ($results->StatusType) {
      	case 1:
      	  //error
      	  return array('status' => FALSE, 'message' => $results->Message);
      	  break;
      	case 2:
      	  //answer
          if (function_exists('drupal_get_path')) { // Ensure this is a Drupal environment.
            self::sendHoldEmail($bib_id, $location_code, $patron_id, $results->Message); // Send an email to the user when they successfully place a hold.
          }
      	  return array('status' => TRUE, 'message' => $results->Message);
      	  break;
      	case 3:
      	  //conditional
          if (function_exists('drupal_get_path')) { // Ensure this is a Drupal environment.
            self::sendHoldEmail($bib_id, $location_code, $patron_id, $results->Message); // Send an email to the user when they successfully place a hold.
          }
      	  //call hold request reply, which recursively replies until an answer or error is returned
      	  return self::holdRequestReply($results->RequestGUID, $location_code, $results->StatusValue, $results->TxnQualifier, $results->TxnGroupQualifer, $xml);
      	  break;
      }
    }
    catch (Exception $e) {
      watchdog('PolarisAPI', 'There was an issue executing createHoldRequest: @exception',
                array('@exception' => $e->getMessage()),
                WATCHDOG_ERROR);
      return array('status' => FALSE, 'message' => 'An error has occured');
    }
  }

  /**
   * Reply call for hold request.
   *
   * Recursive function until an answer or error is returned
   *
   * @param string $requestGUID
   * @param string $requestingOrg
   * @param int $statusValue
   * @param string $txnQualifier
   * @param string $txnGroup
   *
   * @return array
   *   Returns an array with keys of status and message
   */
  private static function holdRequestReply($requestGUID, $requestingOrg, $statusValue, $txnQualifier, $txnGroup, $xml = FALSE) {
    $endpoint = '/holdrequest/' . $requestGUID;
    $data = new stdClass();
    $data->TxnGroupQualifier = $txnGroup;
    $data->TxnQualifier = $txnQualifier;
    $data->RequestingOrgId = $requestingOrg;
    $data->Answer = 1; //always answer yes, except any hold
    $data->State = $statusValue;

    try {
      $results = self::putCallAPI($endpoint, $data, NULL, NULL, FALSE, TRUE, $xml);

      switch ($results->StatusType) {
      	case 1:
      	  //error - return values
      	  return array('status' => TRUE, 'message' => $results->Message);
      	  break;
      	case 2:
      	  //answer - return values
      	  return array('status' => TRUE, 'message' => $results->Message);
      	  break;
      	case 3:
      	  //conditional - recursively call holdRequestReply until we recieve an answer or error
      	  self::holdRequestReply($requestGUID, $requestingOrg, $results->StatusValue, $txnQualifier, $txnGroup, $xml);
      	  break;
      }
    }
    catch (Exception $e) {
      watchdog('PolarisAPI', 'There was an issue executing holdRequestReply: @exception',
                array('@exception' => $e->getMessage()),
                WATCHDOG_ERROR);
      return array('status' => FALSE, 'message' => 'An error has occured');
    }
  }

  /**
   * Cancel 1 or all hold requests
   *
   * @param string $patronBarcode
   * @param string $requestID - pass 0 to cancel all requests for specified patron
   * @return mixed
   */
  public static function cancelHoldRequest($patronBarcode, $requestID) {
    $endpoint = '/patron/' . $patronBarcode . '/holdrequests/' . $requestID . '/cancelled?wsid=1&userid=1';
    try {
      $result = self::putCallAPI($endpoint, $data, NULL, FALSE, FALSE, TRUE);
      return TRUE;
    }
    catch(Exception $e) {
      watchdog('PolarisAPI', 'There was an issue executing cancelHoldRequest: @exception',
        array('@exception' => $e->getMessage()),
        WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Suspend or reactivate hold request
   *
   * @param string $patronBarcode
   * @param string $requestID
   */
  public static function suspendHoldRequest($patronBarcode, $requestID) {

  }

  /**
   * Renew one or all items for specified patron
   *
   * @param string $patronBarcode
   * @param string $itemID - pass 0 to renew all
   */
  public static function renewItem($patronBarcode, $itemID) {
    $endpoint = '/patron/' . $patronBarcode . '/itemsout/' . $itemID;

    $data->Action = 'renew';
    $data->LogonBranchID = 1;
    $data->LogonUserID = 1;
    $data->LogonWorkstationID = 1;
    $data->RenewData = new stdClass();
    $data->RenewData->IgnoreOverrideErrors = TRUE;

    try {
      return self::putCallAPI($endpoint, $data, NULL, NULL, FALSE, TRUE);
    }
    catch(Exception $e) {
      $data = trim(preg_replace('/\s+/', ' ', $e->getMessage())); // Strip line breaks.
      preg_match('/Polaris API Error.*ErrorDesc":"(.*)".*/U', $data, $matches); // U makes it an ungreedy match.
      $message = $matches[1];
      // START getting the title.
      global $user;
      // Fully load the current user.
      $account = user_load($user->uid);
      // Wrap the user object.
      $account_w = entity_metadata_wrapper('user', $account);
      $items_object = PolarisAPI::getPatronItemsOut($account_w->field_patron_login_user_id->value());
      $items = $items_object->PatronItemsOutGetRows;
      foreach ($items as $item) {
        if ($item->ItemID == $itemID) {
          $title = $item->Title;
          break;
        }
      }
      // END getting the title.
      if ($message == 'Item fills a hold request, not allowed to renew') {
        $message = 'We were unable to renew "' . $title . '". Someone else has requested this item, which makes it ineligible for renewal.';
        return $message;
      }
      else if ($message == 'Item has exceeded renewal limit, not allowed to renew') {
        $message = 'We were unable to renew "' . $title . '" because it has been renewed the maximum number of times.';
        return $message;
      }
      else if ($message == 'Item is an ebook, not allowed to renew') {
        $message = '"' . $title . '" is not eligible for renewal because it is an eBook. Please visit 3M Cloud Library and check the book out again. If it is checked out by another customer place a hold and you will be notified when it becomes available.';
        return $message;
      }
      else {
        watchdog('PolarisAPI', 'There was an issue executing renewItem: @exception',
          array('@exception' => $e->getMessage()),
          WATCHDOG_ERROR);
        return FALSE;
      }
    }
  }

  /**
   * Check out an item for specified patron
   *
   * @param string $patronBarcode
   * @param string $itemID
   * @param string $vendor_id = 3M Cloud Library, OverDrive, etc.
   */
  public static function checkoutItem($patronBarcode, $itemID, $vendor_id) { // SynchTasksCheckout
    $workstation_id = 1;
    $endpoint = '/synch/tasks/checkout?wsid=' . $workstation_id . '&userid=' . self::$staffPolarisId;

    if (strlen($itemID) < 10) { // Assume 3M item.
      $vendor_id = '3M Cloud Library';
      $vendor_contract_id = '1';
    }
    else { // Assume OverDrive.
      $vendor_id = 'OverDrive, Inc.'; // Could be 'OverDrive, Inc. (integrated)'. (Looking at Polaris.Vendors table.)
      $vendor_contract_id = '2'; // Could be '5'.
    }

    $data->VendorID = $vendor_id;
    $data->VendorContractID = $vendor_contract_id;
    $data->UniqueRecordID = $itemID;
    $data->PatronBarcode = $patronBarcode;
    $data->ItemExpireDateTime = '/Date(' . str_replace('+00:00', '', gmdate(DATE_ATOM, strtotime('+21 days'))) . ')/'; // Assume expiration in 3 weeks.
    $data->TransactionDateTime = '/Date(' . str_replace('+00:00', '', gmdate(DATE_ATOM, strtotime('now'))) . ')/'; // Need it in GMT. Use gmdate() instead of date().

    try {
      return self::putCallAPI($endpoint, $data, NULL, NULL, TRUE, TRUE);
    }
    catch(Exception $e) {
      watchdog('PolarisAPI', 'Error while executing checkoutItem: @exception',
        array('@exception' => $e->getMessage()),
        WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Check in an item for specified patron
   *
   * @param string $patronBarcode
   * @param string $itemID
   * @param string $vendor_id = 3M Cloud Library, OverDrive, etc.
   */
  public static function checkinItem($patronBarcode, $itemID, $vendor_id) { // SynchTasksCheckin
    $workstation_id = 1;
    $endpoint = '/synch/tasks/checkin?wsid=' . $workstation_id . '&userid=' . self::$staffPolarisId;

    if (strlen($itemID) < 10) { // Assume 3M item.
      $vendor_id = '3M Cloud Library';
      $vendor_contract_id = '1';
    }
    else { // Assume OverDrive.
      $vendor_id = 'OverDrive, Inc.'; // Could be 'OverDrive, Inc. (integrated)'. (Looking at Polaris.Vendors table.)
      $vendor_contract_id = '2'; // Could be '5'.
    }

    $data->VendorID = $vendor_id;
    $data->VendorContractID = $vendor_contract_id;
    $data->UniqueRecordID = $itemID;
    $data->PatronBarcode = $patronBarcode;
    $data->TransactionDateTime = '/Date(' . str_replace('+00:00', '', gmdate(DATE_ATOM, strtotime('now'))) . ')/'; // Need it in GMT. Use gmdate() instead of date().

    try {
      return self::putCallAPI($endpoint, $data, NULL, NULL, TRUE, TRUE);
    }
    catch(Exception $e) {
      watchdog('PolarisAPI', 'Error while executing checkinItem: @exception',
        array('@exception' => $e->getMessage()),
        WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Get list of all organizations
   *
   * @param string $type - allowed values: all, system, library, branch
   */
  public static function getOrganizations($type = 'all') {
    static $organizations = array();
    if (!isset($organizations[$type]) || $reset == TRUE) {
      $cid = 'PolarisAPI:getOrganizations:' . $type;
      $cache = cache_get($cid);
      if (empty($cache) || $reset == TRUE) {
        $endpoint = '/organizations/' . $type;
        try {
          $organizations[$type] = self::getCallAPI($endpoint, $data, NULL, NULL, FALSE, TRUE);
          cache_set($cid, $organizations[$type], 'cache', CACHE_TEMPORARY);
        }
        catch(Exception $e) {
          watchdog('PolarisAPI', 'There was an issue executing getOrganizations: @exception',
            array('@exception' => $e->getMessage()),
            WATCHDOG_ERROR);
          cache_clear_all($cid, 'cache');
          return FALSE;
        }
      }
      else {
        $organizations[$type] = $cache->data;
      }
    }
    return $organizations[$type];
  }

  /**
   * Get basic patron info and fees/account balances
   *
   * @param string $patronBarcode
   */
  public static function getBasicPatronData($patron_barcode) {
    $endpoint = '/patron/' . $patron_barcode . '/basicdata?addresses=1';
    try {
      return self::getCallAPI($endpoint, NULL, NULL, FALSE, TRUE, TRUE);
    }
    catch(Exception $e) {
      watchdog('PolarisAPI', 'Error while executing getBasicPatronData: @exception',
        array('@exception' => $e->getMessage()),
        WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Get patron preferences (PatronPreferencesGet)
   *
   * @param string $patronBarcode
   */
  public static function getPatronPreferences($patron_barcode) {
    $endpoint = '/patron/' . $patron_barcode . '/preferences';
    try {
      return self::getCallAPI($endpoint, NULL, NULL, FALSE, TRUE);
    }
    catch(Exception $e) {
      watchdog('PolarisAPI', 'Error while executing getPatronPreferences: @exception',
        array('@exception' => $e->getMessage()),
        WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Get list of fines and fees associated with patron account
   *
   * @param string $patronBarcode
   */
  public static function getPatronAccount($patron_barcode) {
    $endpoint = '/patron/' . $patron_barcode  . '/account/outstanding';
    try {
      return self::getCallAPI($endpoint, NULL, NULL, FALSE, TRUE);
    }
    catch(Exception $e) {
      watchdog('PolarisAPI', 'Error while executing getPatronAccount: @exception',
        array('@exception' => $e->getMessage()),
        WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Add a new list for a customer.
   * Implements PatronAccountCreateTitleList. P. 121
   *
   * @param string $patron_barcode
   * @param string $list_name
   */
  public static function PatronAccountCreateTitleList($patron_barcode, $list_name) {
    $endpoint = '/patron/' . $patron_barcode . '/patronaccountcreatetitlelist';
    $data = new stdClass();
    $data->RecordStoreName = $list_name;
    try {
      return self::postCallAPI($endpoint, $data, NULL, FALSE, FALSE, TRUE, $xml);
    }
    catch(Exception $e) {
      watchdog('PolarisAPI', 'Error while executing PatronAccountCreateTitleList: @exception',
        array('@exception' => $e->getMessage()),
        WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Get basic patron lists
   * Implements PatronAccountGetTitleLists. P. 124
   *
   * @param string $patron_barcode
   */
  public static function PatronAccountGetTitleLists($patron_barcode) {
    $endpoint = '/patron/' . $patron_barcode . '/patronaccountgettitlelists';
    try {
      return self::getCallAPI($endpoint, NULL, NULL, FALSE, TRUE, TRUE);
    }
    catch(Exception $e) {
      watchdog('PolarisAPI', 'Error while executing PatronAccountGetTitleLists: @exception',
        array('@exception' => $e->getMessage()),
        WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Delete a specific list for a customer
   * Implements PatronAccountDeleteTitleList. P. 126
   *
   * @param string $patron_barcode
   * @param integer $list_id
   */
  public static function PatronAccountDeleteTitleList($patron_barcode, $list_id) {
    $endpoint = '/patron/' . $patron_barcode . '/patronaccountdeletetitlelist?list=' . $list_id;
    try {
      return self::deleteCallAPI($endpoint, $data, NULL, NULL, TRUE, TRUE);
    }
    catch(Exception $e) {
      watchdog('PolarisAPI', 'Error while executing PatronAccountDeleteTitleList: @exception',
        array('@exception' => $e->getMessage()),
        WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Add an item into a customer's list.
   * Implements PatronTitleListAddTitle. P. 128
   *
   * @param string $patron_barcode
   * @param string $list_id
   * @param integer $item_id
   */
  public static function patronListItemAdd($patron_barcode, $list_id, $item_id, $title) {
    $endpoint = '/patron/' . $patron_barcode . '/patrontitlelistaddtitle';
    $data = new stdClass();
    $data->RecordStoreID = $list_id;
    $data->RecordName = $title;
    $data->LocalControlNumber = $item_id;
    try {
      return self::postCallAPI($endpoint, $data, NULL, FALSE, FALSE, TRUE, $xml);
    }
    catch(Exception $e) {
      watchdog('PolarisAPI', 'Error while executing patronListItemAdd: @exception',
        array('@exception' => $e->getMessage()),
        WATCHDOG_ERROR);
      return FALSE;
    }
  }

  // @TODO: PatronTitleListCopyTitle. P. 131

  // @TODO: PatronTitleListCopyAllTitles. P. 134

  /**
   * Get the items on a list for an individual customer.
   * Implements PatronTitleListGetTitles. P. 137
   *
   * @param string $patron_barcode
   * @param integer $list_id
   */
  public static function PatronTitleListGetTitles($patron_barcode, $list_id) {
    $endpoint = '/patron/' . $patron_barcode . '/patrontitlelistgettitles?list=' . $list_id;
    try {
      return self::getCallAPI($endpoint, NULL, NULL, FALSE, TRUE, TRUE);
    }
    catch(Exception $e) {
      watchdog('PolarisAPI', 'Error while executing PatronTitleListGetTitles: @exception',
        array('@exception' => $e->getMessage()),
        WATCHDOG_ERROR);
      return FALSE;
    }
  }

  // @TODO: PatronTitleListMoveTitle. P. 139

  // @TODO: PatronTitleListDeleteTitle. P. 142

  // @TODO: PatronTitleListDeleteAllTitles. P. 144

  /**
   * Pay fine for specified patron account
   *
   * PROTECTED CALL - requires staff authentication
   *
   * @param string $patronBarcode
   * @param string $chargeID
   */
  public static function patronAccountPay($patron_barcode, $charge_id, $amount, $reference = '') {
    $workstation_id = 1;
    $endpoint = '/patron/' . $patron_barcode . '/account/' . $charge_id . '/pay?wsid=' . $workstation_id . '&userid=' . self::$staffPolarisId;
    $data = new stdClass();
    $data->TxnAmount = $amount;
    $data->PaymentMethodID = 12;
    $data->FreeTextNote = 'Web order. Reference ' . $reference;
    $result = self::putCallAPI($endpoint, $data, NULL, NULL, TRUE);
    return $result;
  }

  /**
   * Authenticate a staff user.
   */
  public static function authenticateStaff() {
    $endpoint = '/authenticator/staff';
    $data = new stdClass();
    $data->Domain = self::$staffDomain;
    $data->Username = self::$staffUsername;
    $data->Password = self::$staffPassword;
    try {
      return self::postCallAPI($endpoint, $data, NULL, NULL, TRUE);
    }
    catch(Exception $e) {
      watchdog('PolarisAPI', 'Error while executing authenticateStaff: @exception',
        array('@exception' => $e->getMessage()),
        WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Retrieve the Staff account token/secret data from the cache or the API.
   */
  public static function getStaffAuthentication($reset = FALSE) {
    static $authentication = NULL;
    $cid = 'polaris:staffauthentication';
    if (!$reset) {
      if (empty($authentication)) {
        // If static cache is empty, retrieve from Drupal cache.
        $cached = cache_get($cid, 'cache');
        if ($cached && time() < $cached->expire) {
          $authentication = $cached->data;
        }
        else {
          // Retrieve new authentication data from the API.
          $authentication = self::authenticateStaff();
          if (!empty($authentication)) {
            // Parse the expiration date into unix micro-timestamp.
            //$authentication->AuthExpMicroTime = str_replace(array('/Date(', '-0500)/'), '', $authentication->AuthExpDate);
            // Save the authentication object in the cache.
            cache_set($cid, $authentication, 'cache', time() + 86400); // Cache the token for 24 hours.
          }
        }
      }
    }

    return $authentication;
  }

  /**
   * Return the patron record if successful, otherwise NULL.
   */
  public static function patronValidate($identifier, $password) {
    $endpoint = '/patron/' . $identifier;
    try {
      // Return the JSON object of the Patron.
      return self::getCallAPI($endpoint, $password);
    }
    catch(Exception $e) {
      // Log an error if the error (HTTP) code is anything but "unauthorized".
      if ($e->getCode() != 401) {
        watchdog('PolarisAPI', 'Error while executing patronValidate: @exception',
          array('@exception' => $e->getMessage()),
          WATCHDOG_ERROR);
        return FALSE;
      }
    }
    // The identifier and password do not validate.
    return NULL;
  }

  /**
   * Search the Polaris API for a Patron.
   */
  public static function patronSearch($patron_barcode) {
    $endpoint = '/search/patrons/Boolean?q=PATB=' . $patron_barcode;
    return self::getCallAPI($endpoint, NULL, FALSE, TRUE);
  }

  /**
   * Only used to update pin for now,
   * @todo implement other features
   * @param string $pin
   */
  public static function patronUpdate($patronBarcode, $newPin) {
    $endpoint = '/patron/' . $patronBarcode;
    $data = new stdClass();
    $data->LogonBranchID = 1;
    $data->LogonUserID = 1;
    $data->LogonWorkstationID = 1;
    $data->EmailFormat = NULL;
    $data->DeliveryOption = NULL;
    $data->EmailAddress = NULL;
    $data->PhoneVoice1 = NULL;
    $data->Password = $newPin;
    try {
      $result = self::putCallAPI($endpoint, $data, NULL, NULL, FALSE, TRUE);
      return $result;
    }
    catch(Exception $e) {
      watchdog('PolarisAPI', 'There was an issue executing patronUpdate: @exception',
        array('@exception' => $e->getMessage()),
        WATCHDOG_ERROR);
      return FALSE;
    }
  }


  /**
   * Create Patron Registration
   *
   *
   * @return array
   *   Returns an array with keys of status and message
   */

  public static function patronRegistrationCreate($logon_branch_id, $logon_user_id, $logon_workstation_id, $patron_branch_id, $postal_code, $zip_plus_four, $city, $state, $county, $country_id, $street_one, $street_two, $name_first, $name_last, $name_middle, $user1, $user2, $user3, $user4, $user5, $gender, $dob, $phone_voice1, $phone_voice2, $email_address, $language_id, $delivery_option_id, $user_name, $password, $barcode) {

  $endpoint = '/patron';
  $data = new stdClass();
  $data->LogonBranchID = $logon_branch_id;
  $data->LogonUserID = $logon_user_id;
  $data->LogonWorkstationID = $logon_workstation_id;
  $data->PatronBranchID = $patron_branch_id;
  $data->PostalCode = $postal_code;
  $data->ZipPlusFour = $zip_plus_four;
  $data->City = $city;
  $data->State = $state;
  $data->County = $county;
  $data->CountryID = $country_id;
  $data->StreetOne = $street_one;

  if ($street_two) {
    $data->StreetTwo = $street_two;
  }

  $data->NameFirst = $name_first;
  $data->NameLast = $name_last;

  if ($name_middle) {
    $data->NameMiddle = $name_middle;
  }

  if ($user1) {
    $data->User1 = $user1;
  }

  if ($user2) {
    $data->User2 = $user2;
  }

  if ($user3) {
    $data->User3 = $user3;
  }

  if ($user4) {
    $data->User4 = $user4;
  }

  if ($user5) {
    $data->User5 = $user5;
  }

  $data->Gender = $gender;
  $data->Birthdate = $dob;
  $data->PhoneVoice1 = $phone_voice1;

  // only include PhoneVoice2 if it has value
  if ($phone_voice2) {
    $data->PhoneVoice2 = $phone_voice2;
  }

  $data->EmailAddress = $email_address;

  if ($language_id) {
    $data->LanguageID = $language_id;
  }

  if ($delivery_option_id) {
    $data->DeliveryOptionID = $delivery_option_id;
  }

  if ($user_name) {
    $data->UserName = $user_name;
  }

  $data->Password = $password;
  $data->Password2 = $password;
  $data->Barcode = $barcode;

  $xml = TRUE;

  try {
    $results = self::postCallAPI($endpoint, $data, NULL, FALSE, FALSE, TRUE, $xml);

       switch ($results->StatusType) {
         case 1:
         //error
         return array('status' => FALSE, 'message' => $results->Message);
         break;
         case 0: // this was 2; changed it to 0 to account for success msp
         //answer
         return array('status' => TRUE, 'message' => $results->Message, 'response' => $results);
         break;
         case 3:
         //conditional
         //call hold request reply, which recursively replies until an answer or error is returned
         return self::holdRequestReply($results->RequestGUID, $location_code, $results->StatusValue, $results->TxnQualifier,
         $results->TxnGroupQualifer);
         break;
      }
    }
    catch (Exception $e) {
      watchdog('PolarisAPI', 'There was an issue executing patronRegistrationCreate: @exception',
                array('@exception' => $e->getMessage()),
                WATCHDOG_ERROR);
      return array('status' => FALSE, 'message' => 'An error has occured');
    }
  }

  /**
   * A function to help with holds. Need to encode XML like JSON.
   * From: http://tinyurl.com/n8cjynm
   */
  private static function xml_encode($obj) {
    $xml = new XmlWriter();
    $xml->openMemory();
    $xml->startDocument('1.0');
    $xml->setIndent(true);

    if (property_exists($obj, 'StreetOne')) { // only appears when we register a patron
      $xml->startElement('PatronRegistrationCreateData');
    }
    elseif (property_exists($obj, 'TxnGroupQualifier')) { // If it's a reply to a conditional question from Polaris, do this wrapper.
      $xml->startElement('HoldRequestReplyData');
    }
    elseif (property_exists($obj, 'RecordStoreID')) { // For My Lists adding an item to a list.
      $xml->startElement('PatronTitleListAddTitleData');
    }
    elseif (property_exists($obj, 'RecordStoreName')) { // For My Lists adding a new list.
      $xml->startElement('PatronAccountCreateTitleListData');
    }
    else { // Otherwise we're just making a hold request to start with. Do this wrapper.
      $xml->startElement('HoldRequestCreateData');
    }

    foreach ($obj as $key => $value) {
      if ($key == 'ActivationDate') {
        $value = str_replace(')/', '', str_replace('/Date(', '', $value));
      }
			$xml->writeElement($key, $value);
		}
    $xml->endElement();
    return $xml->outputMemory(TRUE);
  }

  /**
   * Function to send a success email to users after their hold has been placed.
   */
  private static function sendHoldEmail($bib_id, $location_code, $patron_id, $message) {
    global $user;
    $account_w = entity_metadata_wrapper('user', $user);
    $patron_barcode = $account_w->field_patron_login_user_id->value();
    $bib = pac_get_clean_bib($bib_id);
    $cleaned_title = pac_extract_bib_title($bib['title']);
    $title = trim($cleaned_title[0]);
    $current_holds = $bib['current_holds'];
    $locations = PolarisAPI::getOrganizations('branch');
    $locations = $locations->OrganizationsGetRows;
    $preferences = PolarisAPI::getPatronPreferences($patron_barcode);
    $format = $preferences->PatronPreferences->DeliveryEmailFormatDescription; // Either "HTML" or "Plain text".

    $branch = '';
    foreach($locations as $location) {
      if ($location->OrganizationID == $location_code) {
        $branch = $location->Name;
      }
    }

    // See corresponding hook_mail() function which adds the message, etc.
    $to = $user->mail;
    if (empty($to)) {
      $basic_patron_data = PolarisAPI::getBasicPatronData($patron_barcode);
      $to = $basic_patron_data->PatronBasicData->EmailAddress;
    }
    if (!empty($to)) {
      $params = array(
        '@title' => $title,
        '@current_holds' => $current_holds,
        '@branch' => $branch,
        '@format' => $format,
        '@patron_barcode' => $patron_barcode,
      );
      $from = variable_get('site_name') . ' <' . variable_get('site_mail') . '>';
      if (!$mail = drupal_mail('pac', 'place_hold', $to, $language = NULL, $params, $from)) {
        watchdog('pac', 'Error sending holds email to @to. Used format "@format". Hold placed on "@title" for patron barcode "@patron_barcode". Mailer Error: @string', array('@to' => $to, '@format' => $format, '@title' => $title, '@patron_barcode' => $patron_barcode, '@string' => $mail->ErrorInfo), WATCHDOG_ERROR);
      }
      else {
        watchdog('pac', 'Successfully sent hold email to @to. Used format "@format". Hold placed on "@title" for patron barcode "@patron_barcode".', array('@to' => $to, '@format' => $format, '@title' => $title, '@patron_barcode' => $patron_barcode), WATCHDOG_INFO);
      }
    }
  }

}

// Negates some Drupal-specific functions in this file to allow for use in other
// systems.
if (!function_exists('cache_get')) {
  define('WATCHDOG_ERROR', 'Generic Error');
  define('CACHE_TEMPORARY', 'Temporary cache');
  function cache_clear_all() {
    return TRUE;
  }
  function cache_get() {
    return NULL;
  }
  function cache_set() {
    return TRUE;
  }
  function drupal_json_decode($data) {
    $result = json_decode($data);
    return $result;
  }
  function drupal_json_encode($data) {
    $result = json_encode($data);
    return $result;
  }
  function watchdog() {
    return TRUE;
  }
}
