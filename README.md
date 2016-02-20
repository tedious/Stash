# Stash - A PHP Caching Library [![Build Status](https://travis-ci.org/tedious/Stash.svg?branch=master)](https://travis-ci.org/tedious/Stash)

[![License](http://img.shields.io/packagist/l/tedivm/stash.svg)](https://github.com/tedious/Stash/blob/master/LICENSE)
[![Latest Stable Version](http://img.shields.io/github/release/tedious/stash.svg)](https://packagist.org/packages/tedivm/stash)
[![Coverage Status](http://img.shields.io/coveralls/tedious/Stash.svg)](https://coveralls.io/r/tedious/Stash?branch=master)
[![Total Downloads](http://img.shields.io/packagist/dt/tedivm/stash.svg)](https://packagist.org/packages/tedivm/stash)

Stash makes it easy to speed up your code by caching the results of expensive
functions or code. Certain actions, like database queries or calls to external
APIs, take a lot of time to run but tend to have the same results over short
periods of time. This makes it much more efficient to store the results and call
them back up later.

## Installing

Installing Stash can be done through a variety of methods, although Composer is
recommended.

### Composer

Until Stash reaches a stable API with version 1.0 it is recommended that you
review changes before Minor updates, although bug fixes will always be
backwards compatible.

```js
"require": {
  "tedivm/stash": "0.14.*"
}
```

or by using the `composer require` command:

```
composer require tedivm/stash
```

### Github

Releases of Stash are available on [Github](https://github.com/tedious/Stash/releases).


## Documentation

Although this README contains some useful data there is a lot more information
at the main site, [stashphp.com](http://www.stashphp.com/).


## Core Concepts

### Main Classes

Stash has three main components: a `Pool` class that represents a specific
grouping of cached objects, an `Item` class that provides access to individual
objects, and a series of `Driver` classes that allow Stash to interact with
caching systems.

Each `Driver` is initialized and then passed into a `Pool`, at which point the
developer can simply forget about it. Developers also have the option of using
multiple Drivers together by joining them with the Composite Driver.

The `Pool` class allows developers to perform a number of tasks. There are a few
maintenance related tasks, such as running a "Purge" to allow backend systems to
perform maintenance tasks or set new logging or driver classes. The `Pool` also
can be used to create `Item` objects, singly or in groups.

Each `Item` represents a single object inside the cache. It has a unique Key,
meaning that any two Items created from the same `Pool` will contain the same
Value. An `Item` can set, get and remove a value from a caching system.

### Keys

A Key is a string that represents an Item in a caching system. At its simplest,
a key is an alphanumeric string and has a one to one relationship with a value
in the cache.

Stash provides a feature known as "stacks" that allows developers to group
related Items together so they can be erased as a group. This is done by giving
Items a nested structure, similar to folders on a computer. Just like with
folders, this is represented by adding slashes to the name representing the file
or cached object.

For example, a Key like "/models/users/34/profile" can allow developers to clear
the data for specific users using that user's id, or clear the data for all
users or even all models. It can also allow that developer to break up data into
specific pieces to only load what is needed.

### Session Storage

The provided Session class takes a Pool in its constructor and can then be
registered as a Session Handler using the built-in PHP methods, the
Session::registerHandler static function, or by using any framework that uses
the SessionHandlerInterface interface.


## Drivers

Stash currently supports the following backends:

* FileSystem
* Sqlite
* APC
* Memcached
* Redis
* Ephemeral (runtime only)

Stash also supports a specialized "Composite" Driver which can contain any
number of the above drivers. This allows developers to created multi-tiered
drivers that use a variety of back ends.


## Symfony Integration

The [Stash Bundle](https://github.com/tedious/TedivmStashBundle) makes using
Stash inside of Symfony projects significantly easier. This bundle exposes
Stash as a Service for developers to use, and allows Symfony to use Stash
as a session handler.


## License

Stash is licensed under the BSD License. See the LICENSE file for details.
