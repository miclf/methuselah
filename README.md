# Methuselah

Methuselah is a set of web scrapers to read data from the official websites of the Belgian federal parliament.

It can be used like this:

```php
$scraper = $container->make('ChamberMPList');

$data = $scraper->setOptions($options)->scrape();
```

Here is a fully working and more verbose example:

```php
// Require the Composer autoloader.
require_once '../vendor/autoload.php';

// Create an instance of the container.
$container = new Illuminate\Container\Container;

// Instantiate a scraper to get all the current members of the parliament.
$scraper = $container->make('Pandemonium\Methuselah\Scrapers\K\MPList');

// Options can be set on scrapers.
$scraper->setOptions(['legislature_number' => 54]);

// Get an array of data. All the scrapers have a public 'scrape()' method.
$list = $scraper->scrape();

// Display the data as JSON.
echo json_encode($list);
```

The current list of info that can be retrieved is described below. The **K** letter means that the feature is available for the Chamber of Representatives (*Kamer*), while the **S** stands for the Senate.

- List of the current members of the parliament (K, S)
- List of members who seated during previous legislatures or who seated earlier in the current legislature (K, S)
- Personal info of a member (K)
- List of existing committees (K)
- Details and seats of a committee (K)

These features and their options are described in more details further below.


## Table of contents

- [Technical details](#technical-details)
- [Dependencies](#dependencies)
- [Instantiating scrapers](#instantiating-scrapers)
- [Available scrapers](#available-scrapers)


## Technical details

This project requires **PHP 5.4** or a newer version. It can be installed with the [Composer package manager](http://getcomposer.org/).


## Dependencies

Methuselah depends on the following Composer packages:

- `guzzlehttp/guzzle` to get web pages from the websites
- `illuminate/container` to manage IoC concerns
- `illuminate/support` for various helpers
- `symfony/css-selector` to query DOM nodes using CSS selectors
- `symfony/dom-crawler` to select and work with DOM nodes


## Instantiating scrapers

Methuselah uses the [Illuminate Container component](https://github.com/illuminate/container) to make it easy to instantiate scrapers. To use it, simply create an instance of the container and call its `make` method to instantiate any scraper.

```php
$container = new Illuminate\Container\Container;

// Instantiate a scraper to get all the current members of the parliament.
$scraper = $container->make('Pandemonium\Methuselah\Scrapers\K\MPList');
```

You can also register scraper classes in the container and `make` them with a simpler name of your choice:

```php
$container = new Illuminate\Container\Container;

// Bind the class to the container with a custom name.
$container->bind(['Pandemonium\Methuselah\Scrapers\K\MPList' => 'ChamberMPList']);

// Instantiate a scraper to get all the current members of the parliament.
$scraper = $container->make('ChamberMPList');
```


## Available scrapers

What follows is a detailed list of the different scrapers and their parameters.

### Chamber of Representatives

#### `\Pandemonium\Methuselah\Scrapers\K\MPList`

Without any parameter, it gets the list of *current* members of the Chamber. When provided a legislature number, it gets the list of *all the MPs who seated* during that legislature.

Required parameters: none.

Optional parameters:
- `legislature_number`: number of the legislature of which the list of MPs is needed.

Example of returned array (shortened to two MPs for clarity):

```json
[
    {
        "identifier": "12345",
        "surname_given_name": "Doe John"
    },
    {
        "identifier": "54321",
        "surname_given_name": "Doe Jane"
    }
]
```


#### `\Pandemonium\Methuselah\Scrapers\K\MP`

This scraper gets info associated to a single member of the Chamber.

Required parameters:
- `identifier`: the Chamber identifier of the member.

Optional parameters:
- `legislature_number`: number of the legislature from which data should be extracted of. Defaults to `54`. There should be no need to set this parameter, since the most recent data is generally what is wanted.

Example of returned array:

```json
{
    "address": null,
    "birthdate": "1969-01-01",
    "committees": {
        "1234": [
            "president",
            "member"
        ],
        "2345": [
            "member"
        ],
        "3456": [
            "member"
        ]
    },
    "email": "john.doe@lachambre.be",
    "gender": "m",
    "given_name_surname": "John Doe",
    "identifier": "12345",
    "lang": "fr",
    "legislatures": [
        "52",
        "53",
        "54"
    ],
    "party": "Cat Party",
    "website": "www.johndoe.be"
}
```

#### `\Pandemonium\Methuselah\Scrapers\K\CommitteeList`

This retrieves the list of *current* working groups and committees of the Chamber. The website provides no way to get the list from a particular point in time.

Required parameters: none.

Optional parameters: none.

Example of returned array:

```json
[
    {
        "identifier": "12345",
        "type": "permanent",
        "name_fr": "…",
        "name_nl": "…"
    },
    {
        "identifier": "54321",
        "surname_given_name": "special",
        "name_fr": "…",
        "name_nl": "…"
    }
]
```


#### `\Pandemonium\Methuselah\Scrapers\K\Committee`

This gets the names of a committee and the existing seats, categorized by types and political groups. The array data of each seat attributed to a member of the Chamber also stores the full name and the identifier of this member.

Required parameters:
- `identifier`: the Chamber identifier of the group or committee.

Optional parameters: none.

Example of returned array (shortened for clarity):

```json
{
    "identifier": "1234",
    "name_fr": "…",
    "name_nl": "…",
    "presidents": [
        {
            "identifier": "12345",
            "given_name_surname": "John Doe",
            "political_group": "Cats"
        }
    ],
    "vice-presidents": [],
    "members": [
        {
            "identifier": "54321",
            "given_name_surname": "Jane Doe",
            "political_group": "Unicorns"
        }
    ],
    "substitutes": [
        {
            "identifier": "23456",
            "given_name_surname": "Bob Doe",
            "political_group": "Unicorns"
        }
    ]
}
```


### Senate

#### `\Pandemonium\Methuselah\Scrapers\S\MPList`

Without any parameter, it gets the list of *current* members of the Senate. When provided a legislature number, it gets the list of *all the senators who seated* during that legislature.

Required parameters: none.

Optional parameters:
- `legislature_number`: number of the legislature of which the list of senators is needed.

Example of returned array:

```json
[
    {
        "identifier": "12345",
        "surname_given_name": "Doe John"
    },
    {
        "identifier": "54321",
        "surname_given_name": "Doe Jane"
    }
]
```


#### `\Pandemonium\Methuselah\Scrapers\S\MP`

This scraper gets info associated to a single senator.

Required parameters:
- `identifier`: the Senate identifier of the senator.

Optional parameters: none.

Example of returned array:

```json
{
    "birthdate": "1969-01-01",
    "committees": {
        "1234": [
            "president",
            "member"
        ],
        "2345": [
            "member"
        ],
        "3456": [
            "member"
        ]
    },
    "gender": "m",
    "given_name_surname": "John Doe",
    "identifier": "12345",
    "legislatures": [
        "4",
        "5",
        "6"
    ],
    "origin": "Parliament of the Unicorn Community",
    "political_group": "Unicorns",
    "type": "federated entities"
}
```
