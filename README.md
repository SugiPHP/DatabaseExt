# Clear\Database

**Version 1.3**

Extends PHP's `PDO`/`PDOStatement` with PSR-14 event dispatching and a read/write connection state guard.

## Components

| Class | Description |
|---|---|
| `PdoExt` | `PDO` subclass that dispatches events around `exec()`/`query()` and enforces the read/write state |
| `PdoStatementExt` | `PDOStatement` subclass (used automatically by `PdoExt`) that dispatches events around `execute()` |
| `PdoInterface` | Interface listing the standard `PDO` methods |
| `PdoStatementInterface` | Interface listing the standard `PDOStatement` methods |
| `Event\*` | PSR-14 event classes dispatched by `PdoExt`/`PdoStatementExt` (see below) |

## Usage

```php
use Clear\Database\PdoExt;

$db = new PdoExt('sqlite:/path/to/database.sqlite');

$db->exec("INSERT INTO users (name) VALUES ('Ivan')");
$sth = $db->prepare('SELECT * FROM users WHERE id = ?');
$sth->execute([1]);
```

### Events

Pass a PSR-14 `EventDispatcherInterface` to observe queries (e.g. for logging or profiling):

```php
use Clear\Database\PdoExt;
use Psr\EventDispatcher\EventDispatcherInterface;

$db = new PdoExt('sqlite:/path/to/database.sqlite');
$db->setEventDispatcher($dispatcher);
```

| Event | Dispatched by | When |
|---|---|---|
| `AfterConnect` | `PdoExt` | After the connection is established |
| `BeforeExec` / `AfterExec` | `PdoExt::exec()` | Around a successful `exec()` call |
| `ExecError` | `PdoExt::exec()` | When `exec()` throws a `PDOException` |
| `BeforeQuery` / `AfterQuery` | `PdoExt::query()` | Around a successful `query()` call |
| `QueryError` | `PdoExt::query()` | When `query()` throws a `PDOException` |
| `BeforeExecute` / `AfterExecute` | `PdoStatementExt::execute()` | Around a successful prepared-statement `execute()` call |
| `ExecuteError` | `PdoStatementExt::execute()` | When `execute()` throws a `PDOException` |

On failure, the `*Error` event is dispatched instead of the matching `After*` event, and the original `PDOException` is then rethrown.

Each `After*`/`*Error` event carries a `getBefore()` accessor returning the matching `Before*` event instance. `PdoEvent` also exposes `setAttribute(string $key, mixed $value)` / `getAttribute(string $key): mixed`, so a listener can stash arbitrary data (e.g. a profiler job) on the `Before*` event and retrieve it via `getBefore()->getAttribute(...)` when the matching `After*`/`*Error` event fires, without keeping its own state between events.

### Read/write state

`PdoExt::setState()` restricts which statements may run:

```php
use Clear\Database\PdoExt;

$db = new PdoExt('sqlite:/path/to/database.sqlite');
$db->setState(PdoExt::STATE_READ_ONLY); // or STATE_READ_WRITE / STATE_UNAVAILABLE
```

In `STATE_READ_ONLY`, statements starting with `ALTER`, `CREATE`, `DELETE`, `DROP`, `GRANT`, `INSERT`, `RENAME`, `REVOKE`, `TRUNCATE`, or `UPDATE` are rejected (`exec()`/`query()` return `false` without dispatching any event). In `STATE_UNAVAILABLE`, all statements are rejected.
