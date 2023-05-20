<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Split;

use OpenFeature\Providers\Split\errors\InvalidTreatmentTypeException;
use OpenFeature\Providers\Split\errors\SplitFactoryCreationException;
use OpenFeature\Providers\Split\errors\TargetingKeyMissingException;
use OpenFeature\Providers\Split\treatments\TreatmentParser;
use OpenFeature\Providers\Split\treatments\TreatmentValidator;
use OpenFeature\implementation\provider\AbstractProvider;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionDetailsFactory;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\ResolutionDetails;
use OpenFeature\interfaces\provider\ThrowableWithResolutionError;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SplitIO\Component\Common\Di;
use SplitIO\Sdk;
use SplitIO\Sdk\ClientInterface;
use SplitIO\Sdk\Factory\SplitFactoryInterface;
use Throwable;

use function is_null;

class SplitProvider extends AbstractProvider implements Provider
{
    protected const NAME = 'SplitProvider';

    /**
     * The Split factory will only be created one time
     */
    private static SplitFactoryInterface $factory;

    private ClientInterface $client;

    /**
     * Create a SplitProvider with the provided factory configuration options
     *
     * The Split SDK will not allow multiple factories to be created. In the event that one
     * already exists, that one will be used for the SplitProvider. If the Factory cannot
     * be created and one does not exist, this will throw a SplitFactoryCreationException
     *
     * @see https://help.split.io/hc/en-us/articles/360020350372-PHP-SDK#configuration
     *
     * @param mixed[] $options The configuration options for the client
     *
     * @throws SplitFactoryCreationException
     */
    public function __construct(?string $apiKey = '', array $options = [])
    {
        if (isset(self::$factory)) {
            $factory = self::$factory;
        } else {
            /** @var SplitFactoryInterface|null $factory */
            $factory = Sdk::factory($apiKey, $options);
            if (is_null($factory)) {
                throw new SplitFactoryCreationException();
            }

            self::$factory = $factory;
        }

        $this->client = $factory->client();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        Di::setLogger($logger);
    }

    public function getLogger(): LoggerInterface
    {
        $logger = Di::getLogger();

        return $logger instanceof LoggerInterface ? $logger : new NullLogger();
    }

    public function resolveBooleanValue(string $flagKey, bool $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolveValue($flagKey, FlagValueType::BOOLEAN, $defaultValue, $context);
    }

    public function resolveStringValue(string $flagKey, string $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolveValue($flagKey, FlagValueType::STRING, $defaultValue, $context);
    }

    public function resolveIntegerValue(string $flagKey, int $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolveValue($flagKey, FlagValueType::INTEGER, $defaultValue, $context);
    }

    public function resolveFloatValue(string $flagKey, float $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolveValue($flagKey, FlagValueType::FLOAT, $defaultValue, $context);
    }

    /**
     * @param mixed[] $defaultValue
     */
    public function resolveObjectValue(string $flagKey, array $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolveValue($flagKey, FlagValueType::OBJECT, $defaultValue, $context);
    }

    /**
     * @param bool|string|int|float|mixed[] $defaultValue
     */
    private function resolveValue(string $flagKey, string $flagType, mixed $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        try {
            if (is_null($context)) {
                throw new TargetingKeyMissingException();
            }

            $targetingKey = $context->getTargetingKey();
            $attributes = $context->getAttributes()->toArray();

            $treatment = $this->client->getTreatment($targetingKey, $flagKey, $attributes);

            if (!TreatmentValidator::validate($flagType, $treatment)) {
                throw new InvalidTreatmentTypeException();
            }

            return ResolutionDetailsFactory::fromSuccess(
                TreatmentParser::parse($flagType, $treatment),
            );
        } catch (Throwable $err) {
            $detailsBuilder = new ResolutionDetailsBuilder();

            $detailsBuilder->withValue($defaultValue);

            if ($err instanceof ThrowableWithResolutionError) {
                $detailsBuilder->withError($err->getResolutionError());
            } else {
                $detailsBuilder->withError(
                    new ResolutionError(ErrorCode::GENERAL(), $err->getMessage()),
                );
            }

            return $detailsBuilder->build();
        }
    }
}
