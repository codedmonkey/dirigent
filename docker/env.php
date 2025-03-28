<?php

return [
    'APP_ENV' => 'prod',
    'DIRIGENT_IMAGE' => '1',
    'SYMFONY_DOTENV_PATH' => './.env.dirigent',

    'DECRYPTION_KEY' => '',
    'DECRYPTION_KEY_FILE' => '/srv/config/secrets/decryption_key',
    'ENCRYPTION_KEY' => '',
    'ENCRYPTION_KEY_FILE' => '/srv/config/secrets/encryption_key',
    'GITHUB_TOKEN' => '',
    'KERNEL_SECRET_FILE' => '/srv/config/secrets/kernel_secret',
    'MAILER_DSN' => 'null://null',
    'SENTRY_DSN' => '',
    'TRUSTED_PROXIES' => '',
];
