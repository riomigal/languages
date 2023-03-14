# Laravel Languages
## What is Laravel Languages?

Laravel Languages is a Translation UI that adds additional functionality to the existing Laravel App.
The App has it's own guard which isolates Laravel Languages from an existing App.

## Features

- Manage Languages and Translations from Filesystem in a Database
- Manage Translators and give them access to selected Languages
- Export/Import between Filesystem and Data
- Search for text, files
- Request Translations
- Approve Translations

## Installation

Require the package from composer:

```composer require riomigal/languages```

Migrate database files, run:

```php artisan migrate```

Publish public assets:

```php artisan vendor:publish --tag=languages-public```

Run queue process:

```php artisan queue:work --queue=languageProcesser```

The queue process has the name languageProcessor, the value is configurable in the config file. To run the default queue or any other additional queue just add them before or after (first has higher priority) the languageProcessor, e.g.:

```php artisan queue:work --queue=languageProcesser,default```

Change the queue connection in the .env file to:

QUEUE_CONNECTION=database

If it's a new project or the lang folder (/lang) wasn't published yet, it is necessary to publish the lang folder with:

```php artisan lang:publish```


