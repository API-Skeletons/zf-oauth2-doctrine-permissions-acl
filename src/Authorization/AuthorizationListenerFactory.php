<?php

namespace ZF\OAuth2\Doctrine\Permissions\Acl\Authorization;

use ZF\OAuth2\Doctrine\Permissions\Acl\Role\ObjectRepositoryProvider;
use Interop\Container\ContainerInterface;

class AuthorizationListenerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        $objectManager = $container->get($config['zf-oauth2-doctrine-permissions-acl']['role']['object_manager']);
        $objectRepositoryProvider = new ObjectRepositoryProvider(
            $objectManager->getRepository($config['zf-oauth2-doctrine-permissions-acl']['role']['entity'])
        );

        return new AuthorizationListener($objectRepositoryProvider);
    }
}