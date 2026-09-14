# SugiPHP\DatabaseExt

**Version 1.0**

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
use SugiPHP\DatabaseExt\PdoExt;

$db = new PdoExt('sqlite:/path/to/database.sqlite');

$db->exec("INSERT INTO users (name) VALUES ('Ivan')");
$sth = $db->prepare('SELECT * FROM users WHERE id = ?');
$sth->execute([1]);
```

By default `PDO::ATTR_ERRMODE` is set to `PDO::ERRMODE_EXCEPTION` (unless overridden via the `$options` array), so failed statements throw a `PDOException` and the `ExecError`/`QueryError`/`ExecuteError` events fire as documented below.

### Events

Pass a PSR-14 `EventDispatcherInterface` to observe queries (e.g. for logging or profiling):

```php
use SugiPHP\DatabaseExt\PdoExt;
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
| `Rejected` | `PdoExt::exec()`/`query()`, `PdoStatementExt::execute()` | Instead of any of the above, when the statement is rejected by the read/write state guard (see below) |

On failure, the `*Error` event is dispatched instead of the matching `After*` event, and the original `PDOException` is then rethrown.

Each `After*`/`*Error` event carries a `getBefore()` accessor returning the matching `Before*` event instance. `PdoEvent` also exposes `setAttribute(string $key, mixed $value)` / `getAttribute(string $key): mixed`, so a listener can stash arbitrary data (e.g. a profiler job) on the `Before*` event and retrieve it via `getBefore()->getAttribute(...)` when the matching `After*`/`*Error` event fires, without keeping its own state between events.

### Logging and profiling example

`SugiPHP\DatabaseExt` only depends on the PSR-14 `EventDispatcherInterface`, not on a full PSR-14 implementation, so the simplest way to log and time every statement is to implement that interface directly with a single class that stashes a start time on the `Before*` event and reads it back on the matching `After*`/`*Error` event:

```php
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use SugiPHP\DatabaseExt\Event\{
    BeforeExec, BeforeQuery, BeforeExecute,
    AfterExec, AfterQuery, AfterExecute,
    ExecError, QueryError, ExecuteError,
    Rejected,
};
use SugiPHP\DatabaseExt\PdoExt;

class QueryProfiler implements EventDispatcherInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function dispatch(object $event): object
    {
        match (true) {
            $event instanceof BeforeExec,
            $event instanceof BeforeQuery,
            $event instanceof BeforeExecute
                => $event->setAttribute('start', microtime(true)),

            $event instanceof AfterExec,
            $event instanceof AfterQuery,
            $event instanceof AfterExecute
                => $this->logger->info('{ms}ms {sql}', $this->context($event)),

            $event instanceof ExecError,
            $event instanceof QueryError,
            $event instanceof ExecuteError
                => $this->logger->error(
                    '{ms}ms {sql} failed: {error}',
                    $this->context($event) + ['error' => $event->getException()->getMessage()]
                ),

            $event instanceof Rejected
                => $this->logger->warning('rejected (state={state}) {sql}', [
                    'state' => $event->getState(),
                    'sql' => $event->getQueryString(),
                ]),

            default => null,
        };

        return $event;
    }

    private function context(AfterExec|AfterQuery|AfterExecute|ExecError|QueryError|ExecuteError $event): array
    {
        $start = $event->getBefore()->getAttribute('start');

        return [
            'sql' => $event->getQueryString(),
            'ms' => round((microtime(true) - $start) * 1000, 1),
        ];
    }
}

$db = new PdoExt('sqlite:/path/to/database.sqlite', options: [
    'dispatcher' => new QueryProfiler($logger),
]);

$db->exec("INSERT INTO users (name) VALUES ('Ivan')"); // logged with its duration
```

Swap the `LoggerInterface` calls for whatever profiler/APM hook you use (e.g. push a span per query) — the point is that `setAttribute()`/`getAttribute()` on the `Before*` event is the mechanism for carrying state (a timer, a profiler span, a request ID, ...) from before a statement runs to after it finishes or fails.

### Read/write state

`PdoExt::setState()` restricts which statements may run:

```php
use SugiPHP\DatabaseExt\PdoExt;

$db = new PdoExt('sqlite:/path/to/database.sqlite');
$db->setState(PdoExt::STATE_READ_ONLY); // or STATE_READ_WRITE / STATE_UNAVAILABLE
```

In `STATE_READ_ONLY`, statements starting with `ALTER`, `CREATE`, `DELETE`, `DROP`, `GRANT`, `INSERT`, `MERGE`, `RENAME`, `REPLACE`, `REVOKE`, `TRUNCATE`, or `UPDATE` are rejected — `exec()`/`query()`/`execute()` return `false` and a `Rejected` event is dispatched instead of the usual `Before*`/`After*` pair, so a listener can audit or monitor blocked write attempts. This is a best-effort, first-keyword check, not a SQL parser — it will not catch a write hidden inside a CTE, a comment-prefixed statement, or a stacked query. That is what the driver-enforced read-only mode below is for; it stops those writes at the connection level even when this check lets them through. In `STATE_UNAVAILABLE`, all statements are rejected the same way.

This keyword check is an app-level guard and only covers statements issued through `PdoExt`/`PdoStatementExt`. As a second, driver-enforced layer, `setState()` also puts the underlying connection itself into (or out of) a read-only mode when the driver supports it:

| Driver | Command issued in `STATE_READ_ONLY` / `STATE_UNAVAILABLE` | Command issued in `STATE_READ_WRITE` |
|---|---|---|
| `pgsql` | `SET default_transaction_read_only = on` | `SET default_transaction_read_only = off` |
| `mysql` | `SET SESSION TRANSACTION READ ONLY` | `SET SESSION TRANSACTION READ WRITE` |
| `sqlite` | `PRAGMA query_only = ON` | `PRAGMA query_only = OFF` |

Other drivers rely solely on the app-level keyword check above.
