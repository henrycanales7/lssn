<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;

/**
 * Provides a 'processed_text' element.
 *
 * @YamlFormElement(
 *   id = "processed_text",
 *   label = @Translation("Processed text"),
 *   category = @Translation("Markup elements"),
 *   states_wrapper = TRUE,
 * )
 */
class ProcessedText extends YamlFormMarkupBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Markup settings.
      'text' => '',
      'format' => filter_default_format(\Drupal::currentUser()),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), ['text']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildText(array &$element, $value, array $options = []) {
    // Copy to element so that we can render it without altering the actual
    // $element.
    $render_element = $element;
    $html = (string) \Drupal::service('renderer')->renderPlain($render_element);
    $element['#markup'] = MailFormatHelper::htmlToText($html);

    // Must remove #type, #text, and #format.
    unset($element['#type'], $element['#text'], $element['#format']);

    return parent::buildText($element, $value, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Issue #2741877 Nested modals don't work: when using CKEditor in a
    // modal, then clicking the image button opens another modal,
    // which closes the original modal.
    // @todo Remove the below workaround once this issue is resolved.
    if (!$form_state->getUserInput() && \Drupal::currentUser()->hasPermission('administer yamlform')) {
      drupal_set_message($this->t('Processed text element can not be opened within a modal. Please see <a href="https://www.drupal.org/node/2741877">Issue #2741877: Nested modals don\'t work</a>.'), 'warning');
    }
    $form = parent::form($form, $form_state);

    // Remove 'Submission display' since the 'format' property is handled by
    // the text format element.
    unset($form['display']);

    $form['markup']['#title'] = $this->t('Processed text settings');
    $form['markup']['text'] = [
      '#type' => 'text_format',
      '#format' => '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function setConfigurationFormDefaultValue(array &$form, array &$properties, array &$property_element, $property_name) {
    // Apply element.format to the text (text_format) element and unset it.
    if ($property_name == 'text') {
      $property_element['#format'] = $properties['format'];
      unset($properties['format']);
    }

    parent::setConfigurationFormDefaultValue($form, $properties, $property_element, $property_name);
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigurationFormProperty(array &$properties, $property_name, $property_value, array $element) {
    if ($property_name == 'text') {
      $properties['text'] = $property_value['value'];
      $properties['format'] = $property_value['format'];
    }
    else {
      parent::getConfigurationFormProperty($properties, $property_name, $property_value, $element);
    }
  }

}
