<?php

namespace RCPL\Polaris\Entity;

use RCPL\Polaris\Controller\Patron as Controller;

class Patron extends EntityBase {

  /**
   * Valid values to send for a PatronUpdate request.
   *
   * @var array
   */
  private $updateable = [];

  /**
   * Valid values to send for a PatronRegistrationCreate request.
   *
   * @var array
   */
  private $createable = [];

  /**
   * Current Patron's barcode.
   *
   * @var int
   */
  public $barcode;

  /**
   * @var \RCPL\Polaris\Controller\HoldRequest
   */
  private $holdRequestController;

  /**
   * @var \RCPL\Polaris\Controller\TitleList
   */
  private $titleListController;

  /**
   * {@inheritdoc}
   */
  public function __construct(Controller $controller, array $data = []) {
    parent::__construct($controller, $data);
    if (empty($this->data['barcode'])) {
      //throw new Exception('Missing value barcode');
    }
    else {
      $this->barcode = $this->data['barcode'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function url() {
    return 'patron/' . $this->barcode;
  }

  public function barcode() {
    return $this->barcode;
  }

  public function getFirstName() {
    $data = $this->data();
    return isset($data->NameFirst) ? $data->NameFirst : NULL;
  }

  public function getMiddleName() {
    $data = $this->data();
    return isset($data->NameMiddle) ? $data->NameMiddle : NULL;
  }

  public function getLastName() {
    $data = $this->data();
    return isset($data->NameLast) ? $data->NameLast : NULL;
  }

  public function getLegalFirstName() {
    $data = $this->data();
    return isset($data->LegalNameFirst) ? $data->LegalNameFirst : NULL;
  }

  public function getLegalMiddleName() {
    $data = $this->data();
    return isset($data->LegalNameMiddle) ? $data->LegalNameMiddle : NULL;
  }

  public function getLegalLastName() {
    $data = $this->data();
    return isset($data->LegalNameLast) ? $data->LegalNameLast : NULL;
  }

  /**
   * Do not use directly, use $this->holdrequest.
   *
   * @see $this->__get()
   *
   * @return \RCPL\Polaris\Controller\HoldRequest
   */
  protected function holdRequestController() {
    if (!isset($this->holdRequestController)) {
      $this->holdRequestController = $this->client->holdRequest->init($this);
    }
    return $this->holdRequestController;
  }

  /**
   * Do not use directly, use $this->titlelist.
   *
   * @see $this->__get()
   *
   * @return \RCPL\Polaris\Controller\TitleList
   */
  protected function titleListController() {
    if (!isset($this->titleListController)) {
      $this->titleListController = $this->client->titleList->init($this);
    }
    return $this->titleListController;
  }

  /**
   * Populate updateable/createable Patron data.
   *
   * @param $key
   * @param $value
   * @return bool
   */
  public function __set($key, $value) {
    if ($this->controller->isUpdateable($key)) {
      $this->updateable[$key] = $value;
    }
    if ($this->controller->isCreateable($key)) {
      $this->createable[$key] = $value;
    }
    return FALSE;
  }

  public function __get($key) {
    if ($this->controller->isUpdateable($key) && isset($this->updateable[$key])) {
      return $this->updateable[$key];
    }
    if ($this->controller->isCreateable($key) && isset($this->createable[$key])) {
      return $this->createable[$key];
    }
    if (strtolower($key) == 'holdrequest') {
      return $this->holdRequestController();
    }
    if (strtolower($key) == 'titlelist') {
      return $this->titleListController();
    }
    return FALSE;
  }

  public function authenticate($password) {
    return $this->controller->authenticate($this->barcode, $password);
  }

  /**
   * @alias for self::data()
   *
   * Aligns with the naming convention of the API.
   */
  public function basicData() {
    return $this->data();
  }

  public function circulateBlocksGet() {
    $endpoint = 'patron/' . $this->barcode . '/circulationblocks';
    return $this->get()->path($endpoint)->send();
  }

  public function data() {
    $endpoint = 'patron/' . $this->barcode . '/basicdata';
    $query = [
      'addresses' => 1,
    ];
    return $this->get()->path($endpoint)->query($query)->simple('PatronBasicData')->send();
  }

  public function holdRequests($type = 'all') {
    return $this->holdRequest->getByType($type);
  }

  public function illRequests($type = 'all') {
    return $this->holdRequest->getByTypeILL($type);
  }

  public function titleLists() {
    return $this->titlelist->getLists();
  }

  public function itemsOut($type = 'all') {
    return $this->get()->path($this->url() . '/itemsout/' . $type)->simple('PatronItemsOutGetRows')->send();
  }

  public function preferences() {
    return $this->get()->path($this->url() . '/preferences')->simple('PatronPreferences')->send();
  }

  public function account() {
    return $this->get()->path($this->url() . '/account/outstanding')->simple('PatronAccountGetRows')->send();
  }

  public function fines() {
    $endpoint = 'patron/' . $this->barcode . '/account/outstanding';
    return $this->get()->path($endpoint)->simple('PatronAccountGetRows')->send();
  }

  public function readingHistory($rowsperpage = 5, $page = 0) {
    $endpoint = 'patron/' . $this->barcode . '/readinghistory';
    return $this->client->request()
      ->public()
      ->get()
      ->query([
        'rowsperpage' => $rowsperpage,
        'page' => $page,
      ])
      ->path($endpoint)
      ->simple('PatronReadingHistoryGetRows')
      ->staff()
      ->send();
  }

  public function enableReadingHistory() {
    $this->ReadingListFlag = 1;
    return $this->update();
  }

  public function disableReadingHistory() {
    $this->ReadingListFlag = '0';
    $this->update();
    $endpoint = 'patron/' . $this->barcode . '/readinghistory';
    return $this->client->request()
      ->public()
      ->path($endpoint)
      ->staff()
      ->delete()
      ->send();
  }

  public function clearReadingHistory($ids) {
    $endpoint = 'patron/' . $this->barcode . '/readinghistory';
    // A maximum of 50 are allowed.
    if (strlen($ids) >= 1) {
      $query = ['ids' => $ids];
    }
    return $this->client->request()
      ->public()
      ->path($endpoint)
      ->query($query)
      ->staff()
      ->delete()
      ->send();
  }

  public function update() { // PatronRegistrationUpdate
    $values = array_merge($this->controller->updateable(), $this->updateable);
    $values = array_filter($values, function ($v) {
      return isset($v);
    });
    $endpoint = 'patron/' . $this->barcode;
    // $test_response = $this->client->request()
    // ->public()
    // ->path($endpoint)
    // ->staff()
    // ->config([
    //   'json' => $values,
    // ])
    // ->put()
    // ->send();
    // $debug = true;
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

  public function updateUsername($new_username) {
    $endpoint = 'patron/' . $this->barcode . '/username/' . $new_username;
    return $this->client->request()
      ->public()
      ->path($endpoint)
      ->staff()
      ->put()
      ->send();
  }

  /**
   * Updates the customer profile by changing the location ID for the preferred
   * pickup location for holds.
   */
  public function updateDefaultPickupLocation($pickupLocationID) {
    if (is_numeric($pickupLocationID)) {
      $this->RequestPickupBranchID = $pickupLocationID;
      return $this->update();
    }
  }

  /**
   * @TODO: Make ::create() and ::update() protected and make this function delegate.
   */
  public function save() {}

  public function create() {
    $values = array_filter(array_merge($this->controller->createable(), $this->createable));
    $endpoint = 'patron';
    return $this->client->request()
      ->public()
      ->path($endpoint)
      ->staff()
      ->config([
        'json' => $values,
      ])
      ->post()
      ->send();
  }

  public function itemCheckin() {
    $query = [
      'wsid' => $this->client->params->get('WORKSTATION_ID'),
      'userid' => 1,
    ];
    $data = [
      //'PatronBarcode' => '455963',
      'VendorID' => '3M Cloud Library',
      'VendorContractID' => 'OverDrive, Inc.',
      'UniqueRecordID' => '589536',
      'PatronBarcode' => $this->barcode,
      'ItemExpireDateTime' => '/Date(' . str_replace('+00:00', '', gmdate(DATE_ATOM, strtotime('+21 days'))) . ')/',
      'TransactionDateTime' => '/Date(' . str_replace('+00:00', '', gmdate(DATE_ATOM, strtotime('now'))) . ')/',
      'ItemExpireDateTime' => str_replace('+00:00', '', gmdate(DATE_ATOM, strtotime('+21 days'))),
      'TransactionDateTime' => str_replace('+00:00', '', gmdate(DATE_ATOM, strtotime('now'))),
    ];
    return $this->client->request()
      ->protected()
      ->put()
      ->staff()
      ->token()
      ->config([
        'json' => $data,
      ])
      ->path('synch/tasks/checkin')
      ->query($query)
      ->send();
  }

  /**
   * Checkout an item by ID.
   *
   * @param int $item_id
   *   The item ID.
   * @param array $response
   *   Response to prompt (optional).
   *
   * @return object
   *   The Polaris response object.
   */
  public function itemCheckout($item_id, array $response = []) {
    $guid = NULL;
    $request_type = 'post';
    if (!empty($response)) {
      $guid = $response['json']['id'];
      $request_type = 'put';

    }
    $host = $this->client->params->get('HOST');
    $path = 'https://' . $host . '/Polaris.ApplicationServices/api/v1/eng/20/polaris/8/633/workflow/' . $guid;
    $access_token = $this->client->staff->auth()->AccessToken;
    $access_secret = $this->client->staff->auth()->AccessSecret;
    $auth_header = 'PAS polaris:' . $access_token . ':' . $access_secret;
    $config = array_merge([
      'headers' => [
        'Authorization' => $auth_header,
      ],
      'json' => [
        'WorkflowRequestType' => 2,
        'TxnBranchID' => $this->client->params->get('DEFAULT_PATRON_BRANCH_ID'),
        'TxnUserID' => $this->client->params->get('STAFF_ID'),
        'TxnWorkstationID' => $this->client->params->get('WORKSTATION_ID'),
        'RequestExtension' => [
          'WorkflowRequestExtensionType' => 1,
          'Data' => [
            'CheckoutTypeID' => 6,
            'PatronBarcode' => $this->barcode,
            'ItemBarcode' => $item_id,
            'OfflineCheckoutDate' => NULL,
            'IsSpecialLoan' => FALSE,
            'SpecialLoanUnits' => 0,
            'SpecialLoanUnitsNum' => 0,
            'IsOvernightPermitted' => FALSE,
            'IsBBMBulkCheckout' => FALSE,
            'IgnorePatronBlocksCheck' => FALSE,
            'IgnoreItemsOutLimitPrompt' => FALSE,
            'IgnoreLoanLimitPromptForMaterialTypeIDs' => NULL,
            'IgnoreORSPatronServiceDatePrompt' => FALSE,
          ],
        ],
        'WorkflowReplies' => NULL,
      ],
    ], $response);
    if ($response = $this->client->{$request_type}($path, $this->client->parameters($config))) {
      return json_decode($response->getBody());
    }
  }

  public function itemRenew($item_id) {
    $renewdata = new \stdClass();
    $renewdata->IgnoreOverrideErrors = TRUE;
    return $this->client->request()
      ->public()
      ->staff()
      ->config([
        'json' => [
          'Action' => 'renew',
          'LogonBranchID' => 1,
          'LogonUserID' => 1,
          'LogonWorkstationID' => $this->client->params->get('WORKSTATION_ID'),
          'RenewData' => $renewdata,
        ],
      ])
      ->path($this->url() . '/itemsout/' . $item_id)
      ->put()
      ->send();
  }

  /**
   * Convenience method for get Patron requests.
   */
  private function get() {
    return $this->controller->client()->request()
      ->staff()
      ->public()
      ->get();
  }

  /**
   * Allows use of PatronMessagesGet API method.
   */
  public function getMessages($unreadonly = true) {
    $endpoint = 'patron/' . $this->barcode . '/messages';
    $query = [
      'unreadonly' => $unreadonly,
    ];
    return $this->get()->path($endpoint)->query($query)->simple('PatronMessagesGetRows')->send();
  }

  /**
   * Allows use of PatronMessagesUpdateStatus API method.
   */
  public function updateMessage($messageid) {
    $endpoint = 'patron/' . $this->barcode . '/messages/freetext/' . $messageid;
    return $this->client->request()
      ->public()
      ->path($endpoint)
      ->staff()
      ->put()
      ->send();
  }

  /*
   * Allows use of PatronNotesGet API method.
   */
  public function getNotes() {
    return $this->get()->path($this->url() . '/notes')->simple('PatronNotes')->send();
  }

  /**
   * Allows use of the PatronUdfConfigsGet API method
   */
  public function getUDFs() {
    return $this->client->request()
      ->public()
      ->path('patronudfs')
      ->simple('PatronUdfConfigsRows')
      ->get()
      ->send();
  }
  
}
