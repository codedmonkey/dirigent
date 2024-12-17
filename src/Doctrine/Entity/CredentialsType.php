<?php

namespace CodedMonkey\Dirigent\Doctrine\Entity;

enum CredentialsType: string
{
    case HttpBasic = 'http-basic';
    case GitlabDeployToken = 'gitlab-dt';
    case GitlabPersonalAccessToken = 'gitlab-pat';
}
