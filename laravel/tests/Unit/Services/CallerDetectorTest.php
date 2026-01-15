<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\CallerType;
use App\Services\CallerDetector;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Services\CallerDetector
 */
final class CallerDetectorTest extends TestCase
{
    private CallerDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new CallerDetector();
    }

    /**
     * @test
     * @dataProvider seoBotUserAgentProvider
     */
    public function detectIdentifiesSeoBots(string $userAgent): void
    {
        $request = $this->createRequest(userAgent: $userAgent);

        $mask = $this->detector->detect($request);

        self::assertSame(CallerType::BOT->value, $mask);
    }

    /**
     * @test
     */
    public function detectIdentifiesAuthenticatedUser(): void
    {
        $request = $this->createRequest(
            userAgent: 'Mozilla/5.0',
            authToken: 'Bearer valid_token_xyz'
        );

        $mask = $this->detector->detect($request);

        self::assertSame(CallerType::AUTHENTICATED->value, $mask);
    }

    /**
     * @test
     */
    public function detectIdentifiesTokenAuthHeader(): void
    {
        $request = $this->createRequest(
            userAgent: 'Mozilla/5.0',
            authToken: 'Token secret_key_123'
        );

        $mask = $this->detector->detect($request);

        self::assertSame(CallerType::AUTHENTICATED->value, $mask);
    }

    /**
     * @test
     */
    public function detectDefaultsToAttackerForUnknownCaller(): void
    {
        $request = $this->createRequest();

        $mask = $this->detector->detect($request);

        self::assertSame(CallerType::ATTACKER->value, $mask);
    }

    /**
     * @test
     */
    public function detectGivesBotPriorityOverAuth(): void
    {
        $request = $this->createRequest(
            userAgent: 'Mozilla/5.0 Googlebot/2.1',
            authToken: 'Bearer valid_token'
        );

        $mask = $this->detector->detect($request);

        // Bot should be detected even with valid auth token
        self::assertSame(CallerType::BOT->value, $mask);
    }

    /**
     * @test
     */
    public function detectRejectsInvalidAuthFormat(): void
    {
        $request = $this->createRequest(
            userAgent: 'Mozilla/5.0',
            authToken: 'invalid_auth_format'
        );

        $mask = $this->detector->detect($request);

        // Should default to attacker due to invalid auth format
        self::assertSame(CallerType::ATTACKER->value, $mask);
    }

    /**
     * @test
     */
    public function detectIgnoresEmptyAuthToken(): void
    {
        $request = $this->createRequest(
            userAgent: 'Mozilla/5.0',
            authToken: ''
        );

        $mask = $this->detector->detect($request);

        self::assertSame(CallerType::ATTACKER->value, $mask);
    }

    /**
     * @test
     */
    public function detectUserAgentIsCaseInsensitive(): void
    {
        $request = $this->createRequest(userAgent: 'GOOGLEBOT');

        $mask = $this->detector->detect($request);

        self::assertSame(CallerType::BOT->value, $mask);
    }

    /**
     * @test
     */
    public function getLabelReturnsBotLabel(): void
    {
        $label = $this->detector->getLabel(CallerType::BOT->value);

        self::assertSame('SEO Bot', $label);
    }

    /**
     * @test
     */
    public function getLabelReturnsAuthLabel(): void
    {
        $label = $this->detector->getLabel(CallerType::AUTHENTICATED->value);

        self::assertSame('Authenticated User', $label);
    }

    /**
     * @test
     */
    public function getLabelReturnsAttackerLabel(): void
    {
        $label = $this->detector->getLabel(CallerType::ATTACKER->value);

        self::assertSame('Unknown Caller', $label);
    }

    /**
     * Create a mock request with specified headers.
     *
     * @param string $userAgent The User-Agent header
     * @param string $authToken The Authorization header
     * @return Request
     */
    private function createRequest(
        string $userAgent = '',
        string $authToken = ''
    ): Request {
        $request = new Request();
        
        if ($userAgent) {
            $request->headers->set('User-Agent', $userAgent);
        }
        
        if ($authToken) {
            $request->headers->set('Authorization', $authToken);
        }

        return $request;
    }

    /**
     * Provider for SEO bot user agent strings.
     *
     * @return array<array<string>>
     */
    public static function seoBotUserAgentProvider(): array
    {
        return [
            ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            ['Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'],
            ['Mozilla/5.0 (Slurp; slurp@inktomi.com; http://www.inktomi.com/slurp.html)'],
            ['Mozilla/5.0 (compatible; DuckDuckBot/1.0)'],
            ['Mozilla/5.0 (compatible; Baiduspider/2.0)'],
            ['Mozilla/5.0 (compatible; YandexBot/3.0)'],
            ['facebookexternalhit/1.1'],
            ['Twitterbot/1.0'],
            ['LinkedIn (+http://www.linkedin.com)'],
            ['Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/533.4 (KHTML, like Gecko) Chrome/5.0.375.99 Safari/533.4 WhatsApp/2.16.174'],
        ];
    }
}
