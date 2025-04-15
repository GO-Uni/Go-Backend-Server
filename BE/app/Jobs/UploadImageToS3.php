<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UploadImageToS3 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected $imagePath;
    protected $s3Path;

    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param string $imagePath
     * @param string $s3Path
     * @return void
     */
    public function __construct($imagePath, $s3Path)
    {
        $this->imagePath = $imagePath;
        $this->s3Path = $s3Path;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $localPath = storage_path('app/public/' . $this->imagePath);

        if (file_exists($localPath)) {
            try {
                Storage::disk('s3')->put($this->s3Path, file_get_contents($localPath), 'public');

                unlink($localPath);

                Log::info('File uploaded successfully to S3', ['s3Path' => $this->s3Path]);
            } catch (\Exception $e) {
                Log::error('Failed to upload file to S3', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    's3Path' => $this->s3Path,
                    'localPath' => $localPath
                ]);

                $this->fail($e);
            }
        } else {
            $message = 'File not found: ' . $localPath;
            Log::error($message);
            throw new \Exception($message);
        }
    }

    /**
     * Determine the time to wait before retrying the job.
     *
     * @return array
     */
    public function backoff()
    {
        return [10, 30, 60];
    }

    /**
     * The job failed to process.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        Log::error('UploadImageToS3 Job Failed', ['exception' => $exception->getMessage()]);
    }
}
