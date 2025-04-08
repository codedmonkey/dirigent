<?php

namespace CodedMonkey\Dirigent\EventListener;

use CodedMonkey\Dirigent\Controller\Dashboard\DashboardRootController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Fakes dashboard routes are created by EasyAdmin to use EasyAdmin template functions.
 */
readonly class DashboardRoutingListener
{
    #[AsEventListener(priority: 10)]
    public function redirectPrettyDashboardUrls(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (str_starts_with($request->attributes->get('_route'), 'dashboard_')) {
            $request->attributes->set(EA::ROUTE_CREATED_BY_EASYADMIN, true);
            $request->attributes->set(EA::DASHBOARD_CONTROLLER_FQCN, DashboardRootController::class);
        }
    }
}
