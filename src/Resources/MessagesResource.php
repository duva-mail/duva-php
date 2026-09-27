<?php

declare(strict_types=1);

namespace Duva\Resources;

use Duva\Attachment;
use Duva\Http\Requester;
use Duva\Http\RequestSpec;
use Duva\Config;
use Duva\Models\Message;
use Duva\Models\MessageAccepted;
use Duva\Models\SendMessageResult;

final readonly class MessagesResource
{
    public function __construct(
        private Requester $requester,
        private Config $config,
    ) {
    }

    /**
     * Accepts a message for delivery. Always asynchronous: `queued` never confirms a delivery,
     * only that the message was validated. Read the outcome with `messages->get()`,
     * `events->list()`, or a webhook.
     *
     * `$params` (all string keys): `from`, `to` (string[]), `subject`, `html`, `text`, `tags`
     * (string[]), `tracking` (`['opens' => bool, 'clicks' => bool]`), `reply_to`, `headers`
     * (string[string]), `metadata` (string[string]), `attachments` (Attachment[]),
     * `idempotency_key`.
     *
     * `idempotency_key`: unique per domain. A UUID is generated when omitted (see
     * `docs/bibliotheques-clientes.md` section 3.3): a network-level retry of the SAME call can
     * then never create a duplicate message, but two separate calls each get their own random
     * key, so they are NOT deduplicated against each other; pass your own stable key for that
     * (e.g. an order id).
     *
     * @param array<string, mixed> $params
     */
    public function send(array $params): SendMessageResult
    {
        $body = $this->buildBody($params);
        $rawKey = $params['idempotency_key'] ?? null;
        $key = is_string($rawKey) && $rawKey !== '' ? $rawKey : self::uuidV4();
        $result = $this->requester->send(new RequestSpec(
            method: 'POST',
            path: $this->config->domainPath() . '/messages',
            body: $body,
            idempotencyKey: $key,
            safeRetry: true,
        ));
        $accepted = MessageAccepted::fromArray($result->asArray());

        return new SendMessageResult(
            id: $accepted->id,
            status: $accepted->status,
            replayed: $result->header('idempotent-replayed') === 'true',
            location: $result->header('location'),
        );
    }

    /** The message's status and each recipient's status. */
    public function get(string $id): Message
    {
        $result = $this->requester->send(new RequestSpec(
            method: 'GET',
            path: $this->config->domainPath() . "/messages/{$id}",
            safeRetry: true,
        ));

        return Message::fromArray($result->asArray());
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function buildBody(array $params): array
    {
        /** @var array<int, Attachment>|null $attachments */
        $attachments = $params['attachments'] ?? null;
        if ($attachments !== null && $attachments !== []) {
            Attachment::assertLimits($attachments);
        }
        $body = [
            'from' => $params['from'],
            'to' => $params['to'],
            'subject' => $params['subject'],
        ];
        foreach (['html', 'text', 'tags', 'reply_to', 'headers', 'metadata'] as $field) {
            if (isset($params[$field])) {
                $body[$field] = $params[$field];
            }
        }
        if (isset($params['tracking'])) {
            /** @var array{opens?: bool, clicks?: bool} $tracking */
            $tracking = $params['tracking'];
            $body['tracking'] = [
                'opens' => (bool) ($tracking['opens'] ?? false),
                'clicks' => (bool) ($tracking['clicks'] ?? false),
            ];
        }
        if ($attachments !== null) {
            $body['attachments'] = array_map(static fn (Attachment $a): array => $a->toArray(), $attachments);
        }

        return $body;
    }

    private static function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
