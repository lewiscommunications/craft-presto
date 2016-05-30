![Presto](https://www.caddis.co/internal/repo/presto.svg)

Presto is a static file extension for the native [Craft cache](https://craftcms.com/docs/templating/cache). It works alongside standard Twig `{% cache %}` tag pairs and includes cache-busting and warming features. Just like standard caching, Presto is automatic. Simply install, update your layouts, and then the cache should recycle automatically as you create, update, or delete content within Craft.

## Template

Presto lets Craft do the heavy lifting of calculating the elements and criteria within the template. As a result, all you need to do in your templates is pass the cache key returned from `craft.presto.cache` to the native cache tag pair. Presto will return a unique cache key that includes the request path by default.

Note that the *entirety* of your template logic *must* be wrapped by the `cache` tags.

```twig
{% set cacheEnabled = craft.config.env != 'local' and cacheDisabled is not defined %}
{% set cacheKey = cacheEnabled ? craft.presto.cache %}

{% cache using key cacheKey if cacheEnabled %}
	{# Template Logic #}
{% endcache %}
```

Keep in mind that when using Presto the `for`, `until`, `if`, and `unless` parameters won't be respected on each request once the file is saved. In the example above `cacheDisabled` represents a Twig variable you could set elsewhere. For instance it should be set in error templates, to selectively disable caching on the layout.

The `craft.presto.cache` tag can accept the following optional parameters.

* group - When set the requested page will write into a sub-folder within the top-level cache directory.
* static - Setting to false will disable static caching for the request and fall back to native caching logic. The cache key will still be returned, only a static file won't be written.

```twig
craft.presto.cache({
	group: 'pjax',
	static: false
})
```

## Server

Your host needs to check for matching static files before Craft handles the request. If the file exists it's served statically. This block should typically be set immediately preceding the primary Craft "index.php" rewrite. Use these examples as a general guideline, your implementation may vary.

##### Apache

```apache
# Check Presto cache
RewriteCond %{REQUEST_FILENAME} !\.(css|gif|ico|jpe?g|png|svg)$ [NC]
RewriteCond %{REQUEST_METHOD} GET
RewriteCond %{DOCUMENT_ROOT}/cache%{REQUEST_URI}/index.html -f
RewriteRule .* /cache%{REQUEST_URI}/index.html [L]

# Craft rewrite here
```

##### Nginx

```nginx
# Block direct cache access
location /cache {
	internal;
}

# Check Presto cache
location ~ !\.(css|gif|ico|jpe?g|png|svg)$ {
	if ($request_method = GET) {
		try_files $uri /cache/$uri/index.html;
	}
}

# Craft rewrite here
```

## Flushing

If you need to flush the cache manually there are a few options. To clear all static files you can select "Presto caches" within "Clear Caches" on the Craft Settings page. If you only want to reset certain endpoints you can also just delete the applicable files from your configured cache directory.

You can also call the Presto purge tag. This tag could be used in a template as the endpoint to a Cron job if needed. Keep in mind there can be multiple purge tags in a template. Here are the available tag parameters.

* expired - When set to true Presto will only clear expired caches based on the [`for`](https://craftcms.com/docs/templating/cache#for) or [`until`](https://craftcms.com/docs/templating/cache#until) cache tag value or the native [`cacheDuration`](https://craftcms.com/docs/config-settings#cacheDuration) config setting. By default all matched files are purged regardless of expiration.
* path - An array of paths to purge. If no paths are set the entire cache will be purged.
* recursive - When set to false nested path caches won't be purged.
* warm - When set to true all flushed paths will automatically be warmed.

```twig
{{ craft.presto.purge({
	expired: true,
	paths: ['/', 'blog'],
	recursive: false,
	warm: true
}) }}
```

## Advanced

A number of advanced settings can be configured by adding a "presto.php" file within the "craft/config" directory. Some use-cases include PJAX, A/B testing, and multi-domain setups.

* fingerprint - Provide additional data to uniquely identify a cached resource. The current request path is always included.
* groups - Set additional folder group paths to bust within. This should match group values passed to `craft.presto.cache`.
* rootPath - Change the root public directory. It defaults to the server's `DOCUMENT_ROOT`.
* warmers - This array sets specific fingerprint, header, and configuration values that should be used when warming the cache in addition to a standard HTTP request. Any of the settings can be excluded.

In this example we're letting Presto know we have PJAX requests caching independently for automated flushing and warming support. This works in conjunction with a specific server routing condition for the PJAX header as well as passing a group parameter value of "pjax" in the PJAX layout to the `craft.presto.cache` tag.

```php
<?php

return array(
	'fingerprint' => array(
		'pjax' => isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX'] === 'true'
	),
	'groups' => array(
		'pjax'
	),
	'warmers' => array(
		array(
			'config' => array(
				'group' => 'pjax'
			),
			'fingerprint' => array(
				'pjax' => true
			),
			'headers' => array(
				'X-PJAX' => 'true'
			)
		)
	)
);
```

## Installation

1. Move the "presto" directory to "craft/plugins".
2. In Craft navigate to the Plugin section within Settings.
3. Click the Install button on the Presto entry.
4. Optionally change the default cache path in the Presto settings.
	* Note that you should exclude cache directory content from version control.

## License

Copyright 2016 [Caddis Interactive, LLC](https://www.caddis.co). Licensed under the [Apache License, Version 2.0](https://github.com/caddis/presto/blob/master/LICENSE).