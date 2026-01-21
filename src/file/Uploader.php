<?php

namespace Arbor\file;

use finfo;
use Exception;
use RuntimeException;
use Arbor\config\ConfigValue;
use Arbor\http\components\UploadedFile;


/**
 * File Uploader Component
 * 
 * Handles secure file uploads with various validation, naming, and storage options.
 * Manages file naming conflicts, size restrictions, and MIME type validation.
 * 
 * @package Arbor\file
 * 
 */
class Uploader
{
    /**
     * Constant to convert megabytes to bytes
     * @var int
     */
    const MB_TO_BYTE = (1024 * 1024);

    /**
     * Directory path where uploaded files will be stored
     * @var string
     */
    protected string $uploads_dir;

    /**
     * Maximum allowed file size in megabytes
     * @var int
     */
    protected int $max_size_mb;

    /**
     * Action to take when a file with the same name already exists
     * Options: 'break', 'duplicate', 'increment'
     * @var string
     */
    protected string $action_on_exist;

    /**
     * Whether to use random names for uploaded files
     * @var bool
     */
    protected bool $random_name;

    /**
     * Maximum allowed length for file names
     * @var int
     */
    protected int $max_name_size;

    /**
     * List of allowed MIME types for uploads
     * @var array<string>
     */
    protected array $mime_types;

    /**
     * Length of random file names (when random naming is enabled)
     * @var int
     */
    protected int $random_name_length;

    /**
     * Maximum number of attempts to generate a unique random file name
     * @var int
     */
    protected int $random_name_max_trials = 50;

    /**
     * Application's root path, used for generating relative paths
     * @var string
     */
    protected string $rootPath;

    /**
     * Initializes the file uploader with configuration settings
     * 
     * @param string $uploads_dir Directory where files will be uploaded
     * @param int $max_size_mb Maximum file size in megabytes
     * @param string $action_on_exist Action when file exists: 'break', 'duplicate', or 'increment'
     * @param bool $random_name Whether to use random names for files
     * @param int $max_name_size Maximum length allowed for file names
     * @param array<string> $mime_types Allowed MIME types for uploading
     * @param int $random_name_length Length for generated random file names
     * @param string $rootPath Application's root directory path
     */
    public function __construct(
        #[ConfigValue('app.uploads_dir')]
        string $uploads_dir,

        #[ConfigValue('files.max_size_mb')]
        int $max_size_mb = 2,

        #[ConfigValue('files.action_on_exist')]
        string $action_on_exist = 'break',

        #[ConfigValue('files.random_name')]
        bool $random_name = false,

        #[ConfigValue('files.max_name_size')]
        int $max_name_size = 100,

        #[ConfigValue('files.mime_types')]
        array $mime_types,

        #[ConfigValue('files.random_name_length')]
        int $random_name_length = 25,

        #[ConfigValue('app.root_dir')]
        string $rootPath
    ) {
        $this->uploads_dir = normalizeDirPath($uploads_dir);
        $this->max_size_mb = $max_size_mb;
        $this->action_on_exist = $action_on_exist;
        $this->random_name = $random_name;
        $this->max_name_size = $max_name_size;
        $this->mime_types = $mime_types;
        $this->random_name_length = $random_name_length;
        $this->rootPath = $rootPath;
    }

    /**
     * Process and upload a file to the configured upload directory
     * 
     * Validates the file, processes its name, and moves it to the destination.
     * 
     * @param UploadedFile $file The uploaded file to process
     * @return array<string, string|int> File metadata including name, path, size, and MIME type
     * @throws Exception If validation fails or upload cannot be completed
     */
    public function upload(UploadedFile $file): array
    {
        // Validate file
        $this->validateFile($file);

        // filter file name
        $file_name = $this->filterFileName($file->getClientFilename());

        // Name file
        $file_name = $this->nameFile($file_name);

        // move
        return $this->moveFile($file, $file_name);
    }

    /**
     * Validates an uploaded file against configured restrictions
     * 
     * Checks file name length, upload status, size limits, and MIME type.
     * 
     * @param UploadedFile $file The file to validate
     * @return void
     * @throws Exception If any validation check fails
     */
    protected function validateFile(UploadedFile $file): void
    {
        // Check if file name is too long
        if (strlen($file->getClientFilename()) > $this->max_name_size) {
            throw new Exception('File name is too big');
        }

        // Check if file was uploaded without errors
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed' . $file->getError());
        }

        // Check max file size
        if ($file->getSize() > $this->max_size_mb * self::MB_TO_BYTE) {
            throw new Exception('Max upload Limit is : ' . $this->max_size_mb . ' MB');
        }

        // finding mime type from the file.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file->getStream()->getMetadata('uri'));


        // if client media type and finfo mimetype doesn't match, assume a spoof upload.
        if ($file->getClientMediaType() !== $mimeType) {
            throw new Exception('File type is not correct');
        }

        // if mimetype doesn't exist in allowed types.
        if (!in_array($mimeType, $this->mime_types)) {
            throw new RuntimeException("Invalid file type.");
        }
    }

    /**
     * Determines the final name for a file, handling name conflicts according to configuration
     * 
     * Can generate random names or handle duplicate filenames based on the configuration.
     * 
     * @param string $file_name Original file name (already filtered)
     * @return string The final file name to use
     * @throws Exception If file exists and action_on_exist is set to 'break' or invalid
     */
    protected function nameFile(string $file_name): string
    {
        if ($this->random_name == true) {
            $file_name = $this->getRandomName($file_name);
        }

        $file_path = $this->uploads_dir . $file_name;

        // early return when file does not exist
        if (!file_exists($file_path)) {
            return $file_name;
        }

        // file exists
        switch ($this->action_on_exist) {
            case 'break':
                throw new Exception('File Already Exist');
                break;

            case 'duplicate':
            case 'increment':
                $file_name = $this->getIncrementedFileName($file_name);
                break;

            default:
                throw new Exception("File Already Exist & Action " . "'$this->action_on_exist'" . " is not defined");
                break;
        }

        return $file_name;
    }

    /**
     * Generates a random unique filename while preserving the original extension
     * 
     * @param string $file_name Original file name
     * @return string A random unique file name
     * @throws RuntimeException If unable to generate a unique name after max trials
     */
    protected function getRandomName(string $file_name): string
    {
        $fileExt = pathinfo($file_name, PATHINFO_EXTENSION);
        $max_trials = $this->random_name_max_trials;

        for ($i = 0; $i < $max_trials; $i++) {
            $randomBase = random_token($this->random_name_length, 'alphaNumeric');
            $newFileName = $randomBase . ($fileExt ? ".$fileExt" : '');

            if (!file_exists($this->uploads_dir . $newFileName)) {
                return $newFileName;
            }
        }

        throw new RuntimeException("Unable to generate unique file name after $max_trials attempts.");
    }

    /**
     * Creates an incremented filename to avoid overwriting existing files
     * 
     * Adds a counter suffix to the base filename when duplicates exist.
     * 
     * @param string $fileName Original file name
     * @return string Incremented file name that doesn't exist in the destination
     */
    protected function getIncrementedFileName(string $fileName): string
    {
        // Extract file info
        $fileInfo = pathinfo($fileName);

        $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
        $baseName = $fileInfo['filename'];

        // Start with original destination path
        $newFileName = $baseName;
        $destination = $this->uploads_dir . $newFileName . $extension;

        // Append counter if file exists
        $counter = 1;
        while (file_exists($destination)) {
            $newFileName = $baseName . '_' . $counter;
            $destination = $this->uploads_dir . $newFileName . $extension;
            $counter++;
        }

        return $newFileName . $extension;
    }

    /**
     * Sanitizes a filename by replacing disallowed characters
     * convert all charachters into lowercase.
     * 
     * @param string $name Original file name
     * @return string Filtered file name with special characters replaced by underscores
     */
    protected function filterFileName(string $name): string
    {
        return strtolower(
            preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $name)
        );
    }

    /**
     * Moves the uploaded file to its final destination and returns metadata
     * 
     * @param UploadedFile $file The uploaded file to move
     * @param string $name The target file name (already processed)
     * @return array<string, string|int> File metadata including name, paths, size, and MIME type
     * @throws RuntimeException If the file cannot be moved
     */
    protected function moveFile(UploadedFile $file, string $name): array
    {
        $upload_dir = ensureDir($this->uploads_dir);

        // building target path.
        $destination = $upload_dir . $name;

        // move file
        $file->moveTo($destination);


        // read the moved file and return meta.
        if (!file_exists($destination)) {
            throw new RuntimeException("File upload failed to move file at '$destination'");
        }

        $name = basename($destination);
        $relativePath = str_replace($this->rootPath, '', $destination);

        return [
            'name' => $name,
            'relative_path' => $relativePath,
            'full_path' => realpath($destination),
            'size' => filesize($destination),
            'mime' => mime_content_type($destination),
        ];
    }
}
