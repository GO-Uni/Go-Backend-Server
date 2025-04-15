<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DeleteImageFromS3 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public array $s3Paths;

    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param array $s3Paths
     * @return void
     */
    public function __construct(array $s3Paths)
    {
        $this->s3Paths = $s3Paths;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->s3Paths as $path) {
            try {
                if (Storage::disk('s3')->exists($path)) {
                    Storage::disk('s3')->delete($path);
                    Log::info('File deleted successfully from S3', ['s3Path' => $path]);
                } else {
                    Log::warning('File not found in S3', ['s3Path' => $path]);
                }
            } catch (\Exception $e) {
                Log::error('Error deleting file from S3', ['exception' => $e->getMessage()]);
                $this->fail($e);
            }
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
        Log::error('DeleteImageFromS3 Job Failed', ['exception' => $exception->getMessage()]);
    }
}
