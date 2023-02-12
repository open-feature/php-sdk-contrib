<?php

declare(strict_types=1);

namespace OpenFeature\Providers\CloudBees\transformers;

class IdentityTransformer
{
    /**
     * @param bool|string|int|float|mixed[] $x
     *
     * @return bool|string|int|float|mixed[]
     */
    public function __invoke(mixed $x): mixed
    {
        return $x;
    }
}
