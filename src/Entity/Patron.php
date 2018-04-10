<?php

namespace RCPL\Polaris\Entity;

use RCPL\Polaris\Client;
use RCPL\Polaris\Controller\Patron as Controller;
use RCPL\Polaris\Request;

class Patron extends EntityBase {

  /**
   * Valid values to send for a PatronUpdate request.
   *
   * @var array
   */
  private $updateable = [];

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
      throw new Exception('Missing value barcode');
    }
    $this->barcode = $this->data['barcode'];
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

  /**
   * Do not use directly, use $this->holdrequest.
   *
   * @see $this->__get()
   *
   * @return \RCPL\Polaris\Controller\HoldRequest
   */
  protected function holdRequestController() {
    if (!isset($this->holdRequestController)) {
      $this->holdRequestController = $this->client->holdrequest->init($this);
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
      $this->titleListController = $this->client->titlelist->init($this);
    }
    return $this->titleListController;
  }

  /**
   * Populate updateable Patron data.
   *
   * @param $key
   * @param $value
   * @return bool
   */
  public function __set($key, $value) {
    if ($this->controller->isUpdateable($key)) {
      $this->updateable[$key] = $value;
    }
    return FALSE;
  }

  public function __get($key) {
    if ($this->controller->isUpdateable($key) && isset($this->updateable[$key])) {
      return $this->updateable[$key];
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

  public function data() {
    $endpoint = 'patron/' . $this->barcode . '/basicdata';
    $query = [
      'addresses' => 1
    ];
    return $this->get()->path($endpoint)->query($query)->simple('PatronBasicData')->send();
  }

  public function holdRequests($type = 'all') {
    return $this->holdRequest->getByType($type);
  }

  public function titleLists() {
    return $this->titlelist->getLists();
  }

  public function itemsOut($type = 'all') {
    return $this->get()->path($this->url()  . '/itemsout/' . $type)->simple('PatronItemsOutGetRows')->send();
  }

  public function preferences() {
    return $this->get()->path($this->url() . '/preferences')->simple('PatronPreferences')->send();
  }

  public function account() {
    return $this->get()->path($this->url()  . '/account/outstanding')->simple('PatronAccountGetRows')->send();
  }

  public function fines() {
    $endpoint = 'patron/' . $this->barcode  . '/account/outstanding';
    return $this->get()->path($endpoint)->simple('PatronAccountGetRows')->send();
  }

  public function update() {
    $values = array_filter(array_merge($this->controller->updateable(), $this->updateable));
    $endpoint = 'patron/' . $this->barcode;
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

  public function itemCheckout() {
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
        ]
      ])
      ->path($this->url()  . '/itemsout/' . $item_id)
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

}
