## Updating a Patron
```php
$patron = $client->patron->get([barcode]);
$patron->PhoneVoice2 = '123-456-7890';
$patron->update();
```
## Title list
```php
$patron = $client->patron->get([barcode]);
$patron->titleLists();

$list = $patron->titlelist->get([list-id]);
$list->delete();

$list = $patron->titlelist->getByName('Name of Title List');
$list->addTitle([bib-id]);
```

# TODO

## Patron
* registration / register / create

## Account
* pay

## Items
* renew
* checkout
* checkin






