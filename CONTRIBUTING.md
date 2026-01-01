# Contributing

Contributions are welcome and will be fully credited.

## Pull Requests

- **[PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)** - The easiest way to apply the conventions is to run `composer format`.

- **Add tests!** - Your patch won't be accepted if it doesn't have tests.

- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.

- **Create feature branches** - Don't ask us to pull from your master branch.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please squash them before submitting.

## Development Setup

1. Fork and clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```

3. Run the test suite:
   ```bash
   composer test
   ```

4. Run static analysis:
   ```bash
   composer analyse
   ```

5. Fix code style:
   ```bash
   composer format
   ```

## Running Tests

```bash
composer test
```

## Static Analysis

This project uses PHPStan for static analysis:

```bash
composer analyse
```

## Code Style

This project uses Laravel Pint for code formatting:

```bash
composer format
```

**Happy coding!**
