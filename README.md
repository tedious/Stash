# Stash

[![Build Status](https://secure.travis-ci.org/tedivm/Stash.png?branch=master)](http://travis-ci.org/tedivm/Stash)

Stash makes it easy to speed up your code by caching the results of expensive
functions or code. Certain actions, like database queries or calls to external
APIs, take a lot of time to run but tend to have the same results over short
periods of time. This makes it much more efficient to store the results and call
them back up later.

Visit [stash.tedivm.com](http://stash.tedivm.com) for the current documentation.

The [development documentation](http://stash.tedivm.com/dev/) is available for
testing new releases, but is not considered stable.