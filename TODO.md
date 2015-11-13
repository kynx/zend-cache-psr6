# TODO

* Take a look at `clear()` - it's not clear to me how this should work with various backends
* Take a look at `save()` + expiry - turns out `getExpiration()` was removed from the spec a few months ago,
  but I can't think how other implementations would handle item-specific expires without it (unless
  `CacheItem` knows how to persist itself??)
* See how other implementations handle enforcing `CacheItem` not being instantiated away from `getItem()`
* Implement `saveDeferred()` / `commit()` properly
* Double-check return values of `deleteItems()` when passed keys that do not exist
