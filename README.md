# PhpSiteRepositoryTool

Tool that handles upstream updates for Pantheon.

## Getting Started

- Clone repo
- Run `composer install`
- Start using the tool

See deployment for notes on how to deploy the project on a live system.

### Prerequisites

- PHP >= 7.4
- Composer 2

## Running the tests

Before running the tests locally, you must install the correct dependencies for the version of PHP that you are using:

```
composer configure-for-php-version
```

By default, the project is configured for PHP 8.0 / 8.1, so you only need to run `configure-for-php-version` if you wish to test with PHP 7.4 or earlier.

Note that this script will REMOVE TYPEHINTS if you are using a version of PHP prior to 7.4.  It would not be desirable to commit this sort of change, so it is best to avoid testing locally with older versions of PHP. If you do, immediately run `git reset --hard` after running the tests.

The test suite may be run locally by way of some simple composer scripts:

| Test             | Command
| ---------------- | ---
| Run all tests    | `composer test`
| PHPUnit tests    | `composer unit`
| PHP linter       | `composer lint`
| Code style       | `composer cs`     
| Fix style errors | `composer cbf`


## Deployment

Add additional notes about how to deploy this on a live system.

If your project has been set up to automatically deploy its .phar with every GitHub release, then you will be able to deploy by the following procedure:

- Edit the `VERSION` file to contain the version to release, and commit the change.
- Run `composer release`

## Built With

List significant dependencies that developers of this project will interact with.

* [Composer](https://getcomposer.org/) - Dependency Management
* [Robo](https://robo.li/) - PHP Task Runner
* [Symfony](https://symfony.com/) - PHP Framework

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [releases](https://github.com/pantheon-systems/php-site-repository-tool/releases) page.

## Authors

* **Kevin Porras** - created project from template.

See also the list of [contributors](https://github.com/pantheon-systems/php-site-repository-tool/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details

## Acknowledgments

* Hat tip to anyone who's code was used
* Inspiration
* etc
* Thanks to PurpleBooth for the [example README template](https://gist.github.com/PurpleBooth/109311bb0361f32d87a2)
