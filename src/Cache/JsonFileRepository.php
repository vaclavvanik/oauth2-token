<?php

declare(strict_types=1);

namespace VaclavVanik\Oauth2Token\Cache;

use DateTimeImmutable;
use DateTimeInterface;
use RuntimeException;
use Throwable;
use VaclavVanik\Oauth2Token\AccessToken;
use VaclavVanik\Oauth2Token\Exception\ValueError;
use VaclavVanik\Oauth2Token\ExpirationValidator;

use function array_filter;
use function file_exists;
use function is_array;
use function json_decode;
use function json_encode;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function uniqid;

use const JSON_THROW_ON_ERROR;
use const LOCK_EX;

final class JsonFileRepository implements Repository
{
    /** @var string */
    private $file;

    /** @var DateTimeInterface|null */
    private $now;

    /** @var array<string, Entry>|null */
    private $cache;

    /** @var string|null */
    private static $lastError;

    /** @throws ValueError */
    public function __construct(string $file, ?DateTimeInterface $now = null)
    {
        if ($file === '') {
            throw new ValueError('File name cannot be empty.');
        }

        $this->file = $file;
        $this->now = $now;
    }

    public function has(Key $key): bool
    {
        $this->cacheRefresh();

        return isset($this->cache[$key->key()]);
    }

    public function load(Key $key): Entry
    {
        if ($this->has($key) === false) {
            throw Exception\NotFound::fromKey($key);
        }

        return $this->cache[$key->key()];
    }

    public function save(Key $key, Entry $entry): void
    {
        $this->cacheRefresh();

        $this->cache[$key->key()] = $entry;

        $this->persist();
    }

    public function delete(Key $key): void
    {
        $this->cacheRefresh();

        if (! isset($this->cache[$key->key()])) {
            return;
        }

        unset($this->cache[$key->key()]);

        $this->persist();
    }

    private function fileExists(): bool
    {
        return file_exists($this->file);
    }

    /**
     * Write the in-memory cache back to disk (expired entries pruned) and re-read it.
     *
     * @throws Exception\Runtime
     */
    private function persist(): void
    {
        try {
            $live = $this->filterNotExpired($this->cache ?? [], $this->now ?? new DateTimeImmutable());

            $this->write((string) json_encode($live, JSON_THROW_ON_ERROR));
        } catch (Throwable $e) {
            throw Exception\Runtime::fromThrowable($e);
        }

        $this->cacheRefresh();
    }

    /**
     * Read and parse the cache file. Readable but corrupt content is treated as an empty cache - the next
     * save() rewrites it - because losing a cache must stay cheap. A file that cannot be read at all
     * (permissions, a directory, an I/O error) is a misconfiguration and throws.
     *
     * @return array<string, Entry>
     *
     * @throws Exception\Runtime
     */
    private function readFile(): array
    {
        $data = self::box('file_get_contents', $this->file);

        if ($data === false || self::$lastError !== null) {
            throw new Exception\Runtime(
                sprintf('Cannot read cache file "%s": %s.', $this->file, self::$lastError ?? 'unknown error'),
            );
        }

        try {
            $decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($decoded)) {
                return [];
            }

            $items = [];

            foreach ($decoded as $key => $entryData) {
                $tokenData = is_array($entryData) ? ($entryData[Entry::TOKEN] ?? null) : null;

                if (! is_array($tokenData)) {
                    return [];
                }

                $entryData[Entry::TOKEN] = AccessToken::fromArray($tokenData);

                $items[(string) $key] = Entry::fromArray($entryData);
            }

            return $items;
        } catch (Throwable $e) {
            return [];
        }
    }

    private function cacheRefresh(): void
    {
        $this->cache = $this->fileExists() ? $this->readFile() : null;
    }

    /**
     * Write atomically: a concurrent reader sees either the old file or the fully written new one, never a
     * half-written file.
     *
     * @throws RuntimeException
     */
    private function write(string $data): void
    {
        $tmp = $this->file . '.' . uniqid('', true) . '.tmp';

        if (self::box('file_put_contents', $tmp, $data, LOCK_EX) === false) {
            throw new RuntimeException($this->writeError());
        }

        if (self::box('rename', $tmp, $this->file) === false) {
            $error = $this->writeError();

            self::box('unlink', $tmp);

            throw new RuntimeException($error);
        }
    }

    private function writeError(): string
    {
        return sprintf('Cannot write cache file "%s": %s.', $this->file, self::$lastError ?? 'unknown error');
    }

    /**
     * Call a native function with its warning captured into self::$lastError instead of emitted, so a failure
     * can be turned into a clean exception carrying the real reason. Modelled on Symfony's Filesystem::box().
     *
     * @param mixed ...$args
     *
     * @return mixed
     */
    private static function box(string $function, ...$args)
    {
        self::$lastError = null;

        set_error_handler(static function (int $type, string $message): bool {
            self::$lastError = $message;

            return true;
        });

        try {
            return $function(...$args);
        } finally {
            restore_error_handler();
        }
    }

    private function filterNotExpired(array $cache, DateTimeInterface $now): array
    {
        return array_filter($cache, static function (Entry $item) use ($now): bool {
            return self::isExpired($item, $now) === false;
        });
    }

    private static function isExpired(Entry $entry, DateTimeInterface $now): bool
    {
        $validator = new ExpirationValidator($entry->getDelta());

        return $validator->isExpired($entry->getToken(), $entry->getRequestTimestamp(), $now);
    }
}
