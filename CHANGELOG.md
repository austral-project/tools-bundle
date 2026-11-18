# Changelog

All notable changes to this bundle are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html). All Austral bundles share the same major and minor version.

## [Unreleased]

### Added

### Changed

- Support for **Symfony 7.4 LTS** (PHP **≥ 8.4**).
- CI matrix for PHP 8.4/8.5 + PHPUnit Bridge.
- Base unit tests and quality tooling (PHPStan level 8, Rector).
- **BREAKING** `AustralTools::encrypte()` / `decrypte()`: the `$cryptKey` argument is now required (no more default key).
- `AustralTools::getValueByKey()` is deprecated, use `valueByKey()` instead.

### Fixed

- Austral commands: the container is taken again from the kernel of the application (it was always `null` with Symfony 7).
- `ConsoleEvent` and `ConsoleEventSubscriber`: namespace fixed (`Austral\ToolsBundle\…`), the classes could not be autoloaded.

### Removed

## [3.1.1] - 2022-12-16

- Add return execute command
- Add ConsoleErrorEvent if exception
- Add array to string function in tools

## [3.1.0] - 2022-09-17

- Updated bundles version to 3.1

## [3.0.0] - 2021-02-10

- Created new repository and new branch in to Github
- Changed the project License to the MIT
- Writing and publish documentation
- Add to Packagist Service for installation with composer

## 2.x and 1.x

These versions were not open source and have no public documentation.

[Unreleased]: https://github.com/austral-project/tools-bundle/compare/v3.1.3...4.x
[3.1.1]: https://github.com/austral-project/tools-bundle/compare/v3.1.0...v3.1.1
[3.1.0]: https://github.com/austral-project/tools-bundle/compare/v3.0.0...v3.1.0
[3.0.0]: https://github.com/austral-project/tools-bundle/releases/tag/v3.0.0
