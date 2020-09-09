<?php

namespace App\Services;

class GoogleSheet
{

    private $spreedSheetID;
    private $client;
    private $googleSheetService;

    public function __construct(Type $var = null)
    {
        $this->client = new Google_Client();
        
    }
}