<?php

return [

    'import' => [
        // Number of sentences inserted by each import job.
        'chunk_size' => (int) env('SKRIBBLIO_IMPORT_CHUNK_SIZE', 100),
    ],

];
