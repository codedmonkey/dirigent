<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Entity;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum CredentialsType: string implements TranslatableInterface
{
    case HttpBasic = 'http-basic';
    case GithubOauthToken = 'github-oauth';
    case GitlabDeployToken = 'gitlab-dt';
    case GitlabPersonalAccessToken = 'gitlab-pat';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans("credentials.type.$this->value");
    }
}
