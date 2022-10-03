<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;
use RCPL\Polaris\Utility\Parameters;

class Recordset extends ControllerBase {

  /**
   * @var Parameters
   */
  private $params;

  public function __construct(Client $client) {
    parent::__construct($client);
  }
  
  /**
   * Implements RecordSetRecordsGet from Polaris API.
   */
  public function getRecordset($recordset_id) {
    $endpoint = 'recordsets/' . $recordset_id . '/records';
    return $this->client->request()
      ->protected()
      ->token()
      ->path($endpoint)
      ->query([
        'wsid' => $this->client->params->get('WORKSTATION_ID'),
        'userid' => 1,
        'startIndex' => 0,
        'numRecords' => 500,
      ])
      ->get()
      ->simple('RecordSetRecordsGetRows')
      ->send();
  }

  /**
   * Implements RecordSetContentPut from Polaris API.
   */
  public function putRecordset($recordset_id, $record_values) {
    $endpoint = 'recordsets/' . $recordset_id;
    $config = [
      'json' => [
        'Records' => implode(',', $record_values),
      ],
    ];
    return $this->client->request()
      ->protected()
      ->token()
      ->path($endpoint)
      ->query([
        'wsid' => $this->client->params->get('WORKSTATION_ID'),
        'userid' => 1,
        'action' => 'add',
      ])
      ->config($config)
      ->put()
      ->send();
  }

  /**
   * Alternate implementation of RecordSetContentPut from Polaris API
   */
  public function clearRecordset($recordset_id) {
    // Get the recordset values first
    $records = $this->getRecordset($recordset_id);
    $record_values = [];
    foreach ($records as $record) {
      $record_values[] = $record->RecordID;
    }
    if (!empty($record_values)) {
      $config = [
        'json' => [
          'Records' => implode(',', $record_values),
        ],
      ];
      // Then tell it to remove those from the recordset.
      $endpoint = 'recordsets/' . $recordset_id;
      return $this->client->request()
        ->protected()
        ->token()
        ->path($endpoint)
        ->query([
          'wsid' => $this->client->params->get('WORKSTATION_ID'),
          'userid' => 1,
          'action' => 'remove',
        ])
        ->config($config)
        ->put()
        ->send();
    }
  }

}
