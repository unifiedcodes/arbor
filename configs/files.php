<?php

/**
 * File Upload Configuration
 * 
 * ------------------------------------------------------------------------
 * 
 * This configuration array defines parameters for handling file uploads
 * within the application. It controls file size limitations, naming
 * conventions, allowed file types, and thumbnail generation settings.
 * 
 */

return [
    /**
     * Maximum allowed file size in megabytes.
     * Files exceeding this size will be rejected during upload.
     */
    'max_size_mb' => 2,

    /**
     * Defines behavior when a file with the same name already exists:
     * - 'increment': Appends a number to the filename (e.g., image.jpg, image(1).jpg)
     * - Other possible values might include 'overwrite', 'reject', etc.
     */
    'action_on_exist' => 'increment',

    /**
     * Whether uploaded files should be assigned random names.
     * - When true, generates a random filename regardless of the original name
     * - When false, attempts to preserve the original filename (subject to cleaning)
     */
    'random_name' => false,


    'random_name_length' => 25,

    /**
     * Maximum allowed character length for filenames.
     * Names exceeding this length will be truncated.
     */
    'max_name_size' => 200,

    /**
     * Allowed MIME types for uploaded files.
     * Only files matching these types will be accepted.
     * Current configuration only allows specific image formats.
     */
    'mime_types' => [
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/gif',
    ],

    /**
     * Thumbnail generation settings.
     * Defines the dimensions for various thumbnail sizes.
     * Values represent the maximum width in pixels for each size category.
     * Height will be calculated proportionally to maintain aspect ratio.
     */
    'thumbs' => [
        'large' => 1920,
        'medium' => 500,
        'small' => 100,
    ]
];
