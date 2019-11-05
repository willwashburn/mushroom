# Mushroom :mushroom: ![CircleCI (all branches)](https://img.shields.io/circleci/project/github/willwashburn/mushroom.svg?style=flat-square) [![Coveralls](https://img.shields.io/coveralls/willwashburn/mushroom.svg?maxAge=2592000&style=flat-square)](https://coveralls.io/github/willwashburn/mushroom) [![Packagist](https://img.shields.io/packagist/dt/willwashburn/mushroom.svg?style=flat-square)](https://packagist.org/packages/willwashburn/mushroom) [![Packagist](https://img.shields.io/packagist/v/willwashburn/mushroom.svg?style=flat-square)](https://packagist.org/packages/willwashburn/mushroom) [![MIT License](https://img.shields.io/packagist/l/willwashburn/mushroom.svg?style=flat-square)](https://github.com/willwashburn/mushroom/blob/master/LICENSE) 
Expand a link.

Mushroom finds the final destination of a shortened (or not shortened) url.

> Note: Not for psychedelic drug use

## Usage
 ```PHP
 $mushroom = new Mushroom\Mushroom();

 /// With a single bitly link
 $mushroom->expand('bit.ly/xwzfs');
 //// http://www.yourlink.com

 /// With an array of links
 $mushroom->expand(['bit.ly/1asdf','goog.it/sdfsd','somefulllink.com/foo']);
 /// array http://somebitlylink.com, http://somegooglelink.com, http://somefulllink.com/foo
 
 // Find the canonical url of some link (or set of links)
 $mushroom->canonical('http://yourlink.com?utm_param=mushroom');
 // http://www.yourlink.com

```

## Installation
Use composer

```composer require willwashburn/mushroom```

Alternatively, add ```"willwashburn/mushroom": "~2.9"``` to your composer.json

## Change Log
- v2.9.1 - Fix bug where some html sources were not cached
- v2.9.0 - Expose HTML for urls when searching canonically
- v2.8.0 - Only follow JS redirects for whitelisted domains
- v2.7.0 - Follow some JS redirects
- v2.6.0 - Add default timeout to curl options
- v2.5.1 - Add spoofed browser headers to default curl opts
- v2.5.0 - Ensure that http-refresh redirects have host and scheme
- v2.4.0 - Follow http-refresh html meta tags
- v2.3.0 - Ensure that canonical urls have a host
- v2.2.0 - Ensure that canonical urls have a scheme
- v2.1.1 - Remove CURLOPT_NOBODY from defaults
- v2.1.0 - Allow setting curl handle options; set user agent as default 
- v2.0.0 - Stop removing slashes from the end of urls
- v1.1.0 - Add ability to find canonical url from tags in body of returned page
- v1.0.0 - Expand multiple links using multi_curl_* for faster responses
- v0.0.2 - Basic link expanding using curl
- v0.0.1 - Basic link expanding using "get_headers"

## Future Plans
- "Polite" mode to use common link shortening services api's to not count expanding in click counts
