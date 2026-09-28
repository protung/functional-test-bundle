# CONTRIBUTING

TODO

## Coding Standards

TODO

## Dependency Analysis

TODO

## Static Code Analysis

TODO

## Tests

We are using [`phpunit/phpunit`](https://github.com/sebastianbergmann/phpunit) to drive the development.

Run

```sh
$ just test
```

to run all the tests, or

```sh
$ just check
```

to run all the checks CI runs (composer.json validation, coding standard, static analysis and tests).
The commands need [`just`](https://github.com/casey/just).
