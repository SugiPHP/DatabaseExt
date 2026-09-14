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

By default `PDO::ATTR_ERRMODE` is set to `PDO::ERRMODE_EXCEPTION` (unless overridden via the `$options` array), so failed statements throw a `PDOException` and the `ExecError`/`QueryError`/`ExecuteError` events fire as documented below.

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

In `STATE_READ_ONLY`, statements containing `ALTER`, `CREATE`, `DELETE`, `DROP`, `GRANT`, `INSERT`, `MERGE`, `RENAME`, `REPLACE`, `REVOKE`, `TRUNCATE`, or `UPDATE` as a whole word anywhere in the statement are rejected (`exec()`/`query()` return `false` without dispatching any event) — not just when the statement starts with one of them, so data-modifying CTEs, comment-prefixed statements, and stacked queries are also caught. This is a best-effort keyword check, not a SQL parser, so it can still be fooled and may also reject legitimate read statements that happen to contain one of these words (e.g. in a string literal). In `STATE_UNAVAILABLE`, all statements are rejected.

This keyword check is an app-level guard and only covers statements issued through `PdoExt`/`PdoStatementExt`. As a second, driver-enforced layer, `setState()` also puts the underlying connection itself into (or out of) a read-only mode when the driver supports it:

| Driver | Command issued in `STATE_READ_ONLY` / `STATE_UNAVAILABLE` | Command issued in `STATE_READ_WRITE` |
|---|---|---|
| `pgsql` | `SET default_transaction_read_only = on` | `SET default_transaction_read_only = off` |
| `mysql` | `SET SESSION TRANSACTION READ ONLY` | `SET SESSION TRANSACTION READ WRITE` |
| `sqlite` | `PRAGMA query_only = ON` | `PRAGMA query_only = OFF` |

Other drivers (e.g. `sqlsrv`, `oci`) have no equivalent session-level pragma, so they rely solely on the app-level keyword check above.
