<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Storage;

class GoogleInit {
    public function getClient()
    {
        $client = new \Google_Client();
        $client->setApplicationName('Sipeka');
        $client->setAccessType('offline');
        $client->setAuthConfig(storage_path('private/client_secret.json'));
        $client->setPrompt('select_account consent');
        $client->setRedirectUri(url('/login/google/callback'));
        $client->setIncludeGrantedScopes(true);
        $client->setScopes([
            \Google_Service_PeopleService::USERINFO_PROFILE,
            \Google_Service_Sheets::SPREADSHEETS,
            \Google_Service_Drive::DRIVE
        ]);

        return $client;
    }
}