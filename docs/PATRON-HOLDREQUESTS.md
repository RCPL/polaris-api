## Create a new hold request
```php
$patron = $client->patron->get([barcode]);

$hold = $patron->holdrequest->create([
  'BibID' => [bibid] 
]);
$hold->save();
```
## Cancel a hold request
```php
$hold = $patron->holdrequest->get([hold-request-id]);
$hold->cancel();
```

## Activate/suspend a hold request
```
$hold = $patron->holdrequest->get('2966401');
$hold->activate();
$hold->suspendUntil('P1Y');
```
