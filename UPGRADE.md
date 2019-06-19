## Upgrading to 0.14

* Item set no longer saves

    $item->save() has to be explicitly called after setting an item.

* Item set no longer takes ttl parameter

    $item->setTTL($ttl) now has to be called separately.

## Upgrading to 0.11


### 0.11.1


*   Pool and Item Interfaces

    It's recommended that developers who do any Type Hinting or class checking to start checking against the interfaces, rather than the classes, so that custom caching classes that remain compliant with those interfaces can be used.


*   Function Names

    The Item::extendCache() function has been renamed to "extend" and takes an optional ttl. It's new signature Item::extend($ttl = null).




