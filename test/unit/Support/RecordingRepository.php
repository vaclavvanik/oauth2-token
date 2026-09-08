<?php

declare(strict_types=1);

namespace VaclavVanikTest\Oauth2Token\Support;

use VaclavVanik\Oauth2Token\Cache\Entry;
use VaclavVanik\Oauth2Token\Cache\Key;
use VaclavVanik\Oauth2Token\Cache\Repository;

/**
 * Wraps a repository and counts how often each method is hit, so a test can assert the caller does not
 * re-read the store more than it needs to.
 */
final class RecordingRepository implements Repository
{
    /** @var Repository */
    private $inner;

    /** @var array<string, int> */
    public $calls = ['has' => 0, 'load' => 0, 'save' => 0, 'delete' => 0];

    public function __construct(Repository $inner)
    {
        $this->inner = $inner;
    }

    public function has(Key $key): bool
    {
        ++$this->calls['has'];

        return $this->inner->has($key);
    }

    public function load(Key $key): Entry
    {
        ++$this->calls['load'];

        return $this->inner->load($key);
    }

    public function save(Key $key, Entry $entry): void
    {
        ++$this->calls['save'];

        $this->inner->save($key, $entry);
    }

    public function delete(Key $key): void
    {
        ++$this->calls['delete'];

        $this->inner->delete($key);
    }
}
