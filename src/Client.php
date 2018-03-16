<?php

namespace RCPL\Polaris;

use GuzzleHttp\Client as HttpClient;
use Drupal\Core\Site\Settings;
use Drupal\Core\Cache\CacheBackendInterface;
use GuzzleHttp\Psr7\Response;
use Zend\Stdlib\Parameters;
use RCPL\Polaris\Controller\Staff;
use RCPL\Polaris\Utility\Parameters;

/**
 * @file
 * Wrapper class for Polaris API calls.
 *
 * @property Staff staff;
 * @property Organization organization;
 * @property Patron patron;
 * @property Bib bib;
 */



class Client extends HttpClient {

  /**
   * Http client
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

    /**
     * Polaris parameters.
     *
     * @var Parameters;
     */
  protected $params;

    /**
     * Configuration for http request.
     *
     * @var Parameters;
     */
  protected $config;


  /**
   * Client constructor.
   */
  public function __construct(array $params) {
    // https://catalog.richlandlibrary.com/PAPIService/REST/public/v1/1033/100/1/search/headings/TI?startpoint=1&numterms=50&preferredpos=1
    $config = [
      'headers' => [ 
        'PolarisDate' => $this->date,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ],
      'decode_content' => 'json',
      'json' => [
        "Domain"   => $this->params->get('STAFF_DOMAIN'),
        "Username" => $this->params->get('STAFF_USERNAME'),
        "Password" => $this->params->get('STAFF_PASSWORD'),
      ],
    ];
    $this->client = new HttpClient($config);
    $this->config = new Parameters([]);
  }

  public function __get($name) {
    $class = ucwords($name);
    if (isset($this->{$name})) {
      return $this->{$name};
    }
    if (class_exists('\RCPL\Polaris\\' . $class)) {
      $class = '\RCPL\Polaris\\' . $class;
      $this->{$name} = new $class($this);
      return $this->{$name};
    }
    return FALSE;
  }

  public function public(Staff $staff = NULL) {
    $url = $this->url('public') . '/';
    if ($staff) {
      $this->config->set('headers', [
        'X-PAPI-AccessToken' => $staff->auth()->AccessToken,
      ]);
      $this->config->set('access_secret', $staff->auth()->AccessSecret);
    }
    $this->config->set('base_uri', $url);
    return $this;
  }

  public function protected(Staff $staff = NULL) {
    $url = $this->url('private') . '/';
    if ($staff) {
      $url .= $staff->auth()->AccessToken . '/';
      $this->config->set('access_secret', $staff->auth()->AccessSecret);
    }
    $this->config->set('base_uri', $url);
    return $this;
  }

  public function get($path) {
    return $this->auth('GET', $path)->json($this->client->get($path, $this->config->toArray()));
  }

  public function post($path) {
    return $this->auth('POST', $path)->json($this->client->post($path, $this->config->toArray()));
  }

  public function auth($method, $path) {
    $signature = $this->buildSignature($method, $this->config->get('base_uri') . $path, $this->date, NULL, $this->config->get('access_secret'));
    $headers = $this->config->get('headers', []);
    $headers['Authorization'] = 'PWS ' . $this->params->get('ACCESS_ID') . ':' . $signature;
    $this->config->set('headers', $headers);
    return $this;
  }

  private function json(Response $request) {
    return json_decode($request->getBody()->getContents());
  }

  private function url($type = 'public') {
    $url = self::$scheme . '://' . $this->params->get('HOST');
    //$url = "https://rltrain.richlandlibrary.com";
    if ($type == 'public') {
      $url .= self::$baseUrlPublic;
    }
    else {
      $url .= self::$baseUrlProtected;
    }
    return $url;
  }

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
  private function buildSignature($http_method, $url, $date, $pass = '', $access_secret = '') {
    $signature = $http_method . $url . $date . $pass . $access_secret;
    return base64_encode(hash_hmac('sha1', $signature, $this->params->get('ACCESS_KEY'), TRUE));
  }

  /**
   * Get date formated to RFC-1123.
   *
   * @return string
   */
  private static function getPolarisDate() {
    return str_replace('+0000', 'GMT', gmdate('r'));
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
   * @param string $pass
   * @param bool|string $raw_json
   * @param bool $protected
   * @param bool $as_staff
   * @internal param string $api_call
   * @return mixed
   */
  protected static function deleteCallAPI($endpoint, $pass = '', $raw_json = FALSE, $protected = FALSE, $as_staff = FALSE) {
    return self::callAPI($endpoint, 'DELETE', $pass, NULL, $raw_json, $protected, $as_staff);
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
    $headers = [];
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
    $curl_opts = [];
    switch ($method) {
      case 'GET':
        $curl_opts[CURLOPT_HTTPGET] = 1;
        break;
      case 'DELETE':
        $curl_opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
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
    $return_headers = [];

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
      $data = new \SimpleXMLElement($body);
      $json_body = (object) drupal_json_decode(drupal_json_encode($data)); // Parse the XML into an object instead.
    }
    // If HTTP response is not 200 OK throw an error.
    if ($code != '200') {
      throw new \Exception('Polaris HTTP Error: ' . $method . ' ' . $endpoint . ': ' . $status_message . "\r\n" . $body, intval($code));
    }
    // If Polaris API "ErrorCode" is less than zero throw an error.
    if ($json_body->PAPIErrorCode < 0) {
      \Drupal::logger('polaris')->error('Polaris API Error: @method @endpoint @error_message @body, @papi_error_code', ['@method' => $method, '@endpoint' => $endpoint, '@error_message' => $json_body->ErrorMessage, '@body' => $body, '@papi_error_code' => intval($json_body->PAPIErrorCode)]);
      //drupal_set_message('Polaris API Error: ' . $method . ' ' . $endpoint . ': ' . $json_body->ErrorMessage . "\r\n" . $body, intval($json_body->PAPIErrorCode));
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
    static $bib_searches = [];

    $api_call = '/search/bibs/' . $type . '?' . $query;
    $key = md5($api_call);

    if (!isset($bib_searches[$key]) || $reset == TRUE) {
      $cid = 'Client:searchBibs:' . $key;
      $cache = \Drupal::cache()->get($cid);

      if (empty($cache) || $reset == TRUE) {
        try {
          $bib_searches[$key] = self::getCallAPI($api_call);
          \Drupal::cache()->set($cid, $bib_searches[$key], CacheBackendInterface::CACHE_PERMANENT, ['rendered']);
        }
        catch(Exception $e) {
          cache_clear_all($cid, 'cache');
          \Drupal::logger('polaris')->error('There was an issue executing searchBibs: @exception', ['@exception' => $e->getMessage()]);
          return;
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
    static $bibs = [];

    if (!isset($bibs[$bib_id]) || $reset == TRUE) {
      $cid = 'Client:getBib:' . $bib_id;
      $cache = \Drupal::cache()->get($cid);

      if (empty($cache) || $reset == TRUE) {
        $api_call = '/bib/' . $bib_id;
        try {
          $bibs[$bib_id] = self::getCallAPI($api_call);
          \Drupal::cache()->set($cid, $bibs[$bib_id], CacheBackendInterface::CACHE_PERMANENT, ['rendered']);
        }
        catch(Exception $e) {
          cache_clear_all($cid, 'cache');
          \Drupal::logger('polaris')->error('There was an issue executing getBib: @exception', ['@exception' => $e->getMessage()]);
          return;
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
    static $bib_holdings = [];
    if (!isset($bib_holdings[$bib_id]) || $reset == TRUE) {
      $cid = 'Client:getBibHoldings:' . $bib_id;
      $cache = \Drupal::cache()->get($cid);
      if (empty($cache) || $reset == TRUE) {
        $api_call = '/bib/' . $bib_id . '/holdings';
        try {
          $bib_holdings[$bib_id] = self::getCallAPI($api_call);
          \Drupal::cache()->set($cid, $bib_holdings[$bib_id], CacheBackendInterface::CACHE_PERMANENT, ['rendered']);
        }
        catch(Exception $e) {
          cache_clear_all($cid, 'cache');
          \Drupal::logger('polaris')->error('There was an issue executing getBibHoldings: @exception', ['@exception' => $e->getMessage()]);
          return;
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
      \Drupal::logger('polaris')->error('There was an issue executing getPatronHoldRequests: @exception', ['@exception' => $e->getMessage()]);
      return;
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
      \Drupal::logger('polaris')->error('There was an issue executing getPatronItemsOut: @exception', ['@exception' => $e->getMessage()]);
      return;
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
    $data = new \stdClass();
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
    $data->WorkstationID = 633;
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
      	  return array('status' => TRUE, 'message' => $results->Message);
      	  break;
      	case 3:
      	  //conditional
      	  //call hold request reply, which recursively replies until an answer
          // or error is returned
      	  return self::holdRequestReply($results->RequestGUID, $location_code, $results->StatusValue, $results->TxnQualifier, $results->TxnGroupQualifer, $xml);
      	  break;
      }
    }
    catch (Exception $e) {
      \Drupal::logger('polaris')->error('There was an issue executing createHoldRequest: @exception', ['@exception' => $e->getMessage()]);
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
    $data = new \stdClass();
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
      \Drupal::logger('polaris')->error('There was an issue executing holdRequestReply: @exception', ['@exception' => $e->getMessage()]);
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
    $endpoint = '/patron/' . $patronBarcode . '/holdrequests/' . $requestID . '/cancelled?wsid=633&userid=1';
    try {
      $result = self::putCallAPI($endpoint, $data, NULL, FALSE, FALSE, TRUE);
      return TRUE;
    }
    catch(Exception $e) {
      \Drupal::logger('polaris')->error('There was an issue executing cancelHoldRequest: @exception', ['@exception' => $e->getMessage()]);
      return;
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
    $data->LogonWorkstationID = 633;
    $data->RenewData = new \stdClass();
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
      $items_object = Client::getPatronItemsOut($account_w->field_patron_login_user_id->value());
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
        $message = '"' . $title . '" is not eligible for renewal because it is an eBook. Please visit cloudLibrary and check the book out again. If it is checked out by another customer place a hold and you will be notified when it becomes available.';
        return $message;
      }
      else {
        \Drupal::logger('polaris')->error('There was an issue executing renewItem: @exception', ['@exception' => $e->getMessage()]);
        return;
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
    $workstation_id = 633;
    $endpoint = '/synch/tasks/checkout?wsid=' . $workstation_id . '&userid=' . self::$staffPolarisId;

    if (strlen($itemID) < 10) { // Assume cloudLibrary item.
      $vendor_id = 'cloudLibrary';
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
      \Drupal::logger('polaris')->error('Error while executing checkoutItem: @exception', ['@exception' => $e->getMessage()]);
      return;
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
    $workstation_id = 633;
    $endpoint = '/synch/tasks/checkin?wsid=' . $workstation_id . '&userid=' . self::$staffPolarisId;

    if (strlen($itemID) < 10) { // Assume cloudLibrary item.
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
      \Drupal::logger('polaris')->error('Error while executing checkinItem: @exception', ['@exception' => $e->getMessage()]);
      return;
    }
  }

  /**
   * Get list of all organizations
   *
   * @param string $type - allowed values: all, system, library, branch
   */
  public static function getOrganizations($type = 'all', $reset = FALSE) {
    static $organizations = [];
    if (!isset($organizations[$type]) || $reset == TRUE) {
      $cid = 'Client:getOrganizations:' . $type;
      $cache = \Drupal::cache()->get($cid);
      if (empty($cache) || $reset == TRUE) {
        $endpoint = '/organizations/' . $type;
        try {
          $organizations[$type] = self::getCallAPI($endpoint);
          \Drupal::cache()->set($cid, $organizations[$type], CacheBackendInterface::CACHE_PERMANENT, ['rendered']);
        }
        catch(Exception $e) {
          cache_clear_all($cid, 'cache');
          \Drupal::logger('polaris')->error('There was an issue executing getOrganizations: @exception', ['@exception' => $e->getMessage()]);
          return;
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
      \Drupal::logger('polaris')->error('Error while executing getBasicPatronData: @exception', ['@exception' => $e->getMessage()]);
      return;
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
      \Drupal::logger('polaris')->error('Error while executing getPatronPreferences: @exception', ['@exception' => $e->getMessage()]);
      return;
    }
  }

  /**
   * Get patron reading history (PatronReadingHistoryGet)
   *
   * @param string $patronBarcode
   */
  public static function PatronReadingHistoryGet($patron_barcode, $reset = FALSE) {

    $cid = 'Client:PatronReadingHistoryGet:' . $patron_barcode;
    $cache = \Drupal::cache()->get($cid);

    if (empty($cache) || $reset == TRUE) {
      try {
        $endpoint = '/patron/' . $patron_barcode . '/readinghistory?rowsperpage=25&page=1';
        $reading_history = self::getCallAPI($endpoint, NULL, NULL, FALSE, TRUE);
        \Drupal::cache()->set($cid, $reading_history, CacheBackendInterface::CACHE_PERMANENT, ['rendered']);
      }
      catch (Exception $e) {
        cache_clear_all($cid, 'cache');
        \Drupal::logger('polaris')->error('Error while executing PatronReadingHistoryGet: @exception', ['@exception' => $e->getMessage()]);
        return;
      }
    }
    else {
      $reading_history = $cache->data;
    }
    return $reading_history;
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
      \Drupal::logger('polaris')->error('Error while executing getPatronAccount: @exception', ['@exception' => $e->getMessage()]);
      return;
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
    $data = new \stdClass();
    $data->RecordStoreName = $list_name;
    try {
      return self::postCallAPI($endpoint, $data, NULL, FALSE, FALSE, TRUE);
    }
    catch(Exception $e) {
      \Drupal::logger('polaris')->error('Error while executing PatronAccountCreateTitleList: @exception', ['@exception' => $e->getMessage()]);
      return;
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
      \Drupal::logger('polaris')->error('Error while executing PatronAccountGetTitleLists: @exception', ['@exception' => $e->getMessage()]);
      return;
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
      return self::deleteCallAPI($endpoint, NULL, FALSE, FALSE, TRUE);
    }
    catch(Exception $e) {
      \Drupal::logger('polaris')->error('Error while executing PatronAccountDeleteTitleList: @exception', ['@exception' => $e->getMessage()]);
      return;
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
  public static function PatronTitleListAddTitle($patron_barcode, $list_id, $item_id, $title) {
    $endpoint = '/patron/' . $patron_barcode . '/patrontitlelistaddtitle';
    $data = new \stdClass();
    $data->RecordStoreID = $list_id;
    $data->RecordName = $title;
    $data->LocalControlNumber = $item_id;
    try {
      return self::postCallAPI($endpoint, $data, NULL, FALSE, FALSE, TRUE);
    }
    catch(Exception $e) {
      \Drupal::logger('polaris')->error('Error while executing PatronTitleListAddTitle: @exception', ['@exception' => $e->getMessage()]);
      return;
    }
  }

  /**
   * Copy a title from one list to another in a patron account.
   * Implements PatronTitleListCopyTitle. P. 131
   * Note: A negative RecordID (-990) can be submitted instead of the title's
   * position.
   *
   * @param string $patron_barcode
   * @param integer $from_list_id
   * @param integer $position
   * @param integer $to_list_id
   */
  public static function PatronTitleListCopyTitle($patron_barcode, $from_list_id, $position = -990, $to_list_id) {
    $endpoint = '/patron/' . $patron_barcode . '/patrontitlelistcopytitle';
    $data = new \stdClass();
    $data->FromRecordStoreID = $from_list_id;
    $data->FromPosition = $position;
    $data->ToRecordStoreID = $to_list_id;
    try {
      return self::postCallAPI($endpoint, $data, NULL, FALSE, FALSE, TRUE);
    }
    catch(Exception $e) {
      \Drupal::logger('polaris')->error('Error while executing PatronTitleListCopyTitle: @exception', ['@exception' => $e->getMessage()]);
      return;
    }
  }

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
      \Drupal::logger('polaris')->error('Error while executing PatronTitleListGetTitles: @exception', ['@exception' => $e->getMessage()]);
      return;
    }
  }

  // @TODO: PatronTitleListMoveTitle. P. 139

  /**
   * Delete a specific list for a customer
   * Implements PatronTitleListDeleteTitle. P. 142
   *
   * @param string $patron_barcode
   * @param integer $list_id
   * @param integer $position_id
   */
  public static function PatronTitleListDeleteTitle($patron_barcode, $list_id, $position_id) {
    $endpoint = '/patron/' . $patron_barcode . '/patrontitlelistdeletetitle?list=' . $list_id . '&position=' . $position_id;
    try {
      return self::deleteCallAPI($endpoint, NULL, FALSE, FALSE, TRUE);
    }
    catch(Exception $e) {
      \Drupal::logger('polaris')->error('Error while executing PatronTitleListDeleteTitle: @exception', ['@exception' => $e->getMessage()]);
      return;
    }
  }

  /**
   * Delete all titles from a specific list for a customer
   * Implements PatronTitleListDeleteAllTitles. P. 144
   *
   * @param string $patron_barcode
   * @param integer $list_id
   */
  public static function PatronTitleListDeleteAllTitles($patron_barcode, $list_id) {
    $endpoint = '/patron/' . $patron_barcode . '/patrontitlelistdeletealltitles?list=' . $list_id;
    try {
      return self::deleteCallAPI($endpoint, NULL, FALSE, FALSE, TRUE);
    }
    catch(Exception $e) {
      \Drupal::logger('polaris')->error('Error while executing PatronTitleListDeleteAllTitles: @exception', ['@exception' => $e->getMessage()]);
      return;
    }
  }

  /**
   * Pay fine for specified patron account
   *
   * PROTECTED CALL - requires staff authentication
   *
   * @param string $patronBarcode
   * @param string $chargeID
   */
  public static function patronAccountPay($patron_barcode, $charge_id, $amount, $reference = '') {
    $workstation_id = 633;
    $endpoint = '/patron/' . $patron_barcode . '/account/' . $charge_id . '/pay?wsid=' . $workstation_id . '&userid=' . self::$staffPolarisId;
    $data = new \stdClass();
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
    $data = new \stdClass();
    $data->Domain = self::$staffDomain;
    $data->Username = self::$staffUsername;
    $data->Password = self::$staffPassword;
    try {
      return self::postCallAPI($endpoint, $data, NULL, NULL, TRUE);
    }
    catch(Exception $e) {
      \Drupal::logger('polaris')->error('Error while executing authenticateStaff: @exception', ['@exception' => $e->getMessage()]);
      return;
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
        $cached = \Drupal::cache()->get($cid, 'cache');
        if ($cached && time() < $cached->expire) {
          $authentication = $cached->data;
        }
        else {
          // Retrieve new authentication data from the API.
          $authentication = self::authenticateStaff();
          if (!empty($authentication)) {
            // Save the authentication object in the cache for 24 hours.
            \Drupal::cache()->set($cid, $authentication, time() + 86400);
          }
        }
      }
    }

    return $authentication;
  }

  /**
   * Test the customer's username & password
   */
  public static function AuthenticatePatron($identifier, $password) {
    $endpoint = '/authenticator/patron';
    $data = new \stdClass();
    $data->Barcode = $identifier;
    $data->Password = $password;

    try {
      // Return the JSON object result.
      return self::postCallAPI($endpoint, $data, NULL, FALSE, FALSE, TRUE);
    }
    catch(Exception $e) {
      // Log an error if the error (HTTP) code is anything but "unauthorized".
      if ($e->getCode() != 401) {
        \Drupal::logger('polaris')->error('Error while executing AuthenticatePatron: @exception', ['@exception' => $e->getMessage()]);
        return;
      }
    }
    // The identifier and password do not validate.
    return NULL;
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
        \Drupal::logger('polaris')->error('Error while executing patronValidate: @exception', ['@exception' => $e->getMessage()]);
        return;
      }
    }
    // The identifier and password do not validate.
    return NULL;
  }

  /**
   * Search the Polaris API for a Patron.
   */
  public static function patronSearch($patron_barcode) {
    try {
      $endpoint = '/search/patrons/Boolean?q=PATB=' . $patron_barcode;
      return self::getCallAPI($endpoint, NULL, FALSE, TRUE);
    }
    catch(Exception $e) {
      \Drupal::logger('polaris')->error('There was an issue executing patronSearch: @exception', ['@exception' => $e->getMessage()]);
      return;
    }
  }

  /**
   * Only used to update pin for now,
   * @todo implement other features
   * @param string $pin
   */
  public static function patronUpdate($patronBarcode, $newPin) {
    $endpoint = '/patron/' . $patronBarcode;
    $data = new \stdClass();
    $data->LogonBranchID = 1;
    $data->LogonUserID = 1;
    $data->LogonWorkstationID = 633;
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
      \Drupal::logger('polaris')->error('There was an issue executing patronUpdate: @exception', ['@exception' => $e->getMessage()]);
      return;
    }
  }


  /**
   * Create Patron Registration
   *
   *
   * @return array
   *   Returns an array with keys of status and message
   */

  public static function patronRegistrationCreate($logon_branch_id, $logon_user_id, $logon_workstation_id, $patron_branch_id, $postal_code, $zip_plus_four, $city, $state, $county, $country_id, $street_one, $street_two, $name_first, $name_last, $name_middle, $user1, $user2, $user3, $user4, $user5, $gender, $dob, $phone_voice1, $phone_voice2, $email_address, $language_id, $delivery_option_id, $user_name, $password, $barcode, $patron_code_id, $expiration_date, $address_check_date) {

    $endpoint = '/patron';
    $data = new \stdClass();
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

    if ($patron_code_id) {
      $data->PatronCode = $patron_code_id;
    }
    if ($expiration_date) {
      $data->ExpirationDate = $expiration_date;
    }
    if ($address_check_date) {
      $data->AddressCheckDate = $address_check_date;
    }

    $xml = FALSE;

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
      \Drupal::logger('polaris')->error('There was an issue executing patronRegistrationCreate: @exception', ['@exception' => $e->getMessage()]);
      return array('status' => FALSE, 'message' => 'An error has occured');
    }
  }

  /**
   * Updates the pickup branch/location for a hold request.
   *
   * @param string $barcode
   * @param string $hold_request_id - pass 0 to cancel all requests for specified patron
   * @return mixed
   */
  public static function updatePickupBranchID($barcode, $hold_request_id, $hold_pickup_branch_id) {
    $endpoint = '/patron/' . $barcode . '/holdrequests/' . $hold_request_id . '/pickupbranch?wsid=633&userid=1&pickupbranchid=' . $hold_pickup_branch_id;
    $data = new \stdClass();
    try {
      $result = self::putCallAPI($endpoint, $data, NULL, FALSE, FALSE, TRUE);
      return TRUE;
    }
    catch(Exception $e) {
      \Drupal::logger('polaris')->error('There was an issue executing updatePickupBranchID: @exception', ['@exception' => $e->getMessage()]);
      return;
    }
  }

  /**
   * A function to help with holds. Need to encode XML like JSON.
   * From: http://tinyurl.com/n8cjynm
   */
  private static function xml_encode($obj) {
    $xml = new \XmlWriter();
    $xml->openMemory();
    $xml->startDocument('1.0');
    $xml->setIndent(true);

    if (property_exists($obj, 'StreetOne')) { // only appears when we register a patron
      $xml->startElement('PatronRegistrationCreateData');
    }
    elseif (property_exists($obj, 'TxnGroupQualifier')) { // If it's a reply to a conditional question from Polaris, do this wrapper.
      $xml->startElement('HoldRequestReplyData');
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
   * Clean ISBNs (removes text strings in parentheses)
   *
   * @param array $isbns
   * @return mixed
   */
  public static function cleanISBNs($isbns = array()) {
    foreach ($isbns as $key => $value) {
      $isbns[$key] = preg_replace('/\s\([^)]+\)/', '' , $value);
    }
    // Remove nulls.
    $isbns = array_filter($isbns, 'strlen');
    return $isbns;
  }

}
