<?php

namespace Drupal\Tests\webform\Unit;

use Drupal\webform\Utility\WebformElementHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests webform element utility.
 *
 * @group WebformUnit
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformElementHelper
 */
class WebformElementHelperTest extends UnitTestCase {

  /**
   * Tests WebformElementHelper::GetIgnoredProperties().
   *
   * @param array $element
   *   The array to run through WebformElementHelper::GetIgnoredProperties().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformElementHelperl::GetIgnoredProperties()
   *
   * @dataProvider providerGetIgnoredProperties
   */
  public function testGetIgnoredProperties(array $element, $expected) {
    $result = WebformElementHelper::getIgnoredProperties($element);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testGetIgnoredProperties().
   *
   * @see testGetIgnoredProperties()
   */
  public function providerGetIgnoredProperties() {
    // Nothing ignored.
    $tests[] = [
      ['#value' => 'text'],
      [],
    ];
    // Ignore #tree.
    $tests[] = [
      ['#tree' => TRUE],
      ['#tree' => '#tree'],
    ];
    // Ignore #tree and #element_validate.
    $tests[] = [
      ['#tree' => TRUE, '#value' => 'text', '#element_validate' => 'some_function'],
      ['#tree' => '#tree', '#element_validate' => '#element_validate'],
    ];
    // Ignore #subelement__tree and #subelement__element_validate.
    $tests[] = [
      ['#subelement__tree' => TRUE, '#value' => 'text', '#subelement__element_validate' => 'some_function'],
      ['#subelement__tree' => '#subelement__tree', '#subelement__element_validate' => '#subelement__element_validate'],
    ];
    return $tests;
  }

  /**
   * Tests WebformElementHelper::RemoveIgnoredProperties().
   *
   * @param array $element
   *   The array to run through WebformElementHelper::RemoveIgnoredProperties().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformElementHelperl::RemoveIgnoredProperties()
   *
   * @dataProvider providerRemoveIgnoredProperties
   */
  public function testRemoveIgnoredProperties(array $element, $expected) {
    $result = WebformElementHelper::removeIgnoredProperties($element);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testRemoveIgnoredProperties().
   *
   * @see testRemoveIgnoredProperties()
   */
  public function providerRemoveIgnoredProperties() {
    // Nothing removed.
    $tests[] = [
      ['#value' => 'text'],
      ['#value' => 'text'],
    ];
    // Remove #tree.
    $tests[] = [
      ['#tree' => TRUE],
      [],
    ];
    // Remove #tree and #element_validate.
    $tests[] = [
      ['#tree' => TRUE, '#value' => 'text', '#element_validate' => 'some_function'],
      ['#value' => 'text'],
    ];
    // Remove #subelement__tree and #subelement__element_validate.
    $tests[] = [
      ['#subelement__tree' => TRUE, '#value' => 'text', '#subelement__element_validate' => 'some_function'],
      ['#value' => 'text'],
    ];
    return $tests;
  }

}
