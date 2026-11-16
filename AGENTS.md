# Agent guide

Proximity creates runtime proxy objects for PHP 8.4+ with method interception, as a library and a
Symfony bundle (namespace `Proximity\`). This file is for coding agents;
[CONTRIBUTING.md](CONTRIBUTING.md) says the same for people, and where the two differ,
CONTRIBUTING.md wins.

## What a pull request must satisfy

- It targets `main`, never the branch of another open pull request.
- `composer test`, `composer cs:check` and `composer phpstan` pass (`composer install -d
  tools/php-cs-fixer` and `composer install -d tools/phpstan` install the tools once).
- A change in behaviour comes with a test that fails without it; what it makes wrong in `README.md`
  or `docs/` is corrected in the same pull request.
- The title and every commit are [Conventional Commits](https://www.conventionalcommits.org)
  (`type(scope): subject`, `!` for a breaking change); each commit has a body that says what changed
  and why, and the title's release impact equals the highest of the commits'. Pull requests are
  squash-merged: the title becomes the commit on `main` and the changelog entry.
- The library itself needs no framework: code outside `src/Bundle/` does not use Symfony.
