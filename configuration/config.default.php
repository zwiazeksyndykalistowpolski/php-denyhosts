<?php declare(strict_types=1);

return [

    // blacklist based filtering (AnalyzeBlacklistedPagesFloodAction)
    'blacklist_enabled' => true,
    'blacklist_timeInterval' => 300, // 5 minutes
    'blacklist_retriesToBan' => 15,
    'blacklist_banTime' => 3600,
    'blacklist_paths' => [
        '/(POST) \/wp-login/i',
        '/(POST|GET) \/xmlrpc/i',
    ],

    // too many visited 403 pages
    'forbiddenPages_enabled' => true,
    'forbiddenPages_timeInterval' => 300,
    'forbiddenPages_retriesToBan' => 10,
    'forbiddenPages_banTime' => 3600,

    'token' => '...',
    'storagePath' => __DIR__ . '/../example/structure/.htaccess',
    'logFormat' => '%h %l %u %t "%r" %>s %O "%{Referer}i" \"%{User-Agent}i"',
    'accessLogPath' => __DIR__ . '/../example/structure/access_log.txt',
];
