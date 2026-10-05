# Duva for PHP

The official [Duva](https://duva.ca) client library for PHP. Duva is a transactional email API
hosted in Canada.

```bash
composer require duva-mail/duva
composer require guzzlehttp/guzzle  # default PSR-18 transport (see "Configuration" below)
```

Requires PHP 8.2 or later.

## Sending a message

```php
use Duva\Client;

$duva = new Client(apiKey: 'dv_...', domain: 'example.com'); // or DUVA_API_KEY / DUVA_DOMAIN

$message = $duva->messages->send([
    'from' => 'Example <notifications@example.com>',
    'to' => ['client@example.org'],
    'subject' => 'Your order',
    'text' => 'Thank you for your order.',
]);
echo $message->id, ' ', $message->status; // "queued": always asynchronous
```

### To, Cc and Bcc

`to`, `cc` and `bcc` take addresses or `Name <address>`. Every copy shows all the `to` and all the
`cc`; a `bcc` address appears only on its own copy. The three lists together count against your
plan's recipient maximum.

```php
$duva->messages->send([
    'from' => 'Example <notifications@example.com>',
    'to' => ['Jean Tremblay <jean@example.org>'],
    'cc' => ['accounting@example.org'],
    'bcc' => ['archive@example.com'],
    'subject' => 'Your order',
    'text' => 'Thank you for your order.',
]);
```

## Reading events and pagination

```php
foreach ($duva->events->listAll(type: 'bounced') as $event) {
    echo $event->type, ' ', $event->detail['recipient'] ?? '', "\n";
}
```

`events->list()` and `suppressions->list()` return one page (`->data`, `->nextCursor`);
`events->listAll()` and `suppressions->listAll()` return a `Generator` that follows `nextCursor`
for you, without ever loading every page into memory, optionally bounded with `maxItems:`.

## Verifying a webhook

```php
use Duva\Webhooks;
use Duva\Exceptions\WebhookSignatureException;

try {
    $event = Webhooks::constructEvent(getenv('DUVA_WEBHOOK_SECRET'), $headers, $rawBody);
    echo $event->type, ' ', $event->data['message_id'] ?? '';
} catch (WebhookSignatureException) {
    // respond 400
}
```

`$rawBody` must be the **exact bytes** Duva sent (`file_get_contents('php://input')`, not a
re-serialized parsed body): re-encoding it changes the bytes and invalidates the signature.
Rotating your webhook secret? Pass an array — `Webhooks::constructEvent([$old, $new], ...)` —
while both are active.

## Errors

Every error Duva answers with is a `Duva\Exceptions\DuvaException` subclass; rely on
`->errorCode` (the contract), never on the exception message (its wording can change):

```php
use Duva\Exceptions\NotFoundException;
use Duva\Exceptions\QuotaExceededException;
use Duva\Exceptions\ValidationException;

try {
    $duva->messages->send([...]);
} catch (ValidationException $e) {
    var_dump($e->fields); // [['field' => 'to[0]', 'message' => '...']]
} catch (QuotaExceededException $e) {
    echo "retry in {$e->retryAfter}s";
} catch (NotFoundException) {
    // the API key, domain or resource could not be found
}
```

Network failures and timeouts throw `Duva\Exceptions\ConnectionException` /
`TimeoutException` instead (no HTTP response was ever received). Reads and `messages->send()`
(idempotency-key protected) are retried automatically on a transient failure;
`suppressions->add()`/`remove()` and `webhooks->create()`/`delete()` are not, because the
outcome of a timed-out first attempt is unknown. A `429 quota_exceeded` is never retried
automatically (its `retryAfter` can be hours); a `429 rate_limited` is, as long as the wait fits
within `maxRetryWaitSeconds` (30s by default).

## Attachments

```php
use Duva\Attachment;

$attachment = Attachment::fromFile('./invoice.pdf');
$duva->messages->send([..., 'attachments' => [$attachment]]);
```

`Attachment::fromBytes($filename, $content, contentType: null, contentId: null)` works from data
already in memory; `contentId` turns the attachment into an inline image the HTML references
with `cid:`.

## Testing your own application

```php
use Duva\Http\TestTransport;

$transport = new TestTransport(fn ($config, $spec) => TestTransport::response(['status' => 'ok']));
$duva = new Client(apiKey: 'dv_test', domain: 'example.com', transport: $transport);
$duva->health();
$transport->requests[0]->path; // "/health"
```

`Duva\Http\TestTransport` records every `RequestSpec` sent and lets you script the responses: no
network call, no separate HTTP mocking library required.

## Configuration

| Argument | Default | |
|---|---|---|
| `apiKey:` | `DUVA_API_KEY` | Required. |
| `domain:` | `DUVA_DOMAIN` | Required: the domain this key was created for. |
| `baseUrl:` | `https://api.duva.ca` | |
| `timeout:` | `10` (seconds) | |
| `maxRetries:` | `2` | Network failures / `5xx` on a safe-to-retry call. |
| `maxRetryWaitSeconds:` | `30` | A `429 rate_limited` with a longer wait is not retried. |
| `language:` | unset | `"en"` or `"fr"`: the language of `error.message`. |
| `transport:` | a `Psr18Transport` built from Guzzle | Inject any `Duva\Http\TransportInterface` (a different PSR-18 client, proxying, tests). |

Only `psr/http-client`, `psr/http-message` and `psr/http-factory` (interfaces) are hard
dependencies. Without an injected `transport:`, the client builds a default one from
`guzzlehttp/guzzle` (`composer require guzzlehttp/guzzle` if you haven't); to use a different
PSR-18 client instead, wrap it in `Duva\Http\Psr18Transport` yourself and pass it as `transport:`.

## Full reference

The complete API surface and the OpenAPI specification this library follows:
<https://duva.ca/en/docs> and <https://duva.ca/openapi.json>.

## Development

```bash
composer install
composer test                                                       # unit tests
php scripts/fetch-conformance.php && composer test:conformance
composer stan                                                        # PHPStan, level max
```

Response types are hand-written value objects (`src/Models/`, `readonly` classes): PHP is the
other language in `docs/bibliotheques-clientes.md` §4/§5 (besides Ruby) without an idiomatic
single-purpose "models from OpenAPI" generator — `openapi-generator`'s PHP output is verbose,
mutable getter/setter/`ArrayAccess` classes, not the strict `readonly` types this library
targets. The client itself (retries, pagination, errors, webhooks) is checked against the shared
fixtures published in [`duva-mail/duva-conformance`](https://github.com/duva-mail/duva-conformance).

## License

MIT, see [LICENSE](./LICENSE).
