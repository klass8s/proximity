# Contributing to Proximity

Thank you for helping. This file says what to do before you start and what a pull request must
satisfy to be merged.

## Before you start

- **A bug:** open an issue with the smallest class and the proxy options that show it, what the
  proxy did and what you expected, and the versions of PHP and Proximity
  (`composer show klass8s/proximity`).
- **A feature or a change of behaviour:** open an issue to discuss it before writing the code. Every
  generated proxy depends on the generator's output, so a change to it is decided before it is built.
- **A security issue** is reported privately, never in a public issue: see [SECURITY.md](SECURITY.md).

## Building and testing

You need PHP 8.4 or later and Composer.

```bash
composer install
composer install -d tools/php-cs-fixer   # the code-style tool, pinned by its own lock
composer install -d tools/phpstan        # the static analyser, pinned by its own lock
composer test                            # the PHPUnit suite
composer cs:check                        # the code-style check; `composer cs` fixes what it reports
composer phpstan                         # static analysis
```

## What a pull request must satisfy

- It targets `main`, never the branch of another open pull request. A change that depends on an
  unmerged one goes into the same pull request, or waits for that one to merge.
- `composer test`, `composer cs:check` and `composer phpstan` pass.
- A change in behaviour comes with a test that fails without it, and with the documentation it makes
  wrong in `README.md` corrected in the same pull request.
- The title is a [Conventional Commits](https://www.conventionalcommits.org) header,
  `type(scope): subject`, such as `fix(generator): …`, with `!` after the type for a breaking change.
  Pull requests are squash-merged, so the title becomes the commit on `main` from which the next
  release and its changelog are written. Every commit in the pull request is a Conventional Commit
  too, with a body that says what changed and why, and the title's release impact equals the highest
  of the commits': breaking, then `feat`, then `fix`, then the rest.

## License

Contributions are licensed under the [MIT License](LICENSE), as the rest of the repository is.
