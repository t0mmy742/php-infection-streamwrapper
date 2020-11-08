<?php

declare(strict_types=1);

namespace t0mmy742\StreamWrapper;

use AdrianSuter\Autoload\Override\Override;
use DG\BypassFinals;
use InvalidArgumentException;
use RuntimeException;

use function chgrp;
use function chmod;
use function chown;
use function closedir;
use function fclose;
use function feof;
use function fflush;
use function file_get_contents;
use function flock;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function ftruncate;
use function fwrite;
use function is_resource;
use function is_string;
use function lstat;
use function mkdir;
use function opendir;
use function readdir;
use function rename;
use function rewinddir;
use function rmdir;
use function stat;
use function stream_set_blocking;
use function stream_set_timeout;
use function stream_set_write_buffer;
use function stream_wrapper_register;
use function stream_wrapper_restore;
use function stream_wrapper_unregister;
use function touch;
use function unlink;

// phpcs:disable PSR1.Methods.CamelCapsMethodName
class StreamWrapper
{
    public const LOCATION = __FILE__;
    private const STREAM_OPEN_FOR_INCLUDE = 0x00000080;

    /**
     * @var resource|null
     */
    public $context;

    /**
     * @var resource|null|false
     */
    private $resource;

    private static string $intercept;

    private static string $replacement;

    public static function intercept(string $file, string $with): void
    {
        if (!file_exists($file)) {
            throw new InvalidArgumentException('File to intercept and replace does not exist: ' . $file);
        }

        if (!file_exists($with)) {
            throw new InvalidArgumentException('File to replace intercepted file with does not exist: ' . $file);
        }
        self::$intercept = $file;
        self::$replacement = $with;
    }

    public static function enable(): void
    {
        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);
    }

    public static function disable(): void
    {
        stream_wrapper_restore('file');
    }

    /**
     * Close directory handle.
     *
     * @return bool
     */
    public function dir_closedir(): bool
    {
        self::disable();

        if (is_resource($this->resource)) {
            closedir($this->resource);
        }

        self::enable();

        return true;
    }

    /**
     * Open directory handle.
     *
     * @param string $path
     * @param int $options
     *
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public function dir_opendir(string $path, int $options): bool
    {
        self::disable();

        if (is_resource($this->context)) {
            $this->resource = opendir($path, $this->context);
        } else {
            $this->resource = opendir($path);
        }

        self::enable();

        return is_resource($this->resource);
    }

    /**
     * Read entry from directory handle.
     *
     * @return false|string
     * @noinspection PhpUnused
     */
    public function dir_readdir()
    {
        self::disable();

        $r = false;
        if (is_resource($this->resource)) {
            $r = readdir($this->resource);
        }

        self::enable();

        return $r;
    }

    /**
     * Rewind directory handle.
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function dir_rewinddir(): bool
    {
        self::disable();

        if (is_resource($this->resource)) {
            rewinddir($this->resource);
        }

        self::enable();

        return true;
    }

    /**
     * Create a directory.
     *
     * @param string $path
     * @param int $mode
     * @param int $options
     *
     * @return bool
     */
    public function mkdir(string $path, int $mode, int $options): bool
    {
        self::disable();

        $recursive = (bool) ($options & STREAM_MKDIR_RECURSIVE);
        if (is_resource($this->context)) {
            $r = mkdir($path, $mode, $recursive, $this->context);
        } else {
            $r = mkdir($path, $mode, $recursive);
        }

        self::enable();

        return $r;
    }

    /**
     * Rename a file or directory.
     *
     * @param string $path_from
     * @param string $path_to
     *
     * @return bool
     */
    public function rename(string $path_from, string $path_to): bool
    {
        self::disable();

        if (is_resource($this->context)) {
            $r = rename($path_from, $path_to, $this->context);
        } else {
            $r = rename($path_from, $path_to);
        }

        self::enable();

        return $r;
    }

    /**
     * Remove a directory.
     *
     * @param string $path
     * @param int $options
     *
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public function rmdir(string $path, int $options): bool
    {
        self::disable();

        if (is_resource($this->context)) {
            $r = rmdir($path, $this->context);
        } else {
            $r = rmdir($path);
        }

        self::enable();

        return $r;
    }

    /**
     * Retrieve the underlying resource.
     *
     * @param int $cast_as
     *
     * @return false|resource
     * @noinspection PhpUnusedParameterInspection
     */
    public function stream_cast(int $cast_as)
    {
        if (is_resource($this->resource)) {
            return $this->resource;
        }

        return false;
    }

    /**
     * Close a resource.
     * @noinspection PhpUnused
     */
    public function stream_close(): void
    {
        self::disable();

        if (is_resource($this->resource)) {
            fclose($this->resource);
        }

        self::enable();
    }

    /**
     * Test for end-of-file on a file pointer.
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function stream_eof(): bool
    {
        self::disable();

        $r = false;
        if (is_resource($this->resource)) {
            $r = feof($this->resource);
        }

        self::enable();

        return $r;
    }

    /**
     * Flush the output
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function stream_flush(): bool
    {
        self::disable();

        $r = false;
        if (is_resource($this->resource)) {
            $r = fflush($this->resource);
        }

        self::enable();

        return $r;
    }

    /**
     * Advisory file locking.
     *
     * @param int $operation
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function stream_lock(int $operation): bool
    {
        self::disable();

        $r = false;
        if (is_resource($this->resource)) {
            $r = flock($this->resource, $operation);
        }

        self::enable();

        return $r;
    }

    /**
     * Change stream metadata.
     *
     * @param string $path
     * @param int $option
     * @param mixed $value
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function stream_metadata(string $path, int $option, $value): bool
    {
        self::disable();

        $r = false;
        switch ($option) {
            case STREAM_META_TOUCH:
                if (!isset($value[0]) || is_null($value[0])) {
                    $r = touch($path);
                } else {
                    $r = touch($path, $value[0], $value[1]);
                }
                break;

            case STREAM_META_OWNER_NAME:
            case STREAM_META_OWNER:
                $r = chown($path, $value);
                break;

            case STREAM_META_GROUP_NAME:
            case STREAM_META_GROUP:
                $r = chgrp($path, $value);
                break;

            case STREAM_META_ACCESS:
                $r = chmod($path, $value);
                break;
        }

        self::enable();

        return $r;
    }

    /**
     * Open file or URL.
     *
     * @param string $path
     * @param string $mode
     * @param int $options
     * @param string|null $opened_path
     *
     * @return bool
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        self::disable();

        $usePath = (bool) ($options & STREAM_USE_PATH);
        $including = (bool) ($options & self::STREAM_OPEN_FOR_INCLUDE);

        $functionCallMap = Override::getFunctionCallMap($path);


        if (
            $including
            && isset(self::$intercept)
            && isset(self::$replacement)
            && ($path === self::$intercept || realpath($path) === self::$intercept)
        ) {
            $path = self::$replacement;

            $source = file_get_contents($path, $usePath);
            if (!is_string($source)) {
                throw new RuntimeException(sprintf("File `%s` could not be loaded.", $path));
            }
            if ($functionCallMap !== []) {
                $source = Override::convert($source, $functionCallMap);
            }

            if ($mode === 'rb' && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $source = BypassFinals::removeFinals($source);
            }

            $this->resource = fopen('php://temp', 'w+');
            if (is_resource($this->resource)) {
                fwrite($this->resource, $source);
                fseek($this->resource, 0);
            }

            self::enable();

            return is_resource($this->resource);
        } elseif ($functionCallMap !== []) {
            $source = file_get_contents($path, $usePath);
            if (!is_string($source)) {
                throw new RuntimeException(sprintf("File `%s` could not be loaded.", $path));
            }
            $source = Override::convert($source, $functionCallMap);

            if ($mode === 'rb' && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $source = BypassFinals::removeFinals($source);
            }

            $this->resource = fopen('php://temp', 'w+');
            if (is_resource($this->resource)) {
                fwrite($this->resource, $source);
                fseek($this->resource, 0);
            }
            self::enable();

            return is_resource($this->resource);
        } elseif ($mode === 'rb' && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($path, $usePath, $this->context);
            if ($content === false) {
                return false;
            }
            $modified = BypassFinals::removeFinals($content);
            if ($modified !== $content) {
                $this->resource = fopen('php://temp', 'w+');
                if (is_resource($this->resource)) {
                    fwrite($this->resource, $modified);
                    fseek($this->resource, 0);
                }
                self::enable();
                return is_resource($this->resource);
            }
        }
        if (is_resource($this->context)) {
            $this->resource = fopen($path, $mode, $usePath, $this->context);
        } else {
            $this->resource = fopen($path, $mode, $usePath);
        }

        self::enable();

        return is_resource($this->resource);
    }

    /**
     * Read from stream.
     *
     * @param int $count
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function stream_read(int $count): string
    {
        self::disable();

        $r = false;
        if (is_resource($this->resource)) {
            $r = fread($this->resource, $count);
        }

        self::enable();

        if (!is_string($r)) {
            return '';
        }

        return $r;
    }

    /**
     * Seek to specific location in a stream.
     *
     * @param int $offset
     * @param int $whence
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        self::disable();

        $r = -1;
        if (is_resource($this->resource)) {
            $r = fseek($this->resource, $offset, $whence);
        }

        self::enable();

        return $r < 0 ? false : true;
    }

    /**
     * Change stream options.
     *
     * @param int $option
     * @param int $arg1
     * @param int $arg2
     *
     * @return bool|int
     * @noinspection PhpUnused
     */
    public function stream_set_option(int $option, int $arg1, int $arg2)
    {
        self::disable();

        $r = false;
        switch ($option) {
            case STREAM_OPTION_BLOCKING:
                if (is_resource($this->resource)) {
                    $r = stream_set_blocking($this->resource, (bool) $arg1);
                }
                break;

            case STREAM_OPTION_READ_TIMEOUT:
                if (is_resource($this->resource)) {
                    $r = stream_set_timeout($this->resource, $arg1, $arg2);
                }
                break;

            case STREAM_OPTION_WRITE_BUFFER:
                switch ($arg1) {
                    case STREAM_BUFFER_NONE:
                        if (is_resource($this->resource)) {
                            $r = stream_set_write_buffer($this->resource, 0);
                        }
                        break;

                    case STREAM_BUFFER_FULL:
                        if (is_resource($this->resource)) {
                            $r = stream_set_write_buffer($this->resource, $arg2);
                        }
                        break;
                }
                break;
        }

        self::enable();

        return $r;
    }

    /**
     * Retrieve information about a file resource.
     *
     * @return array<int|string, int>
     * @noinspection PhpUnused
     */
    public function stream_stat(): array
    {
        self::disable();

        $r = [];
        if (is_resource($this->resource)) {
            $r = fstat($this->resource);
            if (!is_array($r)) {
                $r = [];
            }
        }

        self::enable();

        return $r;
    }

    /**
     * Retrieve the current position of a stream.
     *
     * @return int
     * @noinspection PhpUnused
     */
    public function stream_tell(): int
    {
        self::disable();

        $r = -1;
        if (is_resource($this->resource)) {
            $r = ftell($this->resource);
        }

        self::enable();

        return $r !== false ? $r : -1;
    }

    /**
     * Truncate stream.
     *
     * @param int $new_size
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function stream_truncate(int $new_size): bool
    {
        self::disable();

        $r = false;
        if (is_resource($this->resource)) {
            $r = ftruncate($this->resource, $new_size);
        }

        self::enable();

        return $r;
    }

    /**
     * Write to stream.
     *
     * @param string $data
     *
     * @return int|false
     * @noinspection PhpUnused
     */
    public function stream_write(string $data)
    {
        self::disable();

        $r = false;
        if (is_resource($this->resource)) {
            $r = fwrite($this->resource, $data);
        }

        self::enable();

        return $r;
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function unlink(string $path): bool
    {
        self::disable();

        $r = unlink($path);

        self::enable();

        return $r;
    }

    /**
     * Retrieve information about a file.
     *
     * @param string $path
     * @param int $flags
     *
     * @return array<int|string, int>|false
     * @noinspection PhpUnused
     */
    public function url_stat(string $path, int $flags)
    {
        self::disable();

        $urlStatLink = (bool) ($flags & STREAM_URL_STAT_LINK);
        $urlStatQuiet = (bool) ($flags & STREAM_URL_STAT_QUIET);

        if ($urlStatLink) {
            $r = $urlStatQuiet ? @lstat($path) : lstat($path);
        } else {
            $r = $urlStatQuiet ? @stat($path) : stat($path);
        }

        self::enable();

        return $r;
    }
}
