<?php

namespace Drupal\apsisone\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Apsis One Config add and edit forms.
 */
class ApsisoneConfigForm extends EntityForm {

  /**
   * @var \Drupal\apsisone\ApsisoneConfignInterface
   */
  protected $entity;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs an ApsisoneConfigForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $apsisone_config = $this->entity;

    $options = [];
    foreach (_apsisone_get_supported_types() as $type_id => $type_label) {
      $options[$type_id] = $type_label;
    }
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Apsis One Configuration type'),
      '#default_value' => $apsisone_config->getType(),
      '#options' => $options,
      '#required' => TRUE,
      '#limit_validation_errors' => [['type']],
      '#submit' => ['::submitSelectType'],
      '#executes_submit_callback' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxReplaceEntityTypeForm',
        'wrapper' => 'entity-type',
        'method' => 'replace',
      ],
    ];

    $form['entity_type_container'] = [
      '#type' => 'container',
      '#prefix' => '<div id="entity-type">',
      '#suffix' => '</div>',
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $apsisone_config->label(),
      '#description' => $this->t("Label for this Apsis One Configuration."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $apsisone_config->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$apsisone_config->isNew(),
    ];

    // if there is no type yet, stop here.
    if ($type = $this->entity->getType()) {

      $entity_type = $this->entityTypeManager->getDefinition($type);

      // Get node types
      if ($entity_type->hasKey('bundle') && $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type->id())) {
        $bundle_options = [];
        foreach ($bundles as $id => $info) {
          $bundle_options[$id] = $info['label'];
        }
      }
      // Get block types
      else {
        if ($type == 'block' && $bundles = \Drupal::entityTypeManager()
            ->getStorage('block_content_type')
            ->loadMultiple()) {
          $bundle_options = [];
          foreach ($bundles as $id => $info) {
            $bundle_options[$id] = $info->get('label');
          }
        }
        // Get supported fields
        else {
          if ($type == 'field_config') {

            $supported_fieldtypes = [
              'image',
              'link',
              'text',
              'text_long',
              'text_with_summary',
              'string',
              'string_long',
            ];

            $bundle_options = [];
            // Go through all content types on site
            $bundles = \Drupal::entityTypeManager()
              ->getStorage('node_type')
              ->loadMultiple();
            foreach ($bundles as $bundle => $bundle_value) {
              $entity_type_id = 'node';
              // Go through all fields on content type
              foreach (\Drupal::entityManager()
                         ->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
                // Add supported fields to bundle options
                if (!empty($field_definition->getTargetBundle()) && in_array($field_definition->getType(), $supported_fieldtypes) && !isset($bundle_options[$field_name])) {
                  $bundle_options[$field_name] = $field_definition->getLabel() . ' (' . $field_name . ')';
                }
              }
            }

            $bundles_block = \Drupal::entityTypeManager()
              ->getStorage('block_content_type')
              ->loadMultiple();
            foreach ($bundles_block as $bundle => $bundle_value) {
              $entity_type_id = 'block_content';
              // Go through all fields on content type
              foreach (\Drupal::entityManager()
                         ->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
                // Add supported fields to bundle options
                if (!empty($field_definition->getTargetBundle()) && in_array($field_definition->getType(), $supported_fieldtypes) && !isset($bundle_options[$field_name])) {
                  $bundle_options[$field_name] = $field_definition->getLabel() . ' (' . $field_name . ')';
                }
              }
            }
          }
        }
      }

      if (isset($bundle_options)) {
        $form['entity_type_container']['bundle'] = [
          '#title' => $entity_type->getBundleLabel(),
          '#type' => 'radios',
          '#options' => $bundle_options,
          '#description' => $this->t('Check to which types this Apsis One configuration should apply.'),
        ];
        if ($bundle_type = $this->entity->getBundleType()) {
          $form['entity_type_container']['bundle']['#default_value'] = $bundle_type;
        }
      }
    }

    $form['status'] = [
      '#title' => $this->t('Enabled'),
      '#type' => 'checkbox',
      '#default_value' => $this->entity->status(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\apsisone\ApsisoneConfigInterface $entity */
    $entity = parent::buildEntity($form, $form_state);

    \Drupal::logger('apsisone')
      ->debug('<pre>' . print_r($entity->getType(), TRUE) . '</pre>');

    $bundles = _apsisone_get_supported_types();

    $bundle = $form_state->getValue('bundle');
    if (array_key_exists($entity->getType(), $bundles) && !empty($bundle)) {
      $entity->set('bundle_type', $bundle);
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $this->messenger()->addMessage($this->t('Config %label saved.', [
      '%label' => $this->entity->label(),
    ]));

    // @TODO Is this where we are missing param that generates error?
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

  /**
   * Helper function to check whether an Apsis One Config configuration entity
   * exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('apsisone_config')
      ->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * Handles switching the type selector.
   */
  public function ajaxReplaceEntityTypeForm($form, FormStateInterface $form_state) {
    return $form['entity_type_container'];
  }

  /**
   * Handles submit call when alias type is selected.
   */
  public function submitSelectType(array $form, FormStateInterface $form_state) {
    $this->entity = $this->buildEntity($form, $form_state);
    $form_state->setRebuild();
  }

}
