## Search by ISBN
```
$bibs = $client->bibliography->search('9781400083053', [], 'keyword/isbn');
```

## Search by Author
```
$bibs = $client->bibliography->search('Author name', [], 'keyword/au');
```

## Get Bibliography holdings
```
$bib = $client->bibliography->get('bibid');
$bib->holdings();
```
