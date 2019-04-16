# Polaris-API
A PHP class built for interacting with the Polaris API. Polaris is a product
from Innovative Interfaces.

## Prerequisites
1. You should work with your Polaris Site Manager to get an account on the
Polaris Developer Network (http://developer.polarislibrary.com/).
2. You should receive an API Access ID and API Access Key for use with your
system. Make note of those.
3. Create a special account & password on your network that is just a basic
domain user.
4. Create a matching account & password in Polaris that has the same permissions
as a basic circulation account. Make note of the Polaris User ID number.

The reason for steps 3 and 4 according to the Polaris 5.0 documentation
(available at the URL above):

>The PAPI Service supports protected methods. These are functions that might be
>performed by a staff member and not a patron. The staff member must
>authenticate with the PAPI service [...] over a secure connection. Domain
>account information will be exchanged and verified only once. If successful,
>the user will be provided an AccessToken and AccessSecret which will be used
>for the remainder of their operations.

## Installation
```
git clone --branch 2.x https://github.com/RCPL/polaris-api.git
composer install
```

## Usage

Please see the included **example.php** for example implementation code.

```
use RCPL\Polaris\Client;

$client = new Client([
  'ACCESS_ID'      => '< your info here >',
  'ACCESS_KEY'     => '< your info here >',
  'HOST'           => '< your info here >',
  'STAFF_DOMAIN'   => '< your info here >',
  'STAFF_ID'       => '< your info here >',
  'STAFF_USERNAME' => '< your info here >',
  'STAFF_PASSWORD' => '< your info here >',
  'WORKSTATION_ID' => '< your info here >'
]);

$patron = $client->patron->get('< library card number >');
var_dump($patron->data());
```

Most of the methods are documented within the class file and provide information
about the required parameters and return values.

**Note:** There are a few methods available in the API that have not yet been
fully accounted for in the class file such as a few of the My Lists methods.
If you discover one of these that's incomplete that you need help with please
either A) contribute using the method outlined below or B) open an issue on
the Github issue queue and request support with it.

## Advanced Usage

* [Patron](docs/PATRON.md)
* [Patron - Hold Requests](docs/PATRON-HOLDREQUESTS.md)
* [Bibliography](docs/BIB.md)

## Contributing
1. Fork it!
2. Create your feature branch: `git checkout -b my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin my-new-feature`
5. Submit a pull request :D

## BrowserStack
Richland Library tests on multiple browsers and devices using BrowserStack. Find out more at [browserstack.com](https://www.>browserstack.com).
<a href="https://www.browserstack.com"><img src="https://www.browserstack.com/images/layout/browserstack-logo-600x315.png" width="150" alt="BrowserStack Logo" /></a>
