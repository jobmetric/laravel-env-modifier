# Env Modifier for laravel

This is a package for env modification the contents of different Laravel projects.

## Install via composer

Run the following command to pull in the latest version:
```bash
composer require jobmetric/laravel-env-modifier
```

## Documentation

To use the services of this package, please follow the instructions below.

### Set file path

By default, the main Laravel env file is in the settings, if you want to change another file and get data from it, you should use this method.

```php
JobMetric\EnvModifier\Facades\EnvModifier::setPath('complete path file')
```

### Set data

You must pass it an array with keys and each key contains a value.

```php
JobMetric\EnvModifier\Facades\EnvModifier::set([
    'KEY1' => 123,
    'KEY2' => 456,
    ...
])
```

### Get data

You can use the following modes to receive the data of an env file.

1- Get a specific key

```php
JobMetric\EnvModifier\Facades\EnvModifier::get('KEY1')
```

2- Get some specific keys

```php
JobMetric\EnvModifier\Facades\EnvModifier::get('KEY1', 'KEY2', ...)
```

3- Get array specific keys

```php
JobMetric\EnvModifier\Facades\EnvModifier::get(['KEY1', 'KEY2', ...])
```

4- Get an array with a given number of keys

The fourth method is less used, it is to show off the power of the program.

```php
JobMetric\EnvModifier\Facades\EnvModifier::get(['KEY1', 'KEY2', ...], 'KEY3', 'KEY4', ...)
```

#### How to return get data

```php
array(
    'KEY1' => 123,
    'KEY2' => 456,
    ...
)
```

### Delete data

You can use the following modes to delete the data of an env file.

1- Delete a specific key

```php
JobMetric\EnvModifier\Facades\EnvModifier::delete('KEY1')
```

2- Delete some specific keys

```php
JobMetric\EnvModifier\Facades\EnvModifier::delete('KEY1', 'KEY2', ...)
```

3- Delete array specific keys

```php
JobMetric\EnvModifier\Facades\EnvModifier::delete(['KEY1', 'KEY2', ...])
```

4- Delete an array with a given number of keys

The fourth method is less used, it is to show off the power of the program.

```php
JobMetric\EnvModifier\Facades\EnvModifier::delete(['KEY1', 'KEY2', ...], 'KEY3', 'KEY4', ...)
```

### Has key

You can use the following method to check a key in a file.

```php
JobMetric\EnvModifier\Facades\EnvModifier::has('KEY1')
```
