<?php

namespace App\Jobs;

use App\Managers\SentenceManager;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportSentenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    private $sentence;

    /**
     * @var int
     */
    private $groupId;

    /**
     * @var int
     */
    private $userId;

    /**
     * Create a new job instance.
     *
     * @param string $sentence
     * @param int $groupId
     * @param int $userId
     */
    public function __construct(string $sentence, int $groupId, int $userId)
    {
        $this->sentence = $sentence;
        $this->groupId = $groupId;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $sentenceManager = new SentenceManager();
        $sentenceManager->add($this->sentence, $this->groupId, $this->userId);
    }
}
