# Decision Log

**a. Tradeoffs noticed**
The brief asks for both "caching" and "a graceful message if the API is unreachable" — these pull
against each other. A pure TTL transient satisfies caching alone, but once it expires, an outage at
that exact moment would still surface an error to every visitor until the next successful fetch.
Resolved by adding a second, non-expiring fallback copy (`wp_options`, updated on every successful
fetch) alongside the transient: on a live-fetch failure, we serve the last known-good data instead
of an error, and the error message is reserved for the true worst case — no cache has ever
succeeded. Availability was prioritized over strict freshness, since stale pet listings are a much
smaller problem for this use case than a broken widget.

**b. Caching approach**
WordPress transients (`set_transient`/`get_transient`) over object caching (Redis/Memcached) or a
custom DB table: transients work out of the box on any host with zero extra infrastructure, respect
the site's object cache automatically if one is configured later, and the admin-configurable TTL
maps directly onto a single settings field. The stale-fallback in `wp_options` was added on top
because a transient alone offers no answer for "cache expired and the API is down right now."

**c. First thing to break under real load**
The `wp_options` fallback write on every successful fetch (`update_option`, autoload off) is fine at
low traffic, but at high concurrency multiple requests could race past an expired transient
simultaneously and all fire live API calls before any of them repopulates the cache (a stampede).
There's no locking around the fetch-and-set path. At scale this should move to a proper object cache
with a lock (e.g. `wp_cache_add` as a mutex) or a cron-refreshed cache instead of on-demand refresh.

**d. With more time**
Add a scheduled cron job to refresh the cache proactively (removing the stampede risk entirely),
add PHPUnit coverage for the sanitize/normalize logic, and add a live "Test Connection" button on
the settings page so admins can validate the API URL without needing a front-end page.
