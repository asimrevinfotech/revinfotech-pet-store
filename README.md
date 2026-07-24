# Revinfotech Pet Store

A WordPress plugin that pulls pet listings from the Swagger Petstore API and displays them in a
styled, paginated table via a custom Elementor widget.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Elementor (free) — the plugin loads without it but the widget won't be available until it's active

## Setup

### Local development (Docker via `@wordpress/env`)

```bash
npm install
npm run env:start
```

This starts a WordPress site at `http://localhost:8888` (admin/password) with the plugin and
Elementor pre-installed, per [.wp-env.json](.wp-env.json).

Other scripts: `npm run env:stop`, `npm run env:destroy`, `npm run env:logs`.

### Installing on an existing WordPress site

1. Zip the `plugin/revinfotech-pet-store` directory.
2. In `wp-admin → Plugins → Add New → Upload Plugin`, upload the zip and activate.
3. Install and activate Elementor (free) if not already active.

## Configuration

Go to `Settings → Pet Store` in `wp-admin`:

| Field | Description | Default |
|---|---|---|
| API Base URL | Base URL of a Petstore-compatible API | `https://petstore.swagger.io/v2` |
| Default Status Filter | `available`, `pending`, or `sold` — used when a widget instance doesn't override it | `available` |
| Cache Duration (minutes) | How long fetched results are cached in a transient before the next live fetch | `60` |

A **Clear Cache Now** button on the same page forces the next page load to re-fetch from the API.

## Widget Usage

In the Elementor editor, search for **Pet Table** (under the **Revinfotech** category) and drag it
onto the page.

**Content controls:**
- **Status Filter** — override the site-wide default for this widget instance
- **Rows Per Page** — client-side pagination size (1–100)

**Style controls:**
- **Header Background Color**
- **Header Text Color**
- **Row Text Color**
- **Border Color**

The table shows image, name, category, and status for each pet. If the API is unreachable or
returns no results, the widget renders a plain-language notice instead of breaking the page.

## Caching

Results are cached per (API base URL, status) pair as a WordPress transient, with a persistent
fallback copy retained in `wp_options` so the widget can still show the last known-good data if a
live API call fails after the cache expires. See the [decision log](DECISION_LOG.md) for why.

## Uninstalling

Deleting the plugin from `wp-admin → Plugins` removes all options and cached data it created
(see `uninstall.php`). Deactivating alone clears the cache but keeps your saved settings.
