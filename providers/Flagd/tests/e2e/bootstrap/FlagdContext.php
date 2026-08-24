<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\Test\e2e\bootstrap;

use Behat\Behat\Context\Context;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use OpenFeature\Providers\Flagd\FlagdProvider;
use OpenFeature\Providers\Flagd\config\ConfigFactory;
use OpenFeature\Providers\Flagd\config\HttpConfig;
use OpenFeature\implementation\flags\Attributes;
use OpenFeature\implementation\flags\EvaluationContext;
use OpenFeature\interfaces\flags\EvaluationContext as EvaluationContextInterface;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\ResolutionDetails;
use PHPUnit\Framework\Assert;
use RuntimeException;

use function getenv;
use function is_array;
use function json_decode;
use function strtolower;

/**
 * Behat step definitions for the flagd testbed gherkin suite, driving a real HTTP-only
 * FlagdProvider against a flagd testbed container. Only the evaluation feature is wired up
 * (see behat.yml.dist); this is an opt-in e2e suite.
 */
final class FlagdContext implements Context
{
    private static ?FlagdTestbedContainer $testbed = null;
    private static string $host;
    private static int $port;

    private Provider $provider;

    /** @var array<string, bool|float|int|string|mixed[]|null> */
    private array $attributes = [];
    private ?string $targetingKey = null;

    private string $flagType;
    private string $flagKey;
    private string $defaultValue;
    private ResolutionDetails $details;

    /**
     * @BeforeSuite
     */
    public static function startTestbed(): void
    {
        // Allow pointing at an already-running flagd (e.g. started by CI) instead of a container.
        $host = getenv('FLAGD_HOST');
        $port = getenv('FLAGD_PORT');

        if ($host !== false && $host !== '' && $port !== false && $port !== '') {
            self::$host = $host;
            self::$port = (int) $port;

            return;
        }

        self::$testbed = FlagdTestbedContainer::start();
        self::$host = self::$testbed->getHost();
        self::$port = self::$testbed->getPort();
    }

    /**
     * @AfterSuite
     */
    public static function stopTestbed(): void
    {
        self::$testbed?->stop();
        self::$testbed = null;
    }

    /**
     * @BeforeScenario
     */
    public function prepareProvider(): void
    {
        $client = new GuzzleClient(['http_errors' => false]);
        $factory = new HttpFactory();

        $config = ConfigFactory::fromOptions(
            self::$host,
            self::$port,
            'http',
            false,
            new HttpConfig($client, $factory, $factory),
        );

        $this->provider = new FlagdProvider($config);
        $this->attributes = [];
        $this->targetingKey = null;
    }

    /**
     * @Given /^an option "[^"]*" of type "[^"]*" with value "[^"]*"$/
     */
    public function anOption(): void
    {
        // no-op: the HTTP-only PHP provider is stateless (no caching/streaming options)
    }

    /**
     * @Given /^a stable flagd provider$/
     */
    public function aStableFlagdProvider(): void
    {
        // provider is (re)created per scenario in prepareProvider()
    }

    /**
     * @Given /^a (?P<type>\w+)-flag with key "(?P<key>[^"]*)" and a default value "(?P<default>[^"]*)"$/
     */
    public function aFlagWithKeyAndDefault(string $type, string $key, string $default): void
    {
        $this->flagType = $type;
        $this->flagKey = $key;
        $this->defaultValue = $default;
    }

    /**
     * @Given /^a context containing a key "(?P<key>[^"]*)", with type "(?P<type>[^"]*)" and with value "(?P<value>[^"]*)"$/
     */
    public function aContextContainingKey(string $key, string $type, string $value): void
    {
        $this->attributes[$key] = self::cast($type, $value);
    }

    /**
     * @When /^the flag was evaluated with details$/
     */
    public function theFlagWasEvaluatedWithDetails(): void
    {
        $context = $this->buildContext();

        switch ($this->flagType) {
            case 'Boolean':
                $this->details = $this->provider->resolveBooleanValue($this->flagKey, strtolower($this->defaultValue) === 'true', $context);

                break;
            case 'String':
                $this->details = $this->provider->resolveStringValue($this->flagKey, $this->defaultValue, $context);

                break;
            case 'Integer':
                $this->details = $this->provider->resolveIntegerValue($this->flagKey, (int) $this->defaultValue, $context);

                break;
            case 'Float':
                $this->details = $this->provider->resolveFloatValue($this->flagKey, (float) $this->defaultValue, $context);

                break;
            case 'Object':
                $decoded = json_decode($this->defaultValue, true);
                $this->details = $this->provider->resolveObjectValue($this->flagKey, is_array($decoded) ? $decoded : [], $context);

                break;
            default:
                throw new RuntimeException('Unsupported flag type: ' . $this->flagType);
        }
    }

    /**
     * @Then /^the resolved details value should be "(?P<value>[^"]*)"$/
     */
    public function theResolvedValueShouldBe(string $value): void
    {
        Assert::assertEquals(self::cast($this->flagType, $value), $this->details->getValue());
    }

    /**
     * @Then /^the reason should be "(?P<reason>[^"]*)"$/
     */
    public function theReasonShouldBe(string $reason): void
    {
        Assert::assertEquals($reason, $this->details->getReason());
    }

    /**
     * @Then /^the error-code should be "(?P<code>[^"]*)"$/
     */
    public function theErrorCodeShouldBe(string $code): void
    {
        $error = $this->details->getError();

        if ($code === '') {
            Assert::assertNull($error);

            return;
        }

        Assert::assertNotNull($error);
        Assert::assertEquals($code, (string) $error->getResolutionErrorCode()->getValue());
    }

    private function buildContext(): ?EvaluationContextInterface
    {
        if ($this->attributes === [] && $this->targetingKey === null) {
            return null;
        }

        return new EvaluationContext($this->targetingKey, new Attributes($this->attributes));
    }

    /**
     * @return bool|float|int|string|mixed[]|null
     */
    private static function cast(string $type, string $value)
    {
        switch ($type) {
            case 'Boolean':
                return strtolower($value) === 'true';
            case 'Integer':
                return (int) $value;
            case 'Float':
                return (float) $value;
            case 'Object':
                $decoded = json_decode($value, true);

                return is_array($decoded) ? $decoded : null;
            case 'String':
            default:
                return $value;
        }
    }
}
