# PHP SASS Compiler

## Features

### Supported modes

- (default) compile if not-include files changed: can be used in production env if you really need to
- compile if any files changed: handy during local development
- force compile without any modification check: in case you need it


### Output formats

Checkout https://github.com/scssphp/scssphp/tree/master/src/Formatter

Default is `Compressed`.

### Imports

Changes `@import "~` to `@import "../node_modules/`


## Installation

    composer require bond211/php-sass-compiler


## Usage

### Laravel

Add e.g. in `AppServiceProvider`:

    if (App::environment('local') && !App::runningInConsole()) {
        SassCompiler::run(resource_path('sass/'), public_path('css/'), Mode::CHECK_INCLUDES);
    }
