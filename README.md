# Env Modifier for laravel

This is a package for env modification the contents of different Laravel projects.

## Install via composer

Run the following command to pull in the latest version:
```bash
composer require jobmetric/env-modifier
```

### Add service provider

Add the service provider to the providers array in the config/app.php config file as follows:

```php
'providers' => [

    ...

    JobMetric\EnvModifier\EnvModifierServiceProvider::class,
]
```

## Documentation
