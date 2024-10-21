<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a package entity type.
 */
interface PackageInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
