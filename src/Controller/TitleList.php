<?php

namespace RCPL\Polaris\Controller;

use RCPL\Polaris\Client;
use RCPL\Polaris\Entity\TitleList as Entity;
use RCPL\Polaris\PatronAwareTrait;

/**
 * Actions for creating/removing hold requests for customers.
 */
class TitleList extends ControllerBase {

  use PatronAwareTrait;

  private $data;

  public function url() {
    return $this->patron->url();
  }

  public function get($id) {
    $data = $this->getLists();
    return !isset($data[$id]) ? $data[$id] : FALSE;
  }

  /**
   * Gets a list of all of a particular customer's lists.
   */
  public function getLists() {
    if (!isset($this->data)) {
      $this->data = [];
      $result = $this->client->request()
        ->staff()
        ->public()
        ->get()
        ->path($this->url() . '/patronaccountgettitlelists')
        ->simple('PatronAccountTitleListsRows')
        ->send();
      foreach ($result as $list) {
        $this->data[$list->RecordStoreID] = new Entity($this, (array) $list);
      }
    }
    return $this->data;
  }

  /**
   * Creates a list with the given name
   */
  public function create($list_name) {
    $endpoint = $this->url() . '/patronaccountcreatetitlelist';
    $config = [
      'json' => [
        'RecordStoreName' => $list_name,
      ],
    ];
    $response = $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->config($config)
      ->post()
      ->send();
    if ($response->PAPIErrorCode === 0) {
      $this->getLists();
      return $this->getByName($list_name);
    }
    return FALSE;
  }

  public function getByName($list_name) {
    $filter = array_filter($this->getLists(), function($list) use ($list_name) {
      return $list->name() == $list_name;
    });
    return !empty($filter) ? reset($filter) : FALSE;
  }

  /**
   * Deletes a list.
   */
  public function delete($list_id) {
    $endpoint = $this->url() . '/patronaccountdeletetitlelist';
    $query = [
      'list' => $list_id
    ];
    unset($this->data[$list_id]);
    return $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->query($query)
      ->delete()
      ->send();
  }

  /**
   * Gets all of the titles in a particular list.
   */
  public function getTitles($list_id) {
    $endpoint = $this->url() . '/patrontitlelistgettitles';
    $query = [
      'list' => $list_id
    ];
    return $this->client->request()
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
  public function addTitle($list_id, $item_id) {
    $endpoint = $this->url() . '/patrontitlelistaddtitle';
    $config = [
      'json' => [
        'RecordStoreID' => $list_id,
        'LocalControlNumber' => $item_id
      ],
    ];
    return $this->client->request()
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
  function deleteTitle($list_id, $position_id) {
    $endpoint = $this->url() . '/patrontitlelistdeletetitle';
    $query = [
      'list' => $list_id,
      'position' => $position_id
    ];
    return $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->query($query)
      ->delete()
      ->send();
  }

  /**
   * Removes all the records from a given list, but leaves the empty list
   * in the patron account.
   */
  function deleteAllTitles($list_id) {
    $endpoint = $this->url() . '/patrontitlelistdeletealltitles';
    $query = [
      'list' => $list_id
    ];
    return $this->client->request()
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
  function moveTitle($list_id_from, $position_id, $list_id_to) {
    $endpoint = $this->url() . '/patrontitlelistmovetitle';
    $config = [
      'json' => [
        'FromRecordStoreID' => $list_id_from,
        'FromPosition' => $position_id,
        'ToRecordStoreID' => $list_id_to
      ],
    ];
    return $this->client->request()
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
  function copyTitle($list_id_from, $position_id, $list_id_to) {
    $endpoint = $this->url() . '/patrontitlelistcopytitle';
    $config = [
      'json' => [
        'FromRecordStoreID' => $list_id_from,
        'FromPosition' => $position_id,
        'ToRecordStoreID' => $list_id_to
      ],
    ];
    return $this->client->request()
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
  function copyAllTitles($list_id_from, $list_id_to) {
    $endpoint = $this->url() . '/patrontitlelistcopyalltitles';
    $config = [
      'json' => [
        'FromRecordStoreID' => $list_id_from,
        'ToRecordStoreID' => $list_id_to
      ],
    ];
    return $this->client->request()
      ->public()
      ->staff()
      ->path($endpoint)
      ->config($config)
      ->post()
      ->send();
  }

}
