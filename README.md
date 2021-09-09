<h1 align="left">laravel-response</h1>

## Requirement
1. PHP >= 7.4
2. Laravel 8.x
3. [Composer](https://getcomposer.org/)


## Installing
```shell
$ composer require sevming/laravel-response -vvv
```

## Usage
```shell
# 1.发布配置文件
php artisan vendor:publish --provider="Sevming\LaravelResponse\Providers\LaravelServiceProvider"

# 2.格式化异常响应
`app/Exceptions/Handler.php` 引入 `use Sevming\LaravelResponse\Support\Traits\ExceptionTrait;`
```

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/sevming/laravel-response/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/sevming/laravel-response/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT
