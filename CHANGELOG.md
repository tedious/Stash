
## Stash v0.11 Changelog

### 0.11.6

*   FileSystem compatibility fix for the new OpCache system in later versions of PHP.


### 0.11.5

*   Fixed a bug where OSX would be identified as Windows and path names were limited to that system's length.

*   Fixed a bug in the Pool class where setItemClass would throw an exception.


### 0.11.4

*   Introduced HHVM testing capabilities into the test suite.

*   Removed HHVM specific fatal errors.



### 0.11.3

*   Fixed potential key collision with Ephemeral driver.


### 0.11.2

*   Fixed Bug which prevented some file based caches from purging or flushing on Windows based systems.

*   Fixed Bug in the Filesystem cache which caused a fatal error when certain keys were used.


### 0.11.1


*   Logging Support

    The Pool and Item classes can now have PSR-3 compliant Logging libraries injected into them through the setLogger($logger) functions. Any logger injected into the Pool class will get injected into any Items it generates.


*   Pool and Item Interfaces

    The Stash\Pool and Stash\Item classes now implement the new Stash\Interface\Pool and Stash\interface\Item inferaces.


*   Extend Cache renamed and given a ttl

    The Stash\Item::extendCache() function is now Stash\Item::extend($ttl = null).


*   Formatting changes, PSR-1 and PSR-2 compliant.


*   Added "setItemClass" function to the Pool class

    This allows new Item classes to be generated, as long as they following the new interface.
