<?php

namespace Drupal\webform;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for webform element plugins.
 *
 * @see hook_webform_element_info_alter()
 * @see \Drupal\webform\Annotation\WebformElement
 * @see \Drupal\webform\WebformElementInterface
 * @see \Drupal\webform\WebformElementBase
 * @see plugin_api
 */
class WebformElementManager extends DefaultPluginManager implements FallbackPluginManagerInterface, WebformElementManagerInterface {

  use CategorizingPluginManagerTrait;

  /**
   * List of already instantiated webform element plugins.
   *
   * @var array
   */
  protected $instances = [];

  /**
   * Constructs a new WebformElementManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/WebformElement', $namespaces, $module_handler, 'Drupal\webform\WebformElementInterface', 'Drupal\webform\Annotation\WebformElement');

    $this->alterInfo('webform_element_info');
    $this->setCacheBackend($cache_backend, 'webform_element_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'webform_element';
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    // If configuration is empty create a single reusable instance for each
    // Webform element plugin.
    if (empty($configuration)) {
      if (!isset($this->instances[$plugin_id])) {
        $this->instances[$plugin_id] = parent::createInstance($plugin_id, $configuration);
      }
      return $this->instances[$plugin_id];
    }
    else {
      return parent::createInstance($plugin_id, $configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getInstances() {
    $plugin_definitions = $this->getDefinitions();
    $plugin_definitions = $this->getSortedDefinitions($plugin_definitions);

    // If all the plugin definitions are initialize returned the cached
    // instances.
    if (count($plugin_definitions) == count($this->instances)) {
      return $this->instances;
    }

    // Initialize and return all plugin instances.
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $this->createInstance($plugin_id);
    }

    return $this->instances;
  }

  /**
   * {@inheritdoc}
   */
  public function invokeMethod($method, array &$element, &$context1 = NULL, &$context2 = NULL) {
    // Make sure element has a #type.
    if (!isset($element['#type'])) {
      return NULL;
    }

    $plugin_id = $this->getElementPluginId($element);

    /** @var \Drupal\webform\WebformElementInterface $webform_element */
    $webform_element = $this->createInstance($plugin_id);
    return $webform_element->$method($element, $context1, $context2);
  }

  /**
   * {@inheritdoc}
   */
  public function getElementPluginId(array $element) {
    if (isset($element['#type'])) {
      if ($this->hasDefinition($element['#type'])) {
        return $element['#type'];
      }
      elseif ($this->hasDefinition('webform_' . $element['#type'])) {
        return 'webform_' . $element['#type'];
      }
    }
    elseif (isset($element['#markup'])) {
      return 'webform_markup';
    }

    return $this->getFallbackPluginId(NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getElementInstance(array $element) {
    $plugin_id = $this->getElementPluginId($element);
    return $this->createInstance($plugin_id, $element);
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions(array $definitions = NULL, $sort_by = 'label') {
    $definitions = isset($definitions) ? $definitions : $this->getDefinitions();

    switch ($sort_by) {
      case 'category':
        uasort($definitions, function ($a, $b) use ($sort_by) {
          return strnatcasecmp($a['category'] . '-' . $a[$sort_by], $b['category'] . '-' . $b[$sort_by]);
        });
        break;

      default:
        uasort($definitions, function ($a, $b) use ($sort_by) {
          return strnatcasecmp($a[$sort_by], $b[$sort_by]);
        });
        break;
    }

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    $properties = [];
    $webform_elements = $this->getInstances();
    foreach ($webform_elements as $webform_element) {
      $translatable_properties = $webform_element->getTranslatableProperties();
      $properties += array_combine($translatable_properties, $translatable_properties);
    }
    ksort($properties);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllProperties() {
    $properties = [];
    $webform_elements = $this->getInstances();
    foreach ($webform_elements as $webform_element) {
      $default_properties = array_keys($webform_element->getDefaultProperties());
      $properties += array_combine($default_properties, $default_properties);
    }
    ksort($properties);
    return $properties;
  }

}
