<?php

namespace App\Service;

use Rikudou\DynamoDbCache\Encoder\CacheItemEncoderInterface;

final class Base64CacheItemEncoder implements CacheItemEncoderInterface
{
    public function encode(mixed $input): string
    {
        return base64_encode(serialize($input));
    }

    public function decode(string $input): mixed
    {
        return unserialize(base64_decode($input));
    }
}
