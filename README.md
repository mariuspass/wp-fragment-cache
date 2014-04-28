## WP Fragment Cache

### Description
**This plugin can't be activated without a [persistent backend](https://codex.wordpress.org/Class_Reference/WP_Object_Cache#Persistent_Cache_Plugins) for the [WordPress Object Cache](https://codex.wordpress.org/Class_Reference/WP_Object_Cache).**

Improve website performance by caching individual page fragments (widgets, menus output and long loops).

Adds ability to cache particular sections of your site. This plugin is for people who can't use a full page cache or for sites with lots of pages and with frequent changes.
It can cache entire loops with the WP_Query(bypassing the database queries) or only the html output of the loop and if the query results has changed(comment added, post was edited, postmeta has changed ...) the cache will be invalidated and regenerated.

### Example usage:

```php
<?php if ( ! WP_Fragment_Cache::output( $wp_query_or_blockname, $duration ) ): ?>

  //content to be cached

  <?php WP_Fragment_Cache::store(); ?>
<?php endif; ?>
```

**Parameters:**

**$wp_query_or_blockname**
(mixed/string) (optional) a WP_Query(WP_Comment_Query) result or a string.
Default: the file and the line where the call was initiated(e.g. widgets/most-commented.php:18).

**$duration**
(int/string) (optional) Defines how many seconds to keep the cache for. If you pass 0 the cache will not expire. If you pass  the string "only_today" the cache will expire at the end of the current day. You can use [WordPress Time Constants](http://codex.wordpress.org/Transients_API#Using_Time_Constants).
Default: 86400 (one day).

### Advanced usage:
See the [wiki pages](https://github.com/mariuspass/wp-fragment-cache/wiki)

### Installation:
1. You should have a [persistent backend](https://codex.wordpress.org/Class_Reference/WP_Object_Cache#Persistent_Cache_Plugins) for the [WordPress Object Cache](https://codex.wordpress.org/Class_Reference/WP_Object_Cache) enabled. This plugin can't be activated without a persistent cache.
1. Download the plugin and unzip.
1. Upload them to `/wp-content/plugins/` directory on your WordPress installation.
1. Then activate the WP Fragment Cache plugin from Plugins page.
1. Edit your template files to include WP Fragment Cache. See the [Example usage](#example-usage)
