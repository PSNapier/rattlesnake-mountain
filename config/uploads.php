<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum Upload Size
    |--------------------------------------------------------------------------
    |
    | The largest file each kind of account may upload, in kilobytes. These
    | values back every upload path: horse images, breeding foal designs, and
    | avatars, on both the server and the client. Changing a number here is
    | the only edit needed to change the enforced limit.
    |
    | The server's own `upload_max_filesize` must stay at or above the staff
    | figure, otherwise PHP rejects the request before validation runs.
    |
    */

    'max_kilobytes' => [
        'staff' => 10240,  // 10MB
        'player' => 2048,  // 2MB
    ],

];
