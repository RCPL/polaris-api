# Polaris-API-PHP-Class
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
Simply add the included "pac_polaris.inc" file into your web code and start
using it!

## Usage

Please see the included **example.php** for example implementation code.

This stand-alone PHP class can be included in your website code with a simple
include statement such as:

```
include('pac_polaris.inc');
```

You'll also want to create an array within your code that contains all of your
API credentials like:

```
/* Important: Credentials below must be filled in! */
$GLOBALS['conf'] = array(
  'POLARIS_API_ACCESS_ID' => '', // Given to you by your Polaris Site Manager
  'POLARIS_API_ACCESS_KEY' => '', // Given to you by your Polaris Site Manager
  'POLARIS_API_HOST' => '', // The hostname (e.g., polaris.yourlibrary.com)
  'POLARIS_API_STAFF_DOMAIN' => '', // The network domain for protected methods
  'POLARIS_API_STAFF_ID' => '', // The Polaris account numeric id for protected methods
  'POLARIS_API_STAFF_USERNAME' => '', // The Polaris and matching network domain username
  'POLARIS_API_STAFF_PASSWORD' => '' // The Polaris and matching network domain password
);
```

Then, you can begin making calls to any of the API methods included in the class
similarly to this:

```
// Try pulling a list of 25 titles.
$query = 'q=' . urlencode('Harry Potter') . '&bibsperpage=25';
$result = PolarisAPI::searchBibs($query);
```

Most of the methods are documented within the class file and provide information
about the required parameters and return values.

**Note:** There are a few methods available in the API that have not yet been
fully accounted for in the class file such as a few of the My Lists methods.
If you discover one of these that's incomplete that you need help with please
either A) contribute using the method outlined below or B) open an issue on
the Github issue queue and request support with it.

## Contributing
1. Fork it!
2. Create your feature branch: `git checkout -b my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin my-new-feature`
5. Submit a pull request :D
