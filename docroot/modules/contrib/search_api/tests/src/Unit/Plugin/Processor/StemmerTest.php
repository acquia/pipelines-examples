<?php

namespace Drupal\Tests\search_api\Unit\Plugin\Processor;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\Field;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\search_api\data_type\value\TextValue;
use Drupal\search_api\Plugin\search_api\processor\Stemmer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "Stemmer" processor.
 *
 * @coversDefaultClass \Drupal\search_api\Plugin\search_api\processor\Stemmer
 *
 * @group search_api
 */
class StemmerTest extends UnitTestCase {

  use ProcessorTestTrait;
  use TestItemsTrait;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpMockContainer();

    $this->processor = new Stemmer([], 'string', []);
  }

  /**
   * Tests the preprocessIndexItems() method.
   *
   * @covers ::preprocessIndexItems
   */
  public function testPreprocessIndexItems() {
    $index = $this->getMock(IndexInterface::class);

    $item_en = $this->getMockBuilder(ItemInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $item_en->method('getLanguage')->willReturn('en');
    $field_en = new Field($index, 'foo');
    $field_en->setType('text');
    $field_en->setValues([
      new TextValue('ties'),
    ]);
    $item_en->method('getFields')->willReturn(['foo' => $field_en]);

    $item_de = $this->getMockBuilder(ItemInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $item_de->method('getLanguage')->willReturn('de');
    $field_de = new Field($index, 'foo');
    $field_de->setType('text');
    $field_de->setValues([
      new TextValue('ties'),
    ]);
    $item_de->method('getFields')->willReturn(['foo' => $field_de]);

    $items = [$item_en, $item_de];
    $this->processor->preprocessIndexItems($items);

    /** @var \Drupal\search_api\Plugin\search_api\data_type\value\TextValueInterface $value */
    $value = $field_en->getValues()[0];
    $this->assertEquals('tie', $value->toText());
    $value = $field_de->getValues()[0];
    $this->assertEquals('ties', $value->toText());
  }

  /**
   * Tests the process() method.
   *
   * @param string $passed_value
   *   The value that should be passed into process().
   * @param string $expected_value
   *   The expected processed value.
   *
   * @covers ::process
   *
   * @dataProvider processDataProvider
   */
  public function testProcess($passed_value, $expected_value) {
    $this->invokeMethod('process', [&$passed_value]);
    $this->assertEquals($passed_value, $expected_value);
  }

  /**
   * Provides sets of arguments for testProcess().
   *
   * @return array[]
   *   Arrays of arguments for testProcess().
   */
  public function processDataProvider() {
    return [
      ['Yo', 'yo'],
      ['ties', 'tie'],
      ['cries', 'cri'],
      ['exceed', 'exceed'],
      ['consign', 'consign'],
      ['consigned', 'consign'],
      ['consigning', 'consign'],
      ['consignment', 'consign'],
      ['consist', 'consist'],
      ['consisted', 'consist'],
      ['consistency', 'consist'],
      ['consistent', 'consist'],
      ['consistently', 'consist'],
      ['consisting', 'consist'],
      ['consists', 'consist'],
      ['consolation', 'consol'],
      ['consolations', 'consol'],
      ['consolatory', 'consolatori'],
      ['console', 'consol'],
      ['consoled', 'consol'],
      ['consoles', 'consol'],
      ['consolidate', 'consolid'],
      ['consolidated', 'consolid'],
      ['consolidating', 'consolid'],
      ['consoling', 'consol'],
      ['consolingly', 'consol'],
      ['consols', 'consol'],
      ['consonant', 'conson'],
      ['consort', 'consort'],
      ['consorted', 'consort'],
      ['consorting', 'consort'],
      ['conspicuous', 'conspicu'],
      ['conspicuously', 'conspicu'],
      ['conspiracy', 'conspiraci'],
      ['conspirator', 'conspir'],
      ['conspirators', 'conspir'],
      ['conspire', 'conspir'],
      ['conspired', 'conspir'],
      ['conspiring', 'conspir'],
      ['constable', 'constabl'],
      ['constables', 'constabl'],
      ['constance', 'constanc'],
      ['constancy', 'constanc'],
      ['constant', 'constant'],
      ['knack', 'knack'],
      ['knackeries', 'knackeri'],
      ['knacks', 'knack'],
      ['knag', 'knag'],
      ['knave', 'knave'],
      ['knaves', 'knave'],
      ['knavish', 'knavish'],
      ['kneaded', 'knead'],
      ['kneading', 'knead'],
      ['knee', 'knee'],
      ['kneel', 'kneel'],
      ['kneeled', 'kneel'],
      ['kneeling', 'kneel'],
      ['kneels', 'kneel'],
      ['knees', 'knee'],
      ['knell', 'knell'],
      ['knelt', 'knelt'],
      ['knew', 'knew'],
      ['knick', 'knick'],
      ['knif', 'knif'],
      ['knife', 'knife'],
      ['knight', 'knight'],
      ['knightly', 'knight'],
      ['knights', 'knight'],
      ['knit', 'knit'],
      ['knits', 'knit'],
      ['knitted', 'knit'],
      ['knitting', 'knit'],
      ['knives', 'knive'],
      ['knob', 'knob'],
      ['knobs', 'knob'],
      ['knock', 'knock'],
      ['knocked', 'knock'],
      ['knocker', 'knocker'],
      ['knockers', 'knocker'],
      ['knocking', 'knock'],
      ['knocks', 'knock'],
      ['knopp', 'knopp'],
      ['knot', 'knot'],
      ['knots', 'knot'],
      // This can happen when Tokenizer is off during indexing, or when
      // preprocessing a search query with quoted keywords.
      [" \tExtra  spaces \rappeared \n", 'extra space appear'],
      ["\tspaced-out  \r\n", 'space out'],
    ];
  }

}
