<?php

/**
 * @file
 * Contains \Drupal\computed_field\Plugin\Field\FieldType\ComputedDecimalItem.
 */

namespace Drupal\computed_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'computed_float' field type.
 *
 * @FieldType(
 *   id = "computed_float",
 *   label = @Translation("Computed (float)"),
 *   description = @Translation("This field defines a float field whose value is computed by PHP-Code"),
 *   category = @Translation("Computed"),
 *   default_widget = "computed_number_widget",
 *   default_formatter = "computed_decimal"
 * )
 */
class ComputedFloatItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'prefix' => '',
      'suffix' => '',
      'code' => '$value = 0;',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('float')
      ->setLabel(new TranslatableMarkup('Float'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();
    $schema = [
      'columns' => [
        'value' => [
          'type' => 'float',
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    /**
     * @Todo: add useful code
     */
    $values['value'] = 0;
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $settings = $this->getSettings();

    $element['prefix'] = [
      '#type' => 'textfield',
      '#title' => t('Prefix'),
      '#default_value' => $settings['prefix'],
      '#size' => 60,
      '#description' => t("Define a string that should be prefixed to the value, like '$ ' or '&euro; '. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
    ];
    $element['suffix'] = [
      '#type' => 'textfield',
      '#title' => t('Suffix'),
      '#default_value' => $settings['suffix'],
      '#size' => 60,
      '#description' => t("Define a string that should be suffixed to the value, like ' m', ' kb/s'. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
    ];

    $element['code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Code (PHP) to compute the <em>float</em> value'),
      '#default_value' => $settings['code'],
      '#required' => TRUE,
      '#description' =>
        t('The variables available to your code include:
<ul>
<li><code>$value</code>: the resulting value (to be set in this code),</li>
<li><code>$fields</code>: the list of fields available in this entity,</li>
<li><code>$entity</code>: the entity the field belongs to,</li>
<li><code>$entity_manager</code>: the entity manager service (<em>deprecated!</em>),</li>
<li><code>$entity_type_manager</code>: the entity type manager,</li>
<li><code>$delta</code>: current index of the field in case of multi-value computed fields (counting from 0).</li>
</ul>')
        . '<p>'
        . t('Here\'s a simple example using the <code>$entity</code>-array which sets the computed field\'s value to the value of the sum of the number fields (<code>field_a</code> and <code>field_b</code>) in an entity:')
        . '<ul><li><code>$value = $entity->field_a->value + $entity->field_b->value;</code></li></ul>'
        . '<p>'
        . t('An alternative example using the <code>$fields</code>-array:')
        . '<ul><li><code>$value = $fields[\'field_a\'][0][\'value\'] + $fields[\'field_b\'][0][\'value\'];</code></li></ul>'
        . '</p>'
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $value = $this->executeCode();
    $this->setValue($value);
  }

  /**
   * This function does the computation of the field value.
   * @return float
   */
  public function executeCode() {
    $settings = $this->getSettings();
    $code = $settings['code'];
    $entity_manager = \Drupal::EntityManager();
    $entity_type_manager = \Drupal::EntityTypeManager();
    $entity = $this->getEntity();
    $fields = $entity->toArray();
    $delta = $this->name; // indeed!
    $value = NULL;

    eval($code);
    return $value;
  }

}
