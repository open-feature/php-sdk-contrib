# Contributing

## Development

### System Requirements

PHP 8+ is required.

### Compilation target(s)

We target compatibility with PHP versions 8.0, 8.1, and 8.2.

### Project Structure

The repository is made up of two primary directories for development: `hooks` and `providers`. These each contain packages offering OpenFeature Hooks and Providers respectively. All development is done within those packages.

There is not yet a process for generating a hook or provider package, but [an issue is tracking this](https://github.com/open-feature/php-sdk-contrib/issues/37).

> **🛈 All of the following instructions are from the context of the package directory being developed.**

### Installation and Dependencies

Install dependencies with `composer install`.

We value having as few runtime dependencies as possible. The addition of any dependencies requires careful consideration and review.

### Testing

Run tests with `composer dev:test`.

All packages should implement `composer` scripts for unit and integration tests. It is fine to provide no-ops for these scripts.

### Unit tests

Run unit tests with `composer dev:test:unit`.

### Integration tests

Run unit tests with `composer dev:test:unit`.

### Packaging

This package is directly available via Packagist and can be installed with `composer require open-feature/sdk`. Packagist utilizes Git and tags for releases, and this process is automated through GitHub.

## Pull Request

All contributions to the OpenFeature project are welcome via GitHub pull requests.

To create a new PR, you will need to first fork the GitHub repository and clone upstream.

```bash
git clone https://github.com/open-feature/php-sdk-contrib.git openfeature-php-sdk-contrib
```

Navigate to the repository folder

```bash
cd openfeature-php-sdk-contrib
```

Add your fork as an origin

```bash
git remote add fork https://github.com/YOUR_GITHUB_USERNAME/openfeature-php-sdk-contrib.git
```

Makes sure your development environment is all setup by building and testing

```bash
composer install
composer dev:test
```

To start working on a new feature or bugfix, create a new branch and start working on it.

```bash
git checkout -b feat/NAME_OF_FEATURE
# Make your changes
git commit
git push fork feat/NAME_OF_FEATURE
```

Open a pull request against the main php-sdk repository.

### How to Receive Comments

- If the PR is not ready for review, please mark it as
  [`draft`](https://github.blog/2019-02-14-introducing-draft-pull-requests/).
- Make sure all required CI checks are clear.
- Submit small, focused PRs addressing a single concern/issue.
- Make sure the PR title reflects the contribution.
- Write a summary that helps understand the change.
- Include usage examples in the summary, where applicable.

### How to Get PRs Merged

A PR is considered to be **ready to merge** when:

- Major feedback is resolved.
- Urgent fix can take exception as long as it has been actively communicated.

Any Maintainer can merge the PR once it is **ready to merge**. Note, that some
PRs may not be merged immediately if the repo is in the process of a release and
the maintainers decided to defer the PR to the next release train.

If a PR has been stuck (e.g. there are lots of debates and people couldn't agree
on each other), the owner should try to get people aligned by:

- Consolidating the perspectives and putting a summary in the PR. It is
  recommended to add a link into the PR description, which points to a comment
  with a summary in the PR conversation.
- Tagging domain experts (by looking at the change history) in the PR asking
  for suggestion.
- Reaching out to more people on the [CNCF OpenFeature Slack channel](https://cloud-native.slack.com/archives/C0344AANLA1).
- Stepping back to see if it makes sense to narrow down the scope of the PR or
  split it up.
- If none of the above worked and the PR has been stuck for more than 2 weeks,
  the owner should bring it to the OpenFeatures [meeting](README.md#contributing).

## Versioning and releasing

As described in the [README](./README.md), this project uses release-please, and semantic versioning.
Breaking changes should be identified by using a semantic PR title.

## Dependencies

Keep dependencies to a minimum, especially non-dev dependencies.

The PHP SDK can be a non-dev dependency, as `composer` does not allow multiple versions of packages to be resolved during a `composer install`.
