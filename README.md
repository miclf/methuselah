# Methuselah

Methuselah is a set of web scrapers to read data from the official websites of the Belgian federal parliament.

It can be used like this:

```php
$scraper = $container->make('ChamberMPList');

$data = $scraper->setOptions($options)->scrape();
```

Here is a fully working and more verbose example, using an optional IoC container:

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

- List of current or past members of the parliament ([K](#mplist), [S](#mplist-1))
- Personal info of a member ([K](#mp), [S](#mp-1))
- Details of a dossier ([K](#dossier), [S](#dossier-1))
- List of existing committees ([K](#committeelist), [S](#committeelist-1))
- Details and seats of a committee ([K](#committee), [S](#committee-1))
- List of agenda pages of plenary sessions ([K](#plenaryagendalist))

These features and their options are described in more details further below.


## Table of contents

- [Technical details](#technical-details)
- [Dependencies](#dependencies)
- [Instantiating scrapers](#instantiating-scrapers)
- [Available scrapers](#available-scrapers)


## Technical details

This project requires **PHP 5.4** or a newer version. It can be installed with the [Composer package manager](http://getcomposer.org/).

There is no released version yet. To install this package, you need to require the development version in your `composer.json` file, like this:

```json
{
    "require": {
        "pandemonium/methuselah": "dev-master"
    }
}
```


## Dependencies

Methuselah depends on the following Composer packages:

- `guzzlehttp/guzzle` to get web pages from the websites
- `illuminate/container` to manage IoC concerns
- `illuminate/support` for various helpers
- `patchwork/utf8` to deal with encoding and character sets
- `symfony/css-selector` to query DOM nodes using CSS selectors
- `symfony/dom-crawler` to select and work with DOM nodes

This list is simply informative. These packages are automatically installed along Methuselah, you do not have to do anything special to get them. For more info, have a look at the [composer.json](./composer.json) file.


## Instantiating scrapers

Methuselah’s scrapers use *dependency injection* to manage relationships between different classes of the project. It is recommended to use an IoC container to avoid manually instantiating these dependencies. Different modern frameworks include a container, please see your framework’s documentation to learn how to use it.

In case you do not use a framework, Methuselah ships with the [Illuminate Container component](https://github.com/illuminate/container "View the project page on GitHub") to make it easy to instantiate scrapers. To use it, simply create an instance of the container and call its `make` method to instantiate any scraper.

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

// Do stuff…

// Instantiate a scraper to get all the current members of the parliament.
$scraper = $container->make('ChamberMPList');
```


## Available scrapers

What follows is a detailed list of the different scrapers and their parameters. All of them return *PHP arrays*.

That being said, examples of returned data are expressed in JSON for convenience and readability. These examples are also often shortened for brevity, because no one wants to read JSON dumps of hundreds of similar objects when displaying only two is enough to show how they look like.

### Chamber of Representatives

All of these scrapers live in the `\Pandemonium\Methuselah\Scrapers\K` namespace. In other words, to instanciate them, you need to preprend their name with this namespace.

#### MPList

Without any parameter, it gets the list of *current* members of the Chamber. When provided a legislature number, it gets the list of *all the MPs who seated* during that legislature.

##### Parameters

Name               | Type   | Description
-------------------|--------|------------
legislature_number | number | Number of the legislature of which the list of MPs is needed.

##### Example of returned data:

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

**********

#### MP

This scraper gets info associated to a single member of the Chamber.

##### Parameters

Name               | Type   | Description
-------------------|--------|------------
identifier         | string | **Required**. The Chamber identifier of the member.
legislature_number | number | Number of the legislature from which data should be extracted of. Defaults to `54`. There should be no need to set this parameter, since the most recent data is generally what is wanted.

##### Example of returned data:

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

**********

#### CommitteeList

This retrieves the list of *current* working groups and committees of the Chamber. The website provides no way to get the list from a particular point in time.

##### Parameters

This scraper has no parameter.

##### Example of returned data:

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
        "type": "special",
        "name_fr": "…",
        "name_nl": "…"
    }
]
```

**********

#### Committee

This gets the names of a committee and the existing seats, categorized by types and political groups. The array data of each seat attributed to a member of the Chamber also stores the full name and the identifier of this member.

##### Parameters

Name       | Type   | Description
-----------|--------|------------
identifier | string | **Required**. The Chamber identifier of the group or committee.

##### Example of returned data:

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

**********

#### Dossier

This scraper gets information about a full dossier.

##### Parameters

Name       | Type   | Description
-----------|--------|------------
identifier | string | **Required**. The Chamber identifier of the dossier. It matches the pattern `DDKDDDD`, where ‘D’ are digits and ‘K’ is a litteral uppercase ‘k’.

Example of returned array (shortened for clarity):

(to be defined)

**********

#### PlenaryAgendaList

This retrieves a list of links of plenary session week agendas that are *currently* published online. The website provides no way to get the list from a particular point in time nor to access archives of agendas.

##### Parameters

This scraper has no parameter.

##### Example of returned data:

```json
[
    {
        "identifier": "1502_00",
        "startDate": "2015-01-05",
        "endDate": "2015-01-09",
        "url": "http://www.lachambre.be/url/to/agenda/page/1502_00"
    },
    {
        "identifier": "1451_02",
        "startDate": "2014-12-15",
        "endDate": "2014-12-19",
        "url": "http://www.lachambre.be/url/to/agenda/page/1451_02"
    }
]
```


### Senate

All of these scrapers live in the `\Pandemonium\Methuselah\Scrapers\S` namespace. In other words, to instanciate them, you need to preprend their name with this namespace.

#### MPList

Without any parameter, it gets the list of *current* members of the Senate. When provided a legislature number, it gets the list of *all the senators who seated* during that legislature.

##### Parameters

Name               | Type   | Description
-------------------|--------|------------
legislature_number | number | Number of the legislature of which the list of senators is needed.

##### Example of returned data:

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

**********

#### MP

This scraper gets info associated to a single senator.

##### Parameters

Name       | Type   | Description
-----------|--------|------------
identifier | string | **Required**. The Senate identifier of the senator.

##### Example of returned data:

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

**********

#### CommitteeList

This scraper gets the list of *current* working groups and committees of the Senate. The website provides no way to get the list from a particular point in time.

##### Parameters

This scraper has no parameter.

##### Example of returned data:

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
        "type": "special",
        "name_fr": "…",
        "name_nl": "…"
    }
]
```

**********

#### Committee

This gets the names of a committee and the existing seats, categorized by roles. Each seat’s array stores the full name, the identifier and the political group of the related senator.

##### Parameters

Name       | Type   | Description
-----------|--------|------------
identifier | string | **Required**. The Senate identifier of the group or committee.

##### Example of returned data:

```json
{
    "identifier": "1234",
    "name_fr": "…",
    "name_nl": "…",
    "presidents": [
        {
            "identifier": "12345",
            "surname_given_name": "John Doe",
            "political_group": "Cats"
        }
    ],
    "members": [
        {
            "identifier": "54321",
            "surname_given_name": "Jane Doe",
            "political_group": "Unicorns"
        }
    ],
    "substitutes": [
        {
            "identifier": "23456",
            "surname_given_name": "Bob Doe",
            "political_group": "Unicorns"
        }
    ]
}
```

**********

#### Dossier

This scraper gets information about a full dossier.

##### Parameters

Name       | Type   | Description
-----------|--------|------------
identifier | string | **Required**. The Senate identifier of the dossier. It is the number of the legislature, followed by a capital ‘S’ and, finally, the number of the dossier itself.

##### Example of returned data:

(to be defined)
