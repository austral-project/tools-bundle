# Changelog

All notable changes to this bundle are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html). All Austral bundles share the same major and minor version.

## [Unreleased]

### Added

- `add_in_array` is also available as a Twig filter (it was only a function).
- `Command::setEventDispatcher()`: the event dispatcher of the Austral base command is injected by a setter call in the service definition.

### Changed

- Support for **Symfony 7.4 LTS** (PHP **≥ 8.4**).
- CI matrix for PHP 8.4/8.5 + PHPUnit Bridge.
- Base unit tests and quality tooling (PHPStan, Rector).
- **BREAKING** Services are defined explicitly (no autowiring, no autoconfiguration). Service ids named after a class (`Austral\ToolsBundle\…`) are private: use the public `austral.tools.debug` and `austral.tools.status-checker` aliases to get a service from the container. Autowiring a class of the bundle in an application still works.
- **BREAKING** The Austral base command (`Austral\ToolsBundle\Command\Base\Command`) no longer uses the service container: a command receives its dependencies through its constructor, and the event dispatcher through `setEventDispatcher()`.
- **BREAKING** `Austral\ToolsBundle\Command\StatusChecker` is renamed `StatusCheckerCommand` (the command name `austral:tools:status-checker` does not change). It receives `ServicesStatusChecker` through its constructor, and `ServicesStatusChecker` no longer receives the service container.

### Fixed

- `ConsoleEvent`: namespace fixed (`Austral\ToolsBundle\…`), the class could not be autoloaded.

### Removed

- **BREAKING** `Command::setContainer()` and the `$container` property of the Austral base command: inject the services that the command uses instead.
- **BREAKING** `Command::$initEntityMapping`: no longer read (the entity mapping builds itself on first access, see entity-bundle).
- **BREAKING** `AustralTools::getValueByKey()`: use `valueByKey()`, which takes the same arguments.
- **BREAKING** `AustralTools::encrypte()` and `decrypte()`: they did not encrypt securely (a repeating XOR key, a fixed "random" key written into the result, no integrity check) and `encrypte()` reseeded the random generator of the whole process with `srand(0)`. No Austral bundle uses them. Use `sodium_crypto_secretbox()` instead (see UPGRADE-4.0).
- `ConsoleEventSubscriber`: its listeners were empty and it was never registered as a service.
- The `twig.extension.tools` alias is no longer public.
- `symfony/contracts` dependency: not used by the bundle. As a meta-package, it replaced the individual `symfony/*-contracts` packages, which hid them from composer-require-checker in the bundles depending on tools-bundle.

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
