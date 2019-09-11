# Yii2 REST API template

### Features:

* rest and console apps
* Gii with REST Controllers generator
* CRC32 sign for requests with public key
* DB Trees: Materialized Path Behaviors included
* FlameArt Advanced migration generator tool included, can generate migration for tables and data automatically

## REST

REST accepts JSON-based requests, no need specify page-size or fields in url (bad practice), all in json in axios-style

###### WHERE

Filled by default with AND, and you can use `Yii2 where arrays` for custom conditions

```
{
    where: ['OR', 
        ['AND', 
            ['active'=>1],
            ['user'=>5]
        ],
        ['user'=>6]
    ]
}
```

###### ExtendQuery

You can use extend function `ExtendQuery` to specify custom actions for each Controller model

###### Find in MySQL JSON-fields

App supports search JSON-fields in SQL with `JSON_CONTAINS` (equivalent OR) for each element in array (MySQL 5.7+)

```
{
    where: [
        json_field: [1,3,5]
    ]
}
```

### Relations

Classic `expand` method in Yii2 REST do 15 requests + one request per each table row for each relation. Thats so much. It was rewritten.

Old mechanic is saved, you can use url param `expand`.

New mechanic use `LEFT JOIN` for one request (total 8 requests), then add to output the field: `Fieldname_`. 

For use it you must send request with field names in `expand` object:

```
{
    expand: ['relatedfield','country']
}
```

or select fields in array `$extendFields` variable in controller of table

```
public $extendFields = ['relatedfield','country'];
```

Both variants generating the output:

```
...
data: [{
    id:5,
    relatedfield: 10,
    country: 12,
    relatedfield_: {
        id: 14,
        type: LULZZ
    },
    country_: {
        id: 14,
        name: Belarus,
        alfa2: BY
    },
}]
...
```

**Performance notice:**

Yii2 do 
* URL param `expand`: 15 queries + row*expanded_field_count
* `->with()`: 14 queries
* `LEFT JOIN`: 8 queries  (1 real query + counts for pagination)

