<?php

namespace Arbor\files;

use Arbor\files\FileRecord;
use Arbor\files\ingress\FileContext;
use Arbor\facades\Storage;
use Arbor\files\ingress\Payload;
use Arbor\storage\Uri;
use LogicException;


final class FileHydrator
{
    public static function contextFromUri(string|Uri $uri): FileContext
    {
        $uri = Storage::normalizeUri($uri);

        $fileStats = Storage::stats($uri);
        $absolutePath = Storage::absolutePath($uri);

        if ($fileStats->type !== 'file') {
            throw new LogicException(
                "Uri '{$uri}' is not a valid file."
            );
        }

        $payload = new Payload(
            path: $absolutePath,
            name: $fileStats->name,
            mime: $fileStats->mime,
            size: $fileStats->size,
            extension: $fileStats->extension,
            stream: null,
            error: null,
            moved: true,
        );

        return FileContext::fromProvenPayload(
            payload: $payload,
            mime: $fileStats->mime,
            extension: $fileStats->extension,
            size: $fileStats->size,
            binary: $fileStats->binary,
            name: $fileStats->name
        );
    }

    public static function recordFromUri(string|Uri $uri): FileRecord
    {
        $uri = Storage::normalizeUri($uri);
        $fileStats = Storage::stats($uri);

        if ($fileStats->type !== 'file') {
            throw new LogicException(
                "Uri '{$uri}' is not a valid file."
            );
        }

        return new FileRecord(
            uri: (string) $uri,
            mime: $fileStats->mime,
            extension: $fileStats->extension,
            size: $fileStats->size,
            name: $fileStats->name,
            binary: $fileStats->binary
        );
    }


    public static function contextFromRecord(FileRecord $record): FileContext
    {
        $absolutePath = Storage::absolutePath($record->uri);

        $payload = new Payload(
            name: $record->name,
            mime: $record->mime,
            size: $record->size,
            path: $absolutePath,
            stream: null,
            error: null,
            moved: true,
            extension: $record->extension,
        );

        return FileContext::fromProvenPayload(
            payload: $payload,
            mime: $record->mime,
            extension: $record->extension,
            size: $record->size,
            binary: $record->binary,
            name: $record->name
        );
    }
}
