# PHP Infection StreamWrapper

[![Build Status](https://travis-ci.com/t0mmy742/php-infection-streamwrapper.svg?branch=master)](https://travis-ci.com/t0mmy742/php-infection-streamwrapper)

`t0mmy742/php-infection-streamwrapper` is a StreamWrapper used to replace the `infection/include-interceptor` one.
It was created because it is currently not possible to use `infection/infection` package when using `dg/bypass-finals` or `adriansuter/php-autoload-override`.
This package is a mix between these three StreamWrapper to make theme work together.

## Installation

```bash
$ composer require --dev t0mmy742/php-infection-streamwrapper
```

## Links

[Infection - Mutation Testing framework](https://github.com/infection/infection)  
[Infection - Include Interceptor Stream Wrapper](https://github.com/infection/include-interceptor)  
[PHP-Autoload-Override](https://github.com/adriansuter/php-autoload-override)  
[Bypass Finals](https://github.com/dg/bypass-finals)
