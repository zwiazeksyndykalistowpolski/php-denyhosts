## Blacklist based traffic filtering

Cares only of entries that matches specific endpoints defined in "blacklist_paths".
This means that we may look if somebody is trying to access login page to often, or administration panel.

```
// blacklist based filtering (AnalyzeBlacklistedPagesFloodAction)
    'blacklist_enabled' => true,
    'blacklist_timeInterval' => 300, // 5 minutes
    'blacklist_retriesToBan' => 3,
    'blacklist_paths' => [
        '/(POST|GET) \/wp-login/i',
        '/(POST|GET) \/xmlrpc/i',
    ],
```