<?php
return array(
    
    'server_info' => array(
        'session_cookie_name' => 'PHPSESSID'
    ),
    
    'client_info' => array(
        'client_id' => 'oic-test-fw-instance-chabruz',
        'redirect_uri' => 'https://virtual',
        
        'authorization_endpoint' => 'https://hroch.cesnet.cz/devel/oic-server/oic/authorize',
        'token_endpoint' => 'https://hroch.cesnet.cz/devel/oic-server/oic/token',
        'user_info_endpoint' => 'https://hroch.cesnet.cz/devel/oic-server/oic/userinfo',
        
        'authentication_info' => array(
            'method' => 'client_secret_basic',
            'params' => array(
                'client_secret' => '8d16667945b25c29949b0541f4d0420c'
            )
        )
    ),
    
    'user_info' => array(
        'username' => 'testuser',
        'password' => 'testpasswd'
    )
);