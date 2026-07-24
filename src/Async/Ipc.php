<?php

declare(strict_types=1);

namespace Maybe\Async;

use RuntimeException;

/**
 * Small authenticated transport for the private parent/worker channel.
 */
final class Ipc
{
    /**
     * @param mixed $value
     */
    public static function encode($value, string $key): string
    {
        $serialized = serialize($value);
        $mac = hash_hmac('sha256', $serialized, $key);

        return $mac . "\n" . $serialized;
    }

    /**
     * @return mixed
     */
    public static function decode(string $encoded, string $key)
    {
        $parts = explode("\n", $encoded, 2);
        if (count($parts) !== 2 || !preg_match('/\A[a-f0-9]{64}\z/D', $parts[0])) {
            throw new RuntimeException('Invalid async IPC envelope');
        }

        if (!hash_equals($parts[0], hash_hmac('sha256', $parts[1], $key))) {
            throw new RuntimeException('Invalid async IPC signature');
        }

        $value = unserialize($parts[1], ['allowed_classes' => true, 'max_depth' => 4096]);
        if ($value === false && $parts[1] !== serialize(false)) {
            throw new RuntimeException('Invalid async IPC payload');
        }

        return $value;
    }
}
