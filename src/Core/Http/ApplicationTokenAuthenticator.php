<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Core\Http;

use Symfony\Component\HttpFoundation\Request;

final class ApplicationTokenAuthenticator
{
    public const HEADER_NAME = 'X-AM-Application-Token';

    /** @var string */
    private $expectedToken;

    public function __construct(string $expectedToken)
    {
        $this->expectedToken = $expectedToken;
    }

    public function matchesRequest(Request $request): bool
    {
        return $this->matchesHeaderMap($this->headersFromRequest($request));
    }

    /**
     * @param array<string, list<string>> $headers
     */
    public function matchesHeaderMap(array $headers): bool
    {
        $token = $this->extractToken($headers);
        if ('' === $token) {
            return false;
        }

        return '' !== $this->expectedToken && hash_equals($this->expectedToken, $token);
    }

    /**
     * @return array<string, list<string>>
     */
    private function headersFromRequest(Request $request): array
    {
        /** @var array<string, list<string>> $headers */
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            if (!\is_array($values)) {
                continue;
            }
            $stringValues = [];
            foreach ($values as $value) {
                $stringValues[] = (string) $value;
            }
            $headers[(string) $name] = $stringValues;
        }

        return $headers;
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private function extractToken(array $headers): string
    {
        $needle = strtolower(self::HEADER_NAME);
        foreach ($headers as $name => $values) {
            if (strtolower((string) $name) !== $needle) {
                continue;
            }

            return trim((string) ($values[0] ?? ''));
        }

        return '';
    }
}
