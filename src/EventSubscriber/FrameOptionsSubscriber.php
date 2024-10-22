<?php

namespace Drupal\package_manager\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 *
 */
class FrameOptionsSubscriber implements EventSubscriberInterface {

  /**
   * Modifies the X-Frame-Options header for specific routes.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   */
  public function onKernelResponse(ResponseEvent $event) {
    $request = $event->getRequest();
    $route_name = $request->attributes->get('_route');

    // Check if the current route is the one you want to modify.
    if ($route_name === 'package_manager.packages') {
      $response = $event->getResponse();
      $response->headers->remove('X-Frame-Options');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onKernelResponse', -10];
    return $events;
  }

}
