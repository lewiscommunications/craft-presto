![Presto](https://www.caddis.co/internal/repo/presto.svg)

Presto is a static file extension for the native [Craft cache](https://craftcms.com/docs/templating/cache). It works alongside standard Twig `{% cache %}` tag pairs and includes cache-busting features. Just like standard caching, Presto is automatic. Simply install, update your layouts, and then the cache will bust automatically as you create, update, or delete content within Craft.

#### Quick Start Guide

1. [Set up your general config](#getting-started)
2. [Add cache tags to your layout templates](#template)
3. [Optionally disable error templates](#disable-caching-on-individual-templates)
4. [Configure your server](#server)

## Getting Started

In order to take full advantage of Presto's static caching, turn off [element query caching](https://craftcms.com/docs/config-settings#cacheElementQueries) in your general config file. This will keep the `DeleteStaleTemplateCaches` task from running in the admin. Since Presto busts the entire cache when a new element is saved, element query caching is not necessary.

```php
'cacheElementQueries' => false
```

#### Multi-Enviroment Setup

In the [template example](#template) below, `cacheEnabled` represents a general config variable that you can use to enable or disable caching globally. This is useful if you need to disable caching for your local development environment.

```php
`cacheEnabled` => true
```

## Template

Presto lets Craft do the heavy lifting of calculating the elements within the template. As a result, all you need to do in your templates is pass the cache key returned from `craft.presto.cache` to the native cache tag pair. Presto will return a cache key that includes the host, group (if one is set), and path.

Note that the *entirety* of your template logic *must* be wrapped by the `cache` tags. In addition, it is recommended that you add the `globally` tag so that Craft does not overload the cache (i.e. query string requests).

```twig
{% cache globally using key craft.presto.cache if
	conf.cacheEnabled is defined and
	conf.cacheEnabled and cacheDisabled is not defined
%}
	{# Template Logic #}
{% endcache %}
```

### Parameters

```twig
{% craft.presto.cache({
	group: 'pjax',
	static: false
}) %}
```

**group**<br>
When set the requested page will write into a sub-folder within the top-level cache directory. This is useful for pjax implementations where you load a separate template.

**static**<br>
Setting to false will disable static caching for the request and fall back to native caching logic. The cache key will still be returned, only a static file won't be written.

## Disable Caching on Individual Templates

Keep in mind that when using Presto the `for`, `until`, `if`, and `unless` parameters won't be respected on each request once the static html file is created. In the [template](#template) example above, `cacheDisabled` represents a Twig variable you can set to selectively disable caching on certain templates (i.e. 404 templates).

```twig
{% extends '_layouts/master' %}

{% set cacheDisabled = true %}

{% block content %}
	{# page content #}
{% endblock %}
```

## Server

Your host needs to check for matching static files before Craft handles the request. If the file exists it's served statically. This block should typically be set immediately preceding the primary Craft "index.php" rewrite. Use these examples as a general guideline, your implementation may vary.

#### Apache

```apache
# Check Presto cache
RewriteCond %{REQUEST_FILENAME} !\.(css|eot|gif|ico|jpe?g|otf|png|svg|ttf|webp|woff2?)$ [NC]
RewriteCond %{HTTP:X-PJAX} true
RewriteCond %{REQUEST_METHOD} GET
RewriteCond %{DOCUMENT_ROOT}/cache/%{HTTP_HOST}/presto%{REQUEST_URI}/index.html -f
RewriteRule .* /cache/%{HTTP_HOST}/presto%{REQUEST_URI}/index.html [L,E=nocache:1]]

# Craft rewrite here
```

If you add a cache group, you'll need to add additional configuration. Below is an example of a pjax implementation:

```apache
RewriteCond %{REQUEST_FILENAME} !\.(css|eot|gif|ico|jpe?g|otf|png|svg|ttf|webp|woff2?)$ [NC]
RewriteCond %{HTTP:X-PJAX} !true
RewriteCond %{REQUEST_METHOD} GET
RewriteCond %{DOCUMENT_ROOT}/cache/%{HTTP_HOST}/presto%{REQUEST_URI}/index.html -f
RewriteRule .* /cache/%{HTTP_HOST}/presto%{REQUEST_URI}/index.html [L,E=nocache:1]]
```

## Directory Structure

Presto resolves subdomain hosts automatically. Static html files are created inside a directory named after the requested host (i.e. coolwebsite.com, sub.coolwebsite.com). An additional directory called "presto" is created inside each host directory to avoid .htaccess filename conflicts. See below for an example cache file directory structure:

- cache
	- coolwebsite.com
		- presto
			- index.html
			- blog
				- index.html

## Load Balancer/Scaling

When running Presto in an environment that might spin up additional server instances, standard cache busting will only clear the cache on a single instance. Presto provides a purgeMethod setting which allows you to switch from "immediate" to "cron". As long as the cron job is set up on each instance, cache busting should then take place across your instances.

To use this method, create your "config/presto.php" file and set "purgeMethod" to "cron".

### Crontab

You will also need to set up a cron job to run the `presto check` console command. The following example will run it every 10 minutes.

```
*/10 * * * *  /var/www/craft/app/etc/console/yiic presto check
```

## Config

Copy "presto/config.php" to "craft/config/presto.php" and adjust as needed.

**rootPath:**<br>
Change the root public directory. Default: `$_SERVER['DOCUMENT_ROOT']`

**purgeMethod:**<br>
"immediate" or "cron". Changes how cache busting should be handled: immediately when Craft busts its cache, or via a cron job. Default: `immediate`

## Installation

1. Move the "presto" directory to "craft/plugins".
2. In the Craft admin, navigate to the Plugin section within Settings.
3. Click the Install button on the Presto entry.
4. Optionally change the default cache path in the Presto settings.
	* Note that you should exclude cache directory content from version control.

## Updating

When updating from Presto 0.5.0 or earlier, go to the Presto plugin settings and click `Save` in order to regenerate stored settings.

## License

Copyright 2016 [Lewis Communications, LLC](http://www.lewiscommunications.com). Licensed under the [Apache License, Version 2.0](https://github.com/caddis/presto/blob/master/LICENSE).
