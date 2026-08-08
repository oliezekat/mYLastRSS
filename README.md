# mYLastRSS

Simple yet powerfull PHP class to parse and merge several RSS, RDF, Atom feeds

## Installation

```bash
composer require oliezekat/mylastrss
```

### Suggests

 * Setup PHP with allow_url_fopen to enable fopen transport to fetch URLs of feeds
 * Setup PHP with Open SSL extension (with root certificates bundle) to allow fopen transport to fetch URLs of feeds over HTTPS protocole
 * Include [Requests](https://github.com/WordPress/Requests) or add "rmccue/requests" to your composer.json which was use as default transport to fetch URLs of feeds

## Usage

```php
use \mYLastRSS;

$rss = new \mYLastRSS();
$result = $rss->Get(
    [
    'https://sourceforge.net/p/mylastrss/news/feed.rss',
    'https://github.com/oliezekat/mYLastRSS/releases.atom',
    ]
);
if (is_array($result) && isset($result['items']) && is_array($result['items'])) {
    ...
}
```

## Resources

 * [mYLastRSS project on *GitHub*](https://github.com/oliezekat/mYLastRSS) since 2026.
 * [mYLastRSS project on *SourceForge*](https://sourceforge.net/projects/mylastrss/files/) : credits, licence, and releases between 2006 and 2014.
 * [RSS Advisory Board](https://www.rssboard.org/)
