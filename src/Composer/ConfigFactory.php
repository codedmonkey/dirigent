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
        } elseif ($credentials?->getType() === CredentialsType::GitlabDeployToken) {
            $config->merge([
                'config' => [
                    'gitlab-token' => [
                        $registry->getDomain() => [
                            'username' => $credentials->getUsername(),
                            'token' => $credentials->getToken(),
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
