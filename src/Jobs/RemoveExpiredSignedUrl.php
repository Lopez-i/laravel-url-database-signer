<?php

namespace lopez_i\Jobs;

use lopez_i\UrlSigner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Class RemoveExpiredSignedUrl
 * @package App\Jobs
 */
class RemoveExpiredSignedUrl implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    protected $user_uuid;
    /**
     * @var int
     */
    protected $request_type;


    /**
     * RemoveExpiredSignedUrl constructor.
     * @param string $user_uuid
     * @param string $request_type
     */
    public function __construct(string $user_uuid, string $request_type)
    {
        $this->user_uuid = $user_uuid;
        $this->request_type = $request_type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        UrlSigner::removeExpiredSignedUrl($this->user_uuid, $this->request_type);
    }
}
