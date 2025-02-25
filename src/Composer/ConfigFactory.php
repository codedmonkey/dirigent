<?php

namespace CodedMonkey\Dirigent\Composer;

use CodedMonkey\Dirigent\Doctrine\Entity\Credentials;
use CodedMonkey\Dirigent\Doctrine\Entity\CredentialsType;
use CodedMonkey\Dirigent\Doctrine\Entity\Registry;
use Composer\Config;
use Composer\Factory;

final class ConfigFactory
{
    public static function createForRegistry(Registry $registry): Config
    {
        $config = Factory::createConfig();

        $config->merge([
            'config' => static::buildCredentialsConfig($registry->getDomain(), $registry->getCredentials()),
        ]);

        return $config;
    }

    public static function createForVcsRepository(string $url, ?Credentials $credentials = null): Config
    {
        $config = Factory::createConfig();

        $domain = parse_url($url, PHP_URL_HOST);

        $config->merge([
            'config' => static::buildCredentialsConfig($domain, $credentials),
        ]);

        return $config;
    }

    private static function buildCredentialsConfig(string $domain, ?Credentials $credentials): array
    {
        if (!$credentials && 'github.com' === $domain && $globalGithubToken = $_SERVER['GITHUB_TOKEN'] ?? null) {
            return [
                'github-oauth' => [
                    $domain => $globalGithubToken,
                ],
            ];
        }

        return match ($credentials?->getType()) {
            CredentialsType::HttpBasic => [
                'http-basic' => [
                    $domain => [
                        'username' => $credentials->getUsername(),
                        'password' => $credentials->getPassword(),
                    ],
                ],
            ],
            CredentialsType::GithubOauthToken => [
                'github-oauth' => [
                    $domain => $credentials->getToken(),
                ],
            ],
            CredentialsType::GitlabDeployToken => [
                'gitlab-token' => [
                    $domain => [
                        'username' => $credentials->getUsername(),
                        'token' => $credentials->getToken(),
                    ],
                ],
            ],
            CredentialsType::GitlabPersonalAccessToken => [
                'gitlab-token' => [
                    $domain => [
                        'username' => $credentials->getToken(),
                        'token' => 'private-token',
                    ],
                ],
            ],
            default => [],
        };
    }
}
