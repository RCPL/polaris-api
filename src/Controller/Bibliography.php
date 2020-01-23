<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;
use RCPL\Polaris\Entity\Bibliography as Entity;

class Bibliography extends ControllerBase {

  public function __construct(Client $client) {
    parent::__construct($client);
  }

  /**
   * @param int $id
   *
   * @param array $data
   *   Item data keyed by ElementID.
   * @param object $result
   *   BibSearchRow result, contains additional useful properties.
   */
  public function create(int $id, array $data = [], $result = NULL) {
    return new Entity($this, $id, $data, $result);
  }

  public function get($id, $result = NULL) {
    $data = $this->client->request()
      ->public()
      ->get()
      ->path('bib/' . $id)
      ->simple('BibGetRows')
      ->send();

    if (is_array($data)) {
      return $this->create($id, $data, $result);
    }
    
    return FALSE;
  }

  /**
   * @param string $text
   *   Text to search for (required).
   * @param array $params
   *   Search parameters, i.e. 'sortby'.
   * @param string $qualifier_name
   *   KW|TI|AU|SU|NOTE|PUB|GENRE|SE|ISBN|ISSN|LCCN|PN|LC|DD|LOCAL|SUDOC|CODEN|STRN|CN|BC.
   * @param bool $full
   *   if TRUE returns a full Bibliography Entity per result.
   */
  public function search($text, array $params = [], $qualifier_name = 'keyword/ti', $full = FALSE) {
    $endpoint = 'search/bibs/' . $qualifier_name;
    $params['q'] = $text;
    // Fix for recordset, UPC, etc. searches. Keep the equals sign.
    if ($qualifier_name == 'boolean') {
      $params['q'] = str_replace('%3D', '=', $params['q']);
    }
    $response = $this->client->request()
      ->public()
      ->path($endpoint)
      ->query($params, PHP_QUERY_RFC3986)
      ->get()
      ->send();

    if ($full) {
      $bib = $this;
      if (is_object($response)) {
        $bib_search_rows = [];
        foreach ($response->BibSearchRows as $item) {
          $bib_search_rows[] = $bib->get($item->ControlNumber, $item);
        }
        $response->BibSearchRows = $bib_search_rows;
      }
    }

    return $response;
  }

  /**
   * @param array $bib_ids
   *   Bibliographic ID values to pull MARC records for.
   */
  public function marc($bib_ids) {
    $q = implode(',', $bib_ids);
    $result = $this->client->request()
      ->protected()
      ->token()
      ->get()
      ->path('synch/bibs/MARCXML')
      ->query(['bibids' => $q], PHP_QUERY_RFC3986)
      ->send();
    return $result->GetBibsByIDRows;
  }

  /**
   * @param string $deletedate
   *   Start date and time (records that have been deleted since this
   *   date/time). Format: MM/DD/YYYY HH:MM:SS.
   */
  public function getDeletedBibs($deletedate) {
    $result = $this->client->request()
      ->protected()
      ->token()
      ->get()
      ->path('synch/bibs/deleted')
      ->query(['deletedate' => $deletedate], PHP_QUERY_RFC3986)
      ->send();
    return $result->BibIDListRows;
  }

  /**
   * @param string $updatedate
   *   Start date for record updates. Format: MM/DD/YYYY HH:MM:SS.
   */
  public function getUpdatedBibs($updatedate) {
    $result = $this->client->request()
      ->protected()
      ->token()
      ->get()
      ->path('synch/bibs/updated')
      ->query(['updatedate' => $updatedate], PHP_QUERY_RFC3986)
      ->send();
    return $result->BibIDListRows;
  }
}
