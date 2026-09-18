<?php


return [

    
    'mode'              => 'live',

    'client_id'         => 10000158,
    'auth_token'        => '6a741fd5-43e3-41c7-8e49-0cc3ee7ca56a',
    'hmac_secret'       => 'Pa1SOGifRpKk6E8R',

    'service_endpoint'  => 'https://paycorp-dfcc.prod.aws.paycorp.lk/rest/service/proxy',

    'base_url'          => 'https://sipway1.gamer.gd',

    'return_url'        => 'https://sipway1.gamer.gd/callback.php',
    'success_url'       => 'https://sipway1.gamer.gd/payment-success.php',
    'failed_url'        => 'https://sipway1.gamer.gd/payment-failed.php',

    'currency'          => 'LKR',
    'validate_only'     => false,
];