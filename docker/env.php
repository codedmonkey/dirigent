<?php

return [
    'APP_ENV' => 'prod',
    'DATABASE_URL' => 'postgresql://dirigent@127.0.0.1:5432/dirigent?serverVersion=16&charset=utf8',
    'DECRYPTION_KEY_FILE' => '/srv/config/secrets/decryption_key',
    'DIRIGENT_IMAGE' => '1',
    'ENCRYPTION_KEY_FILE' => '/srv/config/secrets/encryption_key',
    'GITHUB_TOKEN' => '',
    'KERNEL_SECRET_FILE' => '/srv/config/secrets/kernel_secret',
    'MAILER_DSN' => 'null://null',
    'MESSENGER_TRANSPORT_DSN' => 'doctrine://default?auto_setup=0',
    'SENTRY_DSN' => '',
    'SYMFONY_DOTENV_PATH' => './.env.dirigent',
    'TRUSTED_PROXIES' => '',
];
