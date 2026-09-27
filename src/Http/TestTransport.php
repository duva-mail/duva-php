<?php

declare(strict_types=1);

namespace Duva\Http;

use Duva\Config;

/**
 * A scriptable fake Transport (`docs/bibliotheques-clientes.md` section 3.10: "a fake transport
 * and a response builder, so you can test YOUR OWN application without a network call"). Inject
 * it via `Client::__construct(transport: ...)`.
 *
 *   $transport = new TestTransport(fn (Config $config, RequestSpec $spec) => TestTransport::response(['status' => 'ok']));
 *   $duva = new Client(apiKey: 'dv_test', domain: 'example.com', transport: $transport);
 *   $duva->health();
 *   $transport->requests[0]->path; // "/health"
 */
final class TestTransport implements TransportInterface
{
    /** @var array<int, RequestSpec> Every RequestSpec actually sent, in order. */
    public array $requests = [];

    /** @param callable(Config, RequestSpec): RawResponse $handler */
    public function __construct(private readonly mixed $handler)
    {
    }

    public function send(Config $config, RequestSpec $spec): RawResponse
    {
        $this->requests[] = $spec;

        return ($this->handler)($config, $spec);
    }

    /**
     * Builds a RawResponse from a plain value (or null, for a 204), as your handler returns it.
     *
     * @param array<string, string> $headers
     */
    public static function response(mixed $data = null, array $headers = []): RawResponse
    {
        $lowered = [];
        foreach ($headers as $name => $value) {
            $lowered[strtolower($name)] = $value;
        }

        return new RawResponse($data, $lowered);
    }
}
