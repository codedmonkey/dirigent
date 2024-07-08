<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

enum CredentialsType: string
{
    case HttpBasic = 'http-basic';
    case GitlabOauth = 'gitlab-oauth';
    case GitlabToken = 'gitlab-token';
}
