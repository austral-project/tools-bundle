# Upgrade from 3.1 to 4.0

## Requirements

- PHP 8.4 minimum
- Symfony 7.4
- Doctrine ORM 3

## Update the dependencies

```diff
- "austral/tools-bundle": "^3.1"
+ "austral/tools-bundle": "^4.0"
```

All `austral/*` packages must be upgraded to `^4.0` at the same time.

## Breaking changes

### Services: explicit definitions, private class ids

The services of the bundle are no longer autowired nor autoconfigured: each one is defined with its arguments and tags. As a result, the service ids named after a class are **private**. Only the `austral.tools.*` aliases are public, and they are the supported way to get a service from the container.

| Class | Public alias |
|-------|--------------|
| `Services\Debug` | `austral.tools.debug` |
| `Services\ServicesStatusChecker` | `austral.tools.status-checker` |

Before:

```php
$debug = $container->get(\Austral\ToolsBundle\Services\Debug::class);
```

After:

```php
$debug = $container->get('austral.tools.debug');
```

Injecting a service is not affected: autowiring `Austral\ToolsBundle\Services\Debug` in a constructor of your application keeps working.

The `twig.extension.tools` alias is no longer public. The Twig functions, filters and tests keep their 3.1 names.

### Austral base command: no more service container

`Austral\ToolsBundle\Command\Base\Command` no longer receives the service container: `setContainer()` and the `$container` property are removed, and so is `$initEntityMapping`. A command receives the services it uses through its constructor, and the event dispatcher through `setEventDispatcher()`, called by its service definition. Without a dispatcher, the command runs without dispatching the console events.

Before:

```php
final class ImportCommand extends Command
{
  protected function executeCommand(InputInterface $input, OutputInterface $output): void
  {
    $importer = $this->container->get('app.importer');
    $importer->run();
  }
}
```

After:

```php
final class ImportCommand extends Command
{
  public function __construct(private readonly Importer $importer)
  {
    parent::__construct();
  }

  protected function executeCommand(InputInterface $input, OutputInterface $output): void
  {
    $this->importer->run();
  }
}
```

```yaml
# config/services.yaml of the application
App\Command\ImportCommand:
  calls:
    - [setEventDispatcher, ['@event_dispatcher']]
  tags:
    - { name: console.command, command: 'app:import' }
```

With `autowire: true`, the importer is injected automatically. The `calls` line is needed for the `austral.event.console.execute.before` / `.after` events to be dispatched.

### `StatusChecker` renamed `StatusCheckerCommand`

`Austral\ToolsBundle\Command\StatusChecker` is renamed `Austral\ToolsBundle\Command\StatusCheckerCommand`. The command name, `austral:tools:status-checker`, does not change. Only code that extends or references the class must be updated.

### `AustralTools::encrypte()` and `decrypte()` removed

These methods are removed, without replacement in tools-bundle. They did not encrypt securely:

- the value was XORed with a key repeated in a loop (`sha1($cryptKey)`), which anyone who knows part of the clear text can recover;
- the "random" key added on top was always the same (`srand((int) microtime() * 1000000)` is `srand(0)`) and was written into the result;
- nothing detected a modified value: `decrypte()` returned whatever came out;
- `encrypte()` reseeded the random generator of the whole PHP process, so `rand()`, `mt_rand()`, `shuffle()` and `array_rand()` became predictable afterwards.

No Austral bundle uses them. If your application does, use the libsodium functions bundled with PHP: they encrypt and authenticate the value, with a random nonce.

Before:

```php
$token = AustralTools::encrypte(['id' => $user->getId()], $cryptKey);
$data = AustralTools::decrypte($token, $cryptKey);
```

After:

```php
// Once: generate a 32-byte key and keep it as a secret (for example base64-encoded in a Symfony secret).
$key = sodium_crypto_secretbox_keygen();

// Encrypt
$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$token = base64_encode($nonce.sodium_crypto_secretbox(json_encode(['id' => $user->getId()]), $nonce, $key));

// Decrypt
$decoded = base64_decode($token, true);
$nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$json = sodium_crypto_secretbox_open(substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, $key);
if (false === $json)
{
  throw new \RuntimeException('The token was modified or the key is wrong.');
}
$data = json_decode($json, true);
```

> **Warning:** values encrypted with `encrypte()` cannot be read by the new code. If your application stored such values (in the database, in links sent by email…), decrypt them with tools-bundle 3.1 and encrypt them again before upgrading, or let them expire.

### `AustralTools::getValueByKey()` removed

It was deprecated and only called `valueByKey()`, which takes the same arguments.

```bash
grep -rl 'AustralTools::getValueByKey' src/ | xargs sed -i 's/AustralTools::getValueByKey/AustralTools::valueByKey/g'
```

<!--
For each break: what changes, why, and a before / after example.

### Annotations replaced by attributes

Before:
    /** @Austral\Field(...) */
After:
    #[Austral\Field(...)]
-->
