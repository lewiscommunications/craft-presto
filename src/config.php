<?php

/**
 * Presto config.php
 *
 * This file exists only as a template for the Presto settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'presto.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    'rootPath' => '@basePath/public_html',
    'cachePath' => '/cache',
    'purgeMethod' => 'immediate',
    'sitemapIndex' => '@cdnUrl/sitemaps/index.xml',
    'cacheWhenLoggedIn' => true,
];
