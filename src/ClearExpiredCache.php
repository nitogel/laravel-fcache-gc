<?php

namespace Nitogel\LaravelFileCacheGarbageCollector;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\InteractsWithTime;
use SplFileInfo;
use SplFileObject;

/**
 * Class ClearExpiredCache
 * @package Nitogel\LaravelFileCacheGarbageCollector
 *
 * If your OS has limit on open files try:
 * $ ulimit -n 10000
 */
class ClearExpiredCache extends Command
{
    use InteractsWithTime;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:gc {--d|dir} {--i|interactive}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear file cache expired files';

    /** @var Filesystem */
    protected $files;

    /** @var int */
    protected $counter = 0;

    /** @var bool */
    protected $dir = false;

    /** @var bool */
    protected $interactive = false;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cacheDir = config('cache.stores.file.path');

        $this->dir         = $this->option('dir', false);
        $this->interactive = $this->option('interactive', false);

        $this->checkDir($cacheDir);

        if ($this->interactive) {
            $this->info('Total checked files:' . $this->counter);
        }

        return true;
    }

    /**
     * @param array $directories
     */
    public function dirLoop(array $directories)
    {
        foreach ($directories as $directory) {
            $this->checkDir($directory);
            unset($directory);
        }
    }

    /**
     * @param $path string
     */
    public function checkDir(string $path)
    {
        $files = $this->files->files($path);

        $this->fileLoop($files);
        unset($files);

        $folders = $this->files->directories($path);

        $this->dirLoop($folders);

        $files = $this->files->files($path);

        if (empty($files) && $this->dir) {

            if (!$this->interactive) {
                $this->files->deleteDirectory($path);

            } elseif ($this->interactive && $this->confirm('Delete ' . $path)) {
                $this->files->deleteDirectory($path);
            }
        }

        unset($folders);
    }

    /**
     * @param array $files
     */
    public function fileLoop(array $files)
    {
        foreach ($files as $file) {
            $this->checkFile($file);
        }
    }

    /**
     * @param $filePath
     * @return void
     */
    public function checkFile($filePath)
    {
        if ($this->shouldIgnored($filePath)) {
            return;
        }

        $expired = $this->isExpired($filePath);

        if ($expired) {
            if ($this->interactive && !$this->confirm('Delete ' . $filePath)) {
                return;
            }

            $this->files->delete($filePath);
        }

        return;
    }

    /**
     * @param $fileName
     * @return bool
     */
    public function shouldIgnored($fileName): bool
    {
        if (strpos($fileName, '/') !== false) {
            $fileName = basename($fileName);
        }

        $ignored = [
            '.gitignore'
        ];

        return in_array($fileName, $ignored);
    }

    /**
     * @param SplFileInfo $file
     * @return bool
     */
    public function isExpired(SplFileInfo $file): bool
    {
        $this->counter++;
        try {
            /** @var SplFileObject $f */
            $f    = $file->openFile();
            $line = $f->getCurrentLine();
            unset($f);

            $expire = substr($line, 0, 10);
        } catch (Exception $e) {
            $this->warning($e->getMessage());
            return false;
        }

        if ($this->currentTime() >= $expire) {
            return true;
        }

        return false;
    }
}
