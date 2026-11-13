<?php

namespace App\Jobs;

use App\Managers\SentenceManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportSentencesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param array<int, string> $sentences
     */
    public function __construct(
        private array $sentences,
        private int $groupId,
        private int $userId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(SentenceManager $sentenceManager): void
    {
        $sentenceManager->addMany($this->sentences, $this->groupId, $this->userId);
    }
}
