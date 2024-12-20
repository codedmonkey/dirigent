<?php

namespace CodedMonkey\Dirigent\Composer;

use CodedMonkey\Dirigent\Doctrine\Entity\Credentials;
use CodedMonkey\Dirigent\Doctrine\Entity\CredentialsType;
use CodedMonkey\Dirigent\Doctrine\Entity\Registry;
use Composer\Config;
use Composer\Factory;

class ConfigFactory
{
    public static function createForVcsRepository(string $url, ?Credentials $credentials = null): Config
    {
        $config = Factory::createConfig();

        $domain = parse_url($url, PHP_URL_HOST);

        if ('github.com' === $domain && !$credentials && $globalGithubToken = $_SERVER['GITHUB_TOKEN'] ?? null) {
            $config->merge([
                'config' => [
                    'github-oauth' => [
                        $domain => $globalGithubToken,
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

        if (CredentialsType::HttpBasic === $credentials?->getType()) {
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
        } elseif (CredentialsType::GitlabDeployToken === $credentials?->getType()) {
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
        } elseif (CredentialsType::GitlabPersonalAccessToken === $credentials?->getType()) {
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
