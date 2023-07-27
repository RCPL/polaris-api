<?php

namespace RCPL\Polaris;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use RCPL\Polaris\Utility\Parameters;

class Request {

  /**
   * @var Client
   */
  protected $client;

  /**
   * @var Parameters
   */
  protected $config;

  /**
   * Request method
   *
   * @var string GET|POST|PUT|DELETE
   */
  protected $method;

  /**
   * API call type
   *
   * @var string public|protected
   */
  protected $type;

  /**
   * Requires staff authentication
   *
   * @var bool
   */
  protected $staff = FALSE;

  /**
   * Current request path
   *
   * @var string
   */
  protected $path;

  /**
   * Response format
   *
   * @var string
   */
  protected $format = 'json';

  /**
   * TODO: should probably be refactored to be a param.
   */
  protected $ssl;

  /**
   * @var string
   */
  protected $uri;

  /**
   * The key to return from the response to simplify it.
   *
   * @var string
   */
  protected $responseKey;

  /**
   * Request constructor.
   *
   * @param Client $client
   */
  public function __construct(Client $client) {
    $this->client = $client;
    $this->params = $client->params()->clone();
    $this->uri = $client->uri()->clone();
    $this->config = $client->parameters([]);
    $this->ssl = TRUE;
  }

  /**
   * Add query string parameters to request.
   *
   * @param array|string $query
   *
   * @param string $encoding
   *
   * @return $this
   */
  public function query($query, $encoding = NULL) {
    $this->config->set('query', $query);
    if (isset($encoding)) {
      $this->config->set('query_encoding', $encoding);
    }
    return $this;
  }

  /**
   * Make request a GET request.
   *
   * @return $this
   */
  public function get() {
    $this->method = 'GET';
    return $this;
  }

  public function put() {
    $this->method = 'PUT';
    return $this;
  }

  /**
   * @return $this
   */
  public function post() {
    $this->method = 'POST';
    return $this;
  }

  /**
   * @return $this
   */
  public function delete() {
    $this->method = 'DELETE';
    return $this;
  }

  /**
   * @param string|array $path
   *
   * @return $this
   */
  public function path($path) {
    $this->path = is_array($path)
      ? implode('/', array_map([$this, 'encode'], $path))
      : $path;
    return $this;
  }

  /**
   * @alias for ::path
   */
  public function endpoint($path) {
    return $this->path($path);
  }

  /**
   * @return $this
   */
  public function public() {
    $this->uri->set('type', 'public');
    return $this;
  }

  /**
   * @return $this
   */
  public function protected() {
    $this->uri->set('type', 'protected');
    return $this;
  }

  /**
   * @return $this
   */
  public function noSsl() {
    $this->ssl = FALSE;
    return $this;
  }

  /**
   * Enable or disable ssl for this request.
   *
   * @param bool $enabled
   *
   * @return $this
   */
  public function ssl($enabled = TRUE) {
    $this->ssl = $enabled;
    return $this;
  }

  /**
   * @param array $config
   * @return $this
   */
  public function config(array $config = []) {
    $this->config->merge($config);
    return $this;
  }

  /**
   * @return $this
   */
  public function staff() {
    $this->config->set('headers', [
      'X-PAPI-AccessToken' => $this->client->staff->auth()->AccessToken,
    ]);
    $this->config->set('access_secret', $this->client->staff->auth()->AccessSecret);
    return $this;
  }

  /**
   * @return $this
   */
  public function token() {
    $this->uri->set('access-token', $this->client->staff->auth()->AccessToken);
    return $this->staff();
  }

  /**
   * Simplify response output.
   */
  public function simple($key) {
    $this->responseKey = $key;
    return $this;
  }

  /**
   * @alias $this->simple()
   */
  public function simplify($key) {
    return $this->simple($key);
  }

  /**
   * Send final request, this is the last step in the chain.
   *
   * @return mixed
   */
  public function send() {
    $this->uri->set('base', $this->params->get('HOST'));
    $uri = Uri::fromParts([
      'scheme' => $this->ssl ? 'https' : 'http',
      'host' => $this->host(),
    ]);
    $this->config->set('base_uri', strtolower($uri->__toString()) . '/');
    $full = $uri->withPath('/' . $this->path)->withQuery($this->buildQuery());

    $signature = $this->client->signature($this->method, $full->__toString(), $this->client->date, NULL, $this->config->get('access_secret', ''));
    $headers = $this->config->get('headers', []);
    $headers['Authorization'] = 'PWS ' . $this->client->params()->get('ACCESS_ID') . ':' . $signature;
    $this->config->set('headers', $headers);
    $options = (array) $this->config;
    $response = $this->json($this->client->{strtolower($this->method)}($this->path, $options));
    return !empty($this->responseKey) ? $response->{$this->responseKey} : $response;
  }

  private function buildQuery() {
    if (is_string($this->config->get('query'))) {
      return $this->config->get('query');
    }
    $query = $this->config->get('query') ? $this->config->get('query') : [];
    $encoding = $this->config->get('query_encoding');
    return !empty($encoding)
      ? http_build_query($query, '', '&', $encoding)
      : http_build_query($query);
  }

  private function host() {
    return $this->client->template()->expand($this->client->template, $this->uri->toArray());
  }

  private function xml(Response $request) {
    // TODO
  }

  private function json(Response $request) {
    return json_decode($request->getBody()->getContents());
  }

  private function encode($string) {
    return urlencode($string);
  }

}
