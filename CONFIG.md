# Configuration

It's possible to change some plugin logic by defining a constant in the `wp-config.php` file.

## KAYPAY_CALLBACK_URL

Under normal circumstances, the plugin generates callback URL using WooCommerce utilities.
The result URL includes WordPress home URL which is unreachable in some environments (e.g. development server).
Admin can define a custom callback URL like this:

```php
define('KAYPAY_CALLBACK_URL', 'https://domain.com/custom/kaypay/callback');
```

## KAYPAY_LOGGER_URL

Critical errors are sent back to Kaypay for merchant support and troubleshooting. Admin can turn off this feature by setting it to an empty string.

It is recommended to leave this feature turned on to avoid hidden errors.

```php
define('KAYPAY_LOGGER_URL', '');
```
