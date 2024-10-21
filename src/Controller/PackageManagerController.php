<?php

declare(strict_types=1);

namespace Drupal\package_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\package_manager\Entity\Package;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Package Manager routes.
 */
final class PackageManagerController extends ControllerBase {

  /**
   * Handles the subpath for the Package Manager.
   *
   * @param string $subpath
   *   The subpath.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function handleSubpath(string $subpath): Response {
    // Serve the React app.
    $basePath = DRUPAL_ROOT . '/packages/' . $subpath;
    $content = file_get_contents($basePath . '/index.html');

    // Adjust the base path for CSS and JS imports.
    $content = str_replace('<head>', '<head><base href="/packages/' . $subpath . '/">', $content);

    return new Response($content);
  }

  /**
   * Renders the package view with an iframe.
   *
   * @param \Drupal\package_manager\Entity\Package $package
   *   The package entity.
   *
   * @return array
   *   A render array.
   */
  public function view(Package $package) {
    // Get the slug value from the entity.
    $slug = $package->get('slug')->value;

    // Return the render array with the custom template.
    return [
      '#theme' => 'package__package',
      '#slug' => $slug,
    ];
  }

}
