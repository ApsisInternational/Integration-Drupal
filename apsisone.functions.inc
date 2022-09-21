<?php

function _apsisone_get_supported_types() {
  $types = [
    //    'node' => 'Node',
    'block' => 'Block',
    'field_config' => 'Field',
  ];
  if (\Drupal::moduleHandler()->moduleExists('paragraphs')) {
    $types['paragraph'] = 'Paragraph';
  }
  return $types;
}


function _apsisone_create_segment($entity_id, $entity_type, $type, $segment, $segmented_field = '', $segmented_field_match = '', $segmented_field_delta = '') {
  $values = [
    'entity_id' => $entity_id,
    'entity_type' => $entity_type,
    'type' => $type,
    'segment' => $segment,
    'segmented_field' => $segmented_field,
    'segmented_field_delta' => $segmented_field_delta,
    'segmented_field_match' => $segmented_field_match,
  ];

  // If segment already exists update it instead of creating a duplicate
  $already_existing_segmentation = \Drupal::entityTypeManager()
    ->getStorage('segmentation')
    ->loadByProperties([
      'entity_id' => $values['entity_id'],
      'segmented_field' => $values['segmented_field'],
    ]);
  if (!empty($already_existing_segmentation)) {
    $segmentation = array_shift($already_existing_segmentation);
    $segmentation->segment->value = $segment;
    $segmentation->segmented_field_match = $segmented_field_match;
  }
  else {
    $segmentation = \Drupal\apsisone\Entity\Segmentation::create($values);
  }

  $segmentation->save();

  return $segmentation;
}


/*
 * Returns apsisone enabled entities based on type
 */
function _apsisone_get_config_entities($type) {
  $apsis_config_entities = \Drupal::entityTypeManager()
    ->getStorage('apsisone_config')
    ->loadMultiple();

  $enabled = [];
  foreach ($apsis_config_entities as $entity) {
    if ($entity->getType() == $type) {
      $enabled[] = $entity->get('bundle_type');
    }
  }

  return $enabled;
}


function _apsisone_get_block_content_info($block_id) {

  // Get block config
  $block_config = Drupal\block\Entity\Block::load($block_id);

  // Get uuid of the block content
  $uuid = str_replace('block_content:', '', $block_config->getPluginId());

  // Get the block content
  $block_content = \Drupal::entityTypeManager()
    ->getStorage('block_content')
    ->loadByProperties(['uuid' => $uuid]);
  $block_content = array_shift($block_content);

  if (empty($block_content)) {
    return FALSE;
  }

  // To finally get the block entity id (int) not machine name and the block custom type(block type from the block config would only return type 'block')
  $block_entity_id = $block_content->id->value;
  $block_type = $block_content->bundle();

  return ['block_entity_id' => $block_entity_id, 'block_type' => $block_type];
}


function _apsisone_get_existing_segments($entity_id, $entity_type, $segment_type, $segmented_field = '') {

  // Check if we have a segment stored for this entity since before
  $existing_segment = 0;
  $existing_segments = [];
  $segment_field_match = 'any';
  if (!empty($entity_id)) {

    if ($segment_type == 'field') {
      $existing_segmentation = \Drupal::entityTypeManager()
        ->getStorage('segmentation')
        ->loadByProperties([
          'entity_id' => $entity_id,
          'entity_type' => $entity_type,
          'segmented_field' => $segmented_field,
          'type' => $segment_type,
        ]);
    }
    else {
      if ($segment_type == 'block') {
        $existing_segmentation = \Drupal::entityTypeManager()
          ->getStorage('segmentation')
          ->loadByProperties([
            'entity_id' => $entity_id,
            'entity_type' => $segment_type,
            'type' => $segment_type,
          ]);
      }
    }

    if (!empty($existing_segmentation)) {
      $segmentation = array_shift($existing_segmentation);
      $existing_segment = unserialize($segmentation->segment->value);
      $segment_field_match = $segmentation->segmented_field_match->value;

      foreach ($existing_segment as $key => $segment) {
        if ($segment != 0 || $segment != '0') {
          $existing_segments[$key] = $segment;
        }
      }
    }
  }

  return [
    'existing' => $existing_segments,
    'field_match' => $segment_field_match,
  ];
}


function _apsisone_profile_belongs_to_segment($existing_segmentation, $apsis, $profile) {

  $segment_hit = FALSE;
  if (!empty($existing_segmentation)) {
    $segmentation = array_shift($existing_segmentation);
    $existing_segment = unserialize($segmentation->segment->value);
  }

  $segmentation_empty = TRUE;
  foreach ($existing_segment as $value) {
    if ($value !== 0) {
      $segmentation_empty = FALSE;
    }
  }

  if ($segmentation_empty) {
    $segment_hit = TRUE;
  }

  if ($existing_segment != '0' && !$segmentation_empty) {
    $evaluate = $apsis->evaluateProfile($existing_segment, $profile);
    $segment_field_match = $segmentation->segmented_field_match->value;
    if (isset($evaluate["success"]["segments"])) {
      if ($segment_field_match == 'any' && in_array(TRUE, $evaluate["success"]["segments"])) {
        $segment_hit = TRUE;
      }
      if ($segment_field_match == 'all' && !in_array(FALSE, $evaluate["success"]["segments"])) {
        $segment_hit = TRUE;
      }
    }
  }

  return $segment_hit;

}
