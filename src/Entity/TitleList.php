<?php

namespace RCPL\Polaris\Entity;

use RCPL\Polaris\Client;

class TitleList extends EntityBase {


  /**
   * Get current Title List name.
   */
  public function name() {
    return !empty($this->data['RecordStoreName']) ? $this->data['RecordStoreName'] : '';
  }

  /**
   * Get current Record Store ID.
   */
  public function id() {
    return !empty($this->data['RecordStoreID']) ? $this->data['RecordStoreID'] : FALSE;
  }

  /**
   * Gets all of the titles in a particular list.
   */
  public function getTitles() {
    $endpoint = $this->url() . '/patrontitlelistgettitles';
    $query = [
      'list' => $this->id()
    ];
    return $this->client->createRequest()
      ->public()
      ->staff()
      ->path($endpoint)
      ->query($query)
      ->get()
      ->simple('PatronTitleListTitleRows')
      ->send();
  }

  /**
   * Adds the specified bib record to the specified list in the patron account.
   */
  public function addTitle($item_id) {
    $endpoint = $this->url() . '/patrontitlelistaddtitle';
    $config = [
      'json' => [
        'RecordStoreID' => $this->id(),
        'LocalControlNumber' => $item_id
      ],
    ];
    return $this->client->createRequest()
      ->public()
      ->staff()
      ->path($endpoint)
      ->config($config)
      ->post()
      ->send();
  }

  /**
   * Deletes a single specified bibliographic record from the specified list
   * in the patron account.
   */
  function deleteTitle($position_id) {
    $endpoint = $this->url() . '/patrontitlelistdeletetitle';
    $query = [
      'list' => $this->id(),
      'position' => $position_id
    ];
    return $this->client->createRequest()
      ->public()
      ->staff()
      ->path($endpoint)
      ->query($query)
      ->delete()
      ->send();
  }

  function deleteTitleByControlNumber() {

  }

  function deleteTitleByRecordID() {

  }

  /**
   * Removes all the records from a given list, but leaves the empty list
   * in the patron account.
   */
  function deleteAllTitles() {
    $endpoint = $this->url() . '/patrontitlelistdeletealltitles';
    $query = [
      'list' => $this->id()
    ];
    return $this->client->createRequest()
      ->public()
      ->staff()
      ->path($endpoint)
      ->query($query)
      ->delete()
      ->send();
  }

  /**
   * Moves a specified bibliographic record from one title list to another;
   * both title lists are in the same patron account.
   */
  function moveTitle($position_id, $list_id_to) {
    $endpoint = $this->url() . '/patrontitlelistmovetitle';
    $config = [
      'json' => [
        'FromRecordStoreID' => $this->id(),
        'FromPosition' => $position_id,
        'ToRecordStoreID' => $list_id_to
      ],
    ];
    return $this->client->createRequest()
      ->public()
      ->staff()
      ->path($endpoint)
      ->config($config)
      ->post()
      ->send();
  }

  /**
   * Copy a title from one list to another in a patron account.
   */
  function copyTitle($position_id, $list_id_to) {
    $endpoint = $this->url() . '/patrontitlelistcopytitle';
    $config = [
      'json' => [
        'FromRecordStoreID' => $this->id(),
        'FromPosition' => $position_id,
        'ToRecordStoreID' => $list_id_to
      ],
    ];
    return $this->client->createRequest()
      ->public()
      ->staff()
      ->path($endpoint)
      ->config($config)
      ->post()
      ->send();
  }

  /**
   * Copy all titles from one list to another list in the patron account.
   */
  function copyAllTitles($list_id_to) {
    $endpoint = $this->url() . '/patrontitlelistcopyalltitles';
    $config = [
      'json' => [
        'FromRecordStoreID' => $this->id(),
        'ToRecordStoreID' => $list_id_to
      ],
    ];
    return $this->client->createRequest()
      ->public()
      ->staff()
      ->path($endpoint)
      ->config($config)
      ->post()
      ->send();
  }

  function delete() {
    if ($this->id()) {
      $this->controller->delete($this->id());
    }
  }
}
