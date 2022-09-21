<?php

namespace Drupal\apsisone\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\apsisone\ApsisoneConfigInterface;

/**
 * Defines the Apsis One Config entity.
 *
 * @ConfigEntityType(
 *   id = "apsisone_config",
 *   label = @Translation("Apsis One Config"),
 *   handlers = {
 *     "list_builder" = "Drupal\apsisone\Controller\ApsisoneConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\apsisone\Form\ApsisoneConfigForm",
 *       "edit" = "Drupal\apsisone\Form\ApsisoneConfigForm",
 *       "delete" = "Drupal\apsisone\Form\ApsisoneConfigDeleteForm",
 *     }
 *   },
 *   config_prefix = "apsisone_config",
 *   admin_permission = "administer apsisone",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "type",
 *     "bundle_type",
 *   },
 *   lookup_keys = {
 *     "type",
 *     "status",
 *   },
 *   links = {
 *     "collection" = "/admin/config/services/apsisone/apsisone",
 *     "edit-form" = "/admin/config/services/apsisone/apsisone/{apsisone}",
 *     "delete-form" =
 *   "/admin/config/services/apsisone/apsisone/{apsisone}/delete",
 *   }
 * )
 */
class ApsisoneConfig extends ConfigEntityBase implements ApsisoneConfigInterface {

  /**
   * The ApsisoneConfig ID.
   *
   * @var string
   */
  public $id;

  /**
   * The ApsisoneConfig label.
   *
   * @var string
   */
  public $label;

  /**
   * The ApsisoneConfig type.
   *
   * A string denoting the type of Apsis One Configuration this is. For a node
   * this would be 'node', for field it would be 'field', and so on.
   *
   * @var string
   */
  protected $type;

  /**
   * The ApsisoneConfig bundle type.
   *
   * A string denoting the sub type of Apsis One Configuration this is. For node
   * this would be the node type and for field it would be field config type.
   *
   * @var string
   */
  protected $bundle_type;

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleType() {
    return $this->bundle_type;
  }

}
