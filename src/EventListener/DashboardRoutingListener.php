<?php

/**
 * Copyright Onlinq B.V.
 *
 * You're not permitted to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell this code.
 */

namespace CodedMonkey\Conductor\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Redirects requests to Symfony routes of dashboard to EasyAdmin routes
 */
readonly class DashboardRoutingListener
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    #[AsEventListener]
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (str_starts_with($request->getPathInfo(), '/dashboard/')) {
            $url = $this->adminUrlGenerator->setRoute($request->attributes->get('_route'), $request->attributes->get('_route_params'))->generateUrl();
            $response = new RedirectResponse($url, Response::HTTP_MOVED_PERMANENTLY);

            $event->setResponse($response);
        }
    }
}
