<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @copyright Copyright (c) 2016 Tom H Anderson <tom.h.anderson@gmail.com>
 *     for api-skeletons/zf-oauth2-doctrine-permissions
 */

namespace ZF\OAuth2\Doctrine\Permissions\Acl\Authorization;

use Zend\Permissions\Acl\Acl;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManagerInterface;
use ZF\MvcAuth\Identity\IdentityInterface;
use ZF\MvcAuth\Authorization\AuthorizationInterface;
use ZF\OAuth2\Doctrine\Permissions\Acl\Event;
use ZF\OAuth2\Doctrine\Permissions\Acl\Role\ProviderInterface;
use ZF\OAuth2\Doctrine\Identity\AuthenticatedIdentity;
use GianArb\Angry\Unclonable;
use GianArb\Angry\Unserializable;
use GianArb\Angry\Uninvokable;

class AclAuthorization extends Acl implements AuthorizationInterface
{
    use Unclonable;
    use Unserializable;
    use Uninvokable;

    protected $events;

    public function createEventManager(SharedEventManagerInterface $sharedEventManager)
    {
        $this->events = new EventManager(
            $sharedEventManager,
            [
                Event::class,
            ]
        );

        return $this->events;
    }

    public function getEventManager()
    {
        return $this->events;
    }

    public function isAuthorized(IdentityInterface $identity, $resource, $privilege)
    {
        if (null !== $resource && (! $this->hasResource($resource))) {
            $this->addResource($resource);
        }

        // Allow for authorized override in listener
        $results = $this->getEventManager()->trigger(
            Event::IS_AUTHORIZED,
            $this,
            [
                'identity' => $identity,
                'resource' => $resource,
                'privilege' => $privilege,
            ]
        );
        if ($results->stopped()) {
            return $results->last();
        }

        if ($identity instanceof AuthenticatedIdentity) {
            if ($identity->getUser() instanceof ProviderInterface) {
                foreach ($identity->getUser()->getRole() as $role) {
                    if ($this->isAllowed($role->getRoleId(), $resource, $privilege)) {
                        return true;
                    }
                }
            }
        } else {
            // Guest access
            if (! $this->hasRole($identity)) {
                $this->addRole($identity);
            }

            return $this->isAllowed($identity, $resource, $privilege);
        }

        return false;
    }
}
