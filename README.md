# Mushroom :mushroom:
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

Alternatively, add ```"willwashburn/mushroom": "~0.0.2"``` to your composer.json

## Change Log
v0.0.1 - Basic link expanding using "get_headers"
v0.0.2 - Basic link expanding using curl
