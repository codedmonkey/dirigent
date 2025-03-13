<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

enum CredentialsType: string
{
    case HttpBasic = 'http-basic';
    case GithubOauthToken = 'github-oauth';
    case GitlabDeployToken = 'gitlab-dt';
    case GitlabPersonalAccessToken = 'gitlab-pat';
    case SshKey = 'ssh-key';
}
