<?php

namespace CodedMonkey\Conductor\Composer;

use CodedMonkey\Conductor\Doctrine\Entity\Credentials;
use CodedMonkey\Conductor\Doctrine\Entity\CredentialsType;
use CodedMonkey\Conductor\Doctrine\Entity\Registry;
use Composer\Config;
use Composer\Factory;

class ConfigFactory
{
    public static function createForVcsRepository(string $url, ?Credentials $credentials = null): Config
    {
        $config = Factory::createConfig();

        if ($credentials?->getType() === CredentialsType::GitlabOauth) {
            $config->merge([
                'config' => [
                    'gitlab-oauth' => [
                        parse_url($url, PHP_URL_HOST) => [
                            'token' => $credentials->getPassword(),
                        ],
                    ],
                ],
            ]);
        }

        return $config;
    }

    public static function createForRegistry(Registry $registry): Config
    {
        $config = Factory::createConfig();

        $credentials = $registry->getCredentials();

        if ($credentials?->getType() === CredentialsType::HttpBasic) {
            $config->merge([
                'config' => [
                    'http-basic' => [
                        $registry->getDomain() => [
                            'username' => $credentials->getUsername(),
                            'password' => $credentials->getPassword(),
                        ],
                    ],
                ],
            ]);
        } elseif ($credentials?->getType() === CredentialsType::GitlabOauth) {
            $config->merge([
                'config' => [
                    'gitlab-oauth' => [
                        $registry->getDomain() => [
                            'token' => $credentials->getPassword(),
                        ],
                    ],
                ],
            ]);
        } elseif ($credentials?->getType() === CredentialsType::GitlabPersonalAccessToken) {
            $config->merge([
                'config' => [
                    'gitlab-token' => [
                        $registry->getDomain() => [
                            'username' => $credentials->getToken(),
                            'token' => 'private-token',
                        ],
                    ],
                ],
            ]);
        }

        return $config;
    }
}
