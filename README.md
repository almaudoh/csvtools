## CSV Tools
Simple PHP based classes for importing, parsing and structuring CSV files and data.

It is made up of just two files, which contain a parser and then a mapper. The parser class parses the CSV files, which the mapper class allows mapping of the CSV file headers to another set of keys.
The mapper class provides allows iteration over the CSV data and array access to each of the columns. The mapper implements `\Iterator`, `\ArrayAccess` and `\Countable`.

Example usages:
#### filename.csv
```
NAME,MOBILE,MOBILE2,EMAIL,CITY,COUNTRY,BIRTH_DAY,WORK,NOTES,ACTIVE_ROLES,WANTED_ROLES
Jolly,2348030783839,,noreply@example.com,NoCity,NoCountry,38758,My work,My Notes,,
Nolly,2348038983839,2348030783839,noreply@example.com,NoCity,NoCountry,38758,My work,My Notes,,
,,,,,,,,,,
Polly,2348030783839,2348030783839,noreply@example.com,NoCity,NoCountry,38758,My work,My Notes,,
Solly,2348030783457,2348030783839,3reply@example.com,NoCity,NoCountry,38758,My work,My Notes,,
,,,,,,,,,,
,,,,,,,,,,
Lolly,2347090783839,,noreply@example.com,NoCity,NoCountry,38758,My work,My Notes,,
Wolly,2347090783234,,1reply@example.com,NoCity,NoCountry,38758,My work,My Notes,,
,,,,,,,,,,
```


```php
$mapping = [
    'name' => 'NAME',
    'phone' => 'MOBILE',
    'phone2' => 'MOBILE2',
    'email' => 'EMAIL',
    'city' => 'CITY',
    'country' => 'COUNTRY',
    'dob' => 'BIRTH_DAY',
    'address' => 'WORK',
    'notes' => 'NOTES',
    'roles' => 'ACTIVE_ROLES',
    'additional_roles' => 'WANTED_ROLES',
];
$csv = (new CsvDataListMapper())
            ->setSourceFile('filename.csv')
            ->setDataMap($mapping);
foreach ($csv as $record) {
    write_to_database($record);
    print($record['name']);
}
```

prints
```
Jolly
Nolly

Polly
Solly


Lolly
Wolly

```
