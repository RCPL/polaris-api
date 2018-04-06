<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;

class Bibliography extends ControllerBase {

  private $id;

  public function __construct(Client $client, $id = NULL, array $data = []) {
    parent::__construct($client);
    $this->id = $id;
    $this->setData($data);
  }

  public function get($id) {
    $data = $this->client->request()
      ->public()
      ->get()
      ->path('bib/' . $id)
      ->simple('BibGetRows')
      ->send();
    return new static($this->client, $id, $data);
  }

  private function setData(array $data = []) {
    foreach ($data as $row) {
      $this->data[$row->ElementID] = $row;
    }
  }

  /**
   * @param string $text
   *   Text to search for (required)
   * @param array $params
   *   Search parameters, i.e. 'sortby'
   * @param string $qualifier_name
   *   KW|TI|AU|SU|NOTE|PUB|GENRE|SE|ISBN|ISSN|LCCN|PN|LC|DD|LOCAL|SUDOC|CODEN|STRN|CN|BC
   */
  public function search($text, array $params = [], $qualifier_name = 'keyword/ti') {
    $endpoint = 'search/bibs/' . $qualifier_name;
    $params['q'] = $this->encode($text);
    // Fix for recordset searches. Keep the equals sign.
    if ($qualifier_name == 'boolean') {
      $params['q'] = str_replace('BRS%3D', 'BRS=', $params['q']);
    }
    return $this->client->request()
      ->public()
      ->path($endpoint)
      ->query($params)
      ->get()
      ->simplify('BibSearchRows')
      ->send();
  }

  public function holdings() {
    $endpoint = 'bib/' . $this->id . '/holdings';
    return $this->client->request()
      ->staff()
      ->public()
      ->path($endpoint)
      ->query($query)
      ->get()
      ->send();
  }
}
