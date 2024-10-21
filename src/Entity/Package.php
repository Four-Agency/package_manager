<?php

declare(strict_types=1);

namespace Drupal\package_manager\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\package_manager\PackageInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the package entity class.
 *
 * @ContentEntityType(
 *   id = "package",
 *   label = @Translation("Package"),
 *   label_collection = @Translation("Packages"),
 *   label_singular = @Translation("package"),
 *   label_plural = @Translation("packages"),
 *   label_count = @PluralTranslation(
 *     singular = "@count packages",
 *     plural = "@count packages",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\package_manager\PackageListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\package_manager\PackageAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\package_manager\Form\PackageForm",
 *       "edit" = "Drupal\package_manager\Form\PackageForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "package",
 *   admin_permission = "administer package",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/package",
 *     "add-form" = "/package/add",
 *     "canonical" = "/package/{package}",
 *     "edit-form" = "/package/{package}/edit",
 *     "delete-form" = "/package/{package}/delete",
 *     "delete-multiple-form" = "/admin/content/package/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.package.settings",
 * )
 */
final class Package extends ContentEntityBase implements PackageInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }

    // Generate a slug from the label with prefix of /packages/.
    $this->set('slug', '/packages/' . \Drupal::service('pathauto.alias_cleaner')->cleanString($this->get('label')->value));
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['file'] = BaseFieldDefinition::create('file')
      ->setLabel(t('Zip File'))
      ->setRequired(TRUE)
      ->setSettings([
        'file_extensions' => 'zip',
      ])
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'file',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Add boolean toggle field to store whether the package is landscape only.
    $fields['landscape_only'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Landscape Only'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', 'Landscape Only')
      ->setDescription(t('If you package only supports landscape mode, enable this option.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => 5,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Add a field to store a slug which will be generated
    // automatically from the label.
    $fields['slug'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Slug'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setSetting('text_processing', 0)
      ->setSetting('display_description', t('The URL alias for this package.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Published')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the package was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the package was last edited.'));

    return $fields;
  }

  /**
   * Extracts the zip file to the /packages directory.
   */
  public function extractZip() {
    $file = File::load($this->get('file')->target_id);
    $zip_path = $file->getFileUri();
    $destination = DRUPAL_ROOT . $this->get('slug')->value;

    // Ensure the destination directory exists.
    \Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);

    // Extract the zip file.
    $zip = new \ZipArchive();
    if ($zip->open(\Drupal::service('file_system')->realpath($zip_path)) === TRUE) {
      $zip->extractTo($destination);
      $zip->close();
    }
  }

}
