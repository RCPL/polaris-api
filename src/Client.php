<?php

namespace RCPL\Polaris;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\UriTemplate\UriTemplate;
use Psr\Http\Message\ResponseInterface;
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
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Polaris parameters.
   *
   * @var \RCPL\Polaris\Utility\Parameters
   */
  protected $params;

  /**
   * @var \GuzzleHttp\UriTemplate
   */
  protected $uri;

  /**
   * String template for UrlTemplate class.
   *
   * @var string
   */
  public $template = '{base}{+rest}{/type}{/version}{/lang-id}{/app-id}{/org-id}{/access-token}{+path}';

  /**
   * @var mixed
   */
  public $date;

  /**
   * @param array $params
   *
   * @return Parameters
   */
  public function parameters(array $params) {
    return new Parameters($params);
  }

  /**
   * Client constructor.
   */
  public function __construct(array $params) {
    // https://catalog.yourlibrary.com/PAPIService/REST/public/v1/1033/100/1/search/headings/TI?startpoint=1&numterms=50&preferredpos=1
    $this->params = $this->parameters($params);

    $this->uri = $this->parameters([
      'version' => 'v1',
      'rest' => '/PAPIService/REST',
      'lang-id' => '1033',
      'app-id' => '100',
      'org-id' => '1',
    ]);

    $this->date = str_replace('+0000', 'GMT', gmdate('r'));

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
      'timeout' => 30,
    ];
    parent::__construct($config);
  }

  /**
   * Used by controllers to initiate a new request.
   *
   * @return mixed|Request
   */
  public function createRequest() {
    return new Request($this);
  }

  /**
   * @return UriTemplate
   */
  public function template() {
    return new UriTemplate();
  }

  /**
   * @return Parameters
   */
  public function params() {
    return $this->params;
  }

  /**
   * Magic method to dynamically handle controller classes.
   *
   * @param $name
   *   Name of controller class in lower case.
   * @return bool
   */
  public function __get($name) {
    $class_name = ucwords($name);
    if (isset($this->{$name})) {
      return $this->{$name};
    }
    if ($class = $this->getControllerClass($class_name)) {
      return $this->setControllerClass($name, $class);
    }
    return FALSE;
  }

  /**
   * @param $name
   * @param $class
   * @return bool
   */
  protected function setControllerClass($name, $class) {
    $this->{$name} = new $class($this);
    return $this->{$name};
  }

  /**
   * @param $name
   * @return bool|string
   */
  protected function getControllerClass($name) {
    $class = FALSE;
    if (class_exists('\RCPL\Polaris\Controller\\' . $name)) {
      $class = '\RCPL\Polaris\Controller\\' . $name;
    }
    return $class;
  }

  /**
   * @return Parameters
   */
  public function uri() {
    return $this->uri;
  }

  /**
   * @param array $params
   *
   * @return $this
   */
  public function setUri(array $params) {
    $this->uri = $this->parameters($params);
    return $this;
  }

  /**
   * Build polaris signature for auth header.
   *
   * @param string $http_method
   * @param string $url
   * @param string $date
   * @param string $pass
   * @param string $access_secret
   *
   * @return string
   */
  public function signature($http_method, $url, $date, $pass = '', $access_secret = '') {
    $signature = $http_method . $url . $date . $pass . $access_secret;
    return base64_encode(hash_hmac('sha1', $signature, $this->params->get('ACCESS_KEY'), TRUE));
  }

}
