<?php

namespace Arbor\storage\stores;


use Arbor\stream\contracts\ResourceStreamInterface;
use Arbor\stream\contracts\StreamInterface;
use Arbor\stream\StreamFactory;
use RuntimeException;


class LocalStore implements StoreInterface
{
    public function read(string $path): StreamInterface
    {
        if (!is_file($path)) {
            throw new RuntimeException("File not found: {$path}");
        }

        return StreamFactory::fromFile($path);
    }


    public function write(string $path, StreamInterface $data): void
    {
        $dir = dirname($path);

        ensureDir($dir);

        $target = fopen($path, 'wb');

        if ($target === false) {
            throw new RuntimeException("Unable to open file for writing: {$path}");
        }

        try {
            if ($data instanceof ResourceStreamInterface) {
                stream_copy_to_stream($data->resource(), $target);
                return;
            }

            while (!$data->eof()) {
                $chunk = $data->read(8192);
                if ($chunk !== '') {
                    fwrite($target, $chunk);
                }
            }
        } finally {
            fclose($target);
        }
    }


    public function delete(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }


    public function list(string $path): array
    {
        if (!is_dir($path)) {
            throw new RuntimeException("Not a directory: {$path}");
        }

        $items = scandir($path);
        if ($items === false) {
            throw new RuntimeException("Unable to list directory: {$path}");
        }

        return array_values(
            array_filter($items, fn($i) => $i !== '.' && $i !== '..')
        );
    }


    public function rename(string $from, string $to): void
    {
        $dir = dirname($to);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!rename($from, $to)) {
            throw new RuntimeException("Unable to rename {$from} to {$to}");
        }
    }


    public function exists(string $path): bool
    {
        return file_exists($path);
    }


    public function stats(string $path): array
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Path not found: {$path}");
        }

        $stat = stat($path);

        if ($stat === false) {
            throw new RuntimeException("Unable to read stats for: {$path}");
        }

        $isFile = is_file($path);

        $mime = null;

        if ($isFile) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $path) ?: null;
            }
        }

        return [
            'path'        => $path,
            'type'        => $isFile ? 'file' : 'directory',
            'size'        => $isFile ? filesize($path) : 0,
            'mime'        => $mime,
            'modified'    => $stat['mtime'],
            'created'     => $stat['ctime'],
            'accessed'    => $stat['atime'],
            'permissions' => substr(sprintf('%o', fileperms($path)), -4),
            'inode'       => $stat['ino'],
        ];
    }
}
