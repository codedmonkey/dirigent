<?php

namespace CodedMonkey\Dirigent\EventListener;

use CodedMonkey\Dirigent\Controller\Dashboard\DashboardRootController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

readonly class DashboardRoutingListener
{
    /**
     * Imitate that dashboard routes are created by EasyAdmin to use the EasyAdmin context (like template functions).
     */
    #[AsEventListener(priority: 10)]
    public function dashboardContext(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (str_starts_with($request->attributes->get('_route'), 'dashboard_')) {
            $request->attributes->set(EA::DASHBOARD_CONTROLLER_FQCN, DashboardRootController::class);
        }
    }
}
