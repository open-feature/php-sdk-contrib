<?php

declare(strict_types=1);

namespace OpenFeature\Providers\CloudBees\transformers;

class IdentityTransformer
{
    /**
     * @param bool|string|int|float|mixed[]|null $x
     *
     * @return bool|string|int|float|mixed[]|null
     */
    public function __invoke(mixed $x): mixed
    {
        return $x;
    }
}
