<?php

return [

    /*
    |--------------------------------------------------------------------------
    | FreePBX URL
    |--------------------------------------------------------------------------
    |
    | The base URL of your FreePBX installation, including the port if needed.
    | Example: http://192.168.1.100:83 or https://pbx.example.com
    |
    */

    'url' => env('FREEPBX_URL', 'https://your-pbx.local'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Client ID
    |--------------------------------------------------------------------------
    |
    | The OAuth client ID for API authentication. You can create API
    | credentials in FreePBX under Admin > API > Applications.
    |
    */

    'client_id' => env('FREEPBX_CLIENT_ID'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Client Secret
    |--------------------------------------------------------------------------
    |
    | The OAuth client secret for API authentication.
    |
    */

    'client_secret' => env('FREEPBX_CLIENT_SECRET'),

];
