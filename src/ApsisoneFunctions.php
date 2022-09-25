<?php

namespace Drupal\apsisone;

/**
 * Apsis One Functions.
 */
class ApsisoneFunctions {

  /**
   * Returns apsisone enabled entities based on type.
   */
  public static function getConfigEntities($type) {
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

  /**
   * Get existing segments for block or node.
   */
  public static function getExistingSegments($entity_id, $entity_type, $segment_type, $segmented_field = '') {

    // Check if we have a segment stored for this entity since before.
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

  /**
   * Check if apsisone profile belongs to segment/s.
   */
  public static function profileBelongsToSegment($existing_segmentation, $apsis, $profile) {
    $segment_hit = FALSE;
    $existing_segment = FALSE;
    if (!empty($existing_segmentation) && isset($existing_segmentation->segment->value)) {
      $existing_segment = unserialize($existing_segmentation->segment->value);
    }

    $segmentation_empty = TRUE;
    foreach ($existing_segment as $key => $value) {
      if ($value !== 0) {
        $segmentation_empty = FALSE;
      }
      else {
        unset($existing_segment[$key]);
      }
    }

    if ($segmentation_empty) {
      $segment_hit = TRUE;
    }

    if ($existing_segment != '0' && !$segmentation_empty) {
      $evaluate = $apsis->evaluateProfile($existing_segment, $profile);
      $segment_field_match = $existing_segmentation->segmented_field_match->value;
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

}
