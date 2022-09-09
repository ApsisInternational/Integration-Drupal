<?php

namespace Drupal\apsisone\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\apsisone\SegmentationInterface;

/**
 * Defines the Segmentation entity.
 *
 * @ingroup segmentation
 *
 * id: The unique identifier of this entityType. It follows the pattern
 * 'moduleName_xyz' to avoid naming conflicts.
 *
 * label: Human readable name of the entity type.
 *
 * handlers: Handler classes are used for different tasks. You can use
 * standard handlers provided by D8 or build your own, most probably derived
 * from the standard class. In detail:
 *
 * - view_builder: we use the standard controller to view an instance. It is
 *   called when a route lists an '_entity_view' default for the entityType
 *   (see routing.yml for details. The view can be manipulated by using the
 *   standard drupal tools in the settings.
 *
 * - list_builder: We derive our own list builder class from the
 *   entityListBuilder to control the presentation.
 *   If there is a view available for this entity from the views module, it
 *   overrides the list builder. @todo: any view? naming convention?
 *
 * - form: We derive our own forms to add functionality like additional fields,
 *   redirects etc. These forms are called when the routing list an
 *   '_entity_form' default for the entityType. Depending on the suffix
 *   (.add/.edit/.delete) in the route, the correct form is called.
 *
 * - access: Our own accessController where we determine access rights based on
 *   permissions.
 *
 * More properties:
 *
 *  - base_table: Define the name of the table used to store the data. Make sure
 *    it is unique. The schema is automatically determined from the
 *    BaseFieldDefinitions below. The table is automatically created during
 *    installation.
 *
 *  - fieldable: Can additional fields be added to the entity via the GUI?
 *    Analog to content types.
 *
 *  - entity_keys: How to access the fields. Analog to 'nid' or 'uid'.
 *
 *  - links: Provide links to do standard tasks. The 'edit-form' and
 *    'delete-form' links are added to the list built by the
 *    entityListController. They will show up as action buttons in an additional
 *    column.
 *
 * @ContentEntityType(
 *   id = "segmentation",
 *   label = @Translation("Segmentation"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\apsisone\SegmentationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "segmentation",
 *   admin_permission = "administer apsisone",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/segmentation/list"
 *   },
 *   internal = TRUE,
 * )
 *
 * The 'Segmentation' class defines methods and fields for the segmentation entity.
 *
 * Being derived from the ContentEntityBase class, we can override the methods
 * we want. In our case we want to provide access to the standard fields about
 * creation and changed time stamps.
 *
 * The most important part is the definitions of the field properties for this
 * entity type. These are of the same type as fields added through the GUI, but
 * they can by changed in code. In the definition we can define if the user with
 * the rights privileges can influence the presentation (view, edit) of each
 * field.
 */
class Segmentation extends ContentEntityBase implements SegmentationInterface {

  use EntityChangedTrait; // Implements methods defined by EntityChangedInterface.

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Segmentation entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Segmentation entity.'))
      ->setReadOnly(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('ID of entity segmentation is enabled for.'))
      ->setSetting('unsigned', TRUE)
      ->setReadOnly(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(
        t('Type of entity.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('type'))
      ->setDescription(
        t('Type'))
      ->setReadOnly(TRUE);

    $fields['segment'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Segment'))
      ->setDescription(t('The Apsis One segment entity belongs to.'));

    $fields['segmented_field'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Segmented field'))
      ->setDescription(t('The field the Apsis One segment applies to.'))
      ->setSetting('max_length', 255);

    $fields['segmented_field_delta'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Segmented field delta'))
      ->setDescription(t('The specific multifield the Apsis One segment applies to.'))
      ->setSetting('max_length', 255);

    $fields['segmented_field_match'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Segmented field match'))
      ->setDescription(t('The matching criteria any/all'))
      ->setSetting('max_length', 255);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }
}
