# Mushroom :mushroom: [![Travis](https://img.shields.io/travis/willwashburn/mushroom.svg)](https://travis-ci.org/willwashburn/mushroom) [![Packagist](https://img.shields.io/packagist/dt/willwashburn/mushroom.svg)](https://packagist.org/packages/willwashburn/mushroom) [![Packagist](https://img.shields.io/packagist/v/willwashburn/mushroom.svg)](https://packagist.org/packages/willwashburn/mushroom)
Expand a link.

Mushroom finds the final destination of a shortened (or not shortened) url.

> Note: Not for psychedelic drug use

# Usage
 ```PHP
 $mushroom = new Mushroom\Mushroom();

 /// With a single bitly link
 $mushroom->expand('bit.ly/xwzfs');
 //// http://www.yourlink.com

 /// With an array of links
 $mushroom->expand(['bit.ly/1asdf','goog.it/sdfsd','somefulllink.com/foo']);
 /// array http://somebitlylink.com, http://somegooglelink.com, http://somefulllink.com/foo

```

# Installation
Use composer

```composer require willwashburn/mushroom```

Alternatively, add ```"willwashburn/mushroom": "~1.0.0"``` to your composer.json

## Change Log
- v1.0.0 - Expand multiple links using multi_curl_* for faster responses
- v0.0.2 - Basic link expanding using curl
- v0.0.1 - Basic link expanding using "get_headers"
