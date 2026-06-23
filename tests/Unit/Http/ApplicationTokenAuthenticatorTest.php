<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Tests\Unit\Http;

use ApplicationManagerTools\AmDriver\Core\Http\ApplicationTokenAuthenticator;
use PHPUnit\Framework\TestCase;

final class ApplicationTokenAuthenticatorTest extends TestCase
{
    public function testMatchesHeaderMapWhenTokenIsValid(): void
    {
        // Arrange
        $authenticator = new ApplicationTokenAuthenticator('secret-app');

        // Act
        $matches = $authenticator->matchesHeaderMap(['X-AM-Application-Token' => ['secret-app']]);

        // Assert
        self::assertTrue($matches);
    }

    public function testRejectsMissingOrInvalidToken(): void
    {
        // Arrange
        $authenticator = new ApplicationTokenAuthenticator('secret-app');

        // Act
        $missing = $authenticator->matchesHeaderMap([]);
        $invalid = $authenticator->matchesHeaderMap(['X-AM-Application-Token' => ['wrong']]);

        // Assert
        self::assertFalse($missing);
        self::assertFalse($invalid);
    }
}
