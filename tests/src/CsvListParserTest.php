<?php

namespace CsvTools\Tests;

use CsvTools\CsvListParser;

/**
 * Tests the CsvListParser.
 *
 * @group CsvTools
 */
class CsvListParserTest extends \PHPUnit_Framework_TestCase {

  public function testSetSettings() {
    $parser = new CsvListParser();
    $parser->setSetting('fake_value', 'value');
    $this->assertEquals($parser->getSetting('fake_value'), 'value');
  }

  public function testUnsetSettings() {
    $parser = new CsvListParser();
    $parser->setSetting('header_map', ['new header' => 'mapped header']);
    $this->assertEquals($parser->getSetting('header_map'), ['new header' => 'mapped header']);
    $parser->unsetSetting('header_map');
    $this->assertNull($parser->getSetting('header_map'));
    // Test with a default value
    $parser->setSetting('delimiter', '#');
    $this->assertEquals($parser->getSetting('delimiter'), '#');
    $parser->unsetSetting('delimiter');
    $this->assertEquals($parser->getSetting('delimiter'), ',');
  }

  public function testDefaultSettings() {
    $parser = new CsvListParser();
    $this->assertEquals($parser->getSetting('delimiter'), ',');
    $this->assertEquals($parser->getSetting('quote'), '"');
    $this->assertEquals($parser->getSetting('separator_index'), '|');
    $this->assertEquals($parser->getSetting('index_by'), [0 => '']);
    $this->assertEquals($parser->getSetting('on_collision'), CsvListParser::ON_COLLISION_ABORT);
    $this->assertEquals($parser->getSetting('has_header'), TRUE);
    $this->assertEquals($parser->getSetting('header_map'), NULL);
    $this->assertEquals($parser->getSetting('max_records'), NULL);
    $this->assertEquals($parser->getSetting('record_length'), 0);
  }

  /**
   * Tests the two parsing methods for strings and for files.
   *
   * @param \CsvTools\CsvListParser $parser
   * @param $method
   * @param $argument
   *
   * @dataProvider providerCsvParsingMethod
   */
  public function testCsvParsingMethod(CsvListParser $parser, $method, $argument) {
    $parsedCsv = $parser->$method($argument);

    // 1 header, 11 columns and 5 rows.
    $this->assertEquals(11, count($parsedCsv[0]));
    $this->assertEquals(6, count($parsedCsv[1]));
    $this->assertEquals(['NAME', 'MOBILE', 'MOBILE2', 'EMAIL', 'CITY', 'COUNTRY',
      'BIRTH_DAY', 'WORK', 'NOTES', 'ACTIVE_ROLES', 'WANTED_ROLES'], $parsedCsv[0]);
    $this->assertEquals(['Lolly', '2347090783839', '','noreply@example.com', 'NoCity',
      'NoCountry', '38758', 'My work', 'My Notes', '', ''], $parsedCsv[1][4]);

    $parser->setSetting('header_map', [
      'name' => 'NAME',
      'phone' => 'MOBILE',
      'email address' => 'EMAIL',
    ]);
    $parsedCsv = $parser->$method($argument);

    // 1 header, 3 mapped columns and 5 rows.
    $this->assertEquals(3, count($parsedCsv[0]));
    $this->assertEquals(6, count($parsedCsv[1]));
    $this->assertEquals($parsedCsv[0], ['name', 'phone', 'email address']);
    $this->assertEquals($parsedCsv[1][0], ['Jolly', '2348030783839', 'noreply@example.com']);

    $parser
      ->setSetting('header_map', [
        'name' => 0,
        'phone' => 1,
        'mobile' => 1,
        'email' => 3,
        'username' => 0,
      ])
      ->setSetting('has_header', FALSE);
    $parsedCsv = $parser->$method($argument);
    // 1 header, 5 mapped columns and 5 rows.
    $this->assertEquals(5, count($parsedCsv[0]));
    $this->assertEquals($parsedCsv[0], ['name', 'phone', 'mobile', 'email', 'username']);
    $this->assertEquals($parsedCsv[1][0], ['NAME', 'MOBILE', 'MOBILE', 'EMAIL', 'NAME']);
    $this->assertEquals($parsedCsv[1][1], ['Jolly', '2348030783839', '2348030783839', 'noreply@example.com', 'Jolly']);

    // Read only two records.
    $parser->setSetting('max_records', 2);
    $parsedCsv = $parser->$method($argument);
    $this->assertEquals(2, count($parsedCsv[1]));

    // Ensure this works also with
    $parser->unsetSetting('header_map');
    $parsedCsv = $parser->$method($argument);
    $this->assertEquals(2, count($parsedCsv[1]));
  }

  public function providerCsvParsingMethod() {
    return [
      [new CsvListParser(), 'parseCsvString', $this->csvString()],
      [new CsvListParser(), 'parseCsvFile', __DIR__ . '/../files/filename_a.csv'],
    ];

  }

  protected function csvString() {
    return <<<CSV
NAME,MOBILE,MOBILE2,EMAIL,CITY,COUNTRY,BIRTH_DAY,WORK,NOTES,ACTIVE_ROLES,WANTED_ROLES
Jolly,2348030783839,,noreply@example.com,NoCity,NoCountry,38758,My work,My Notes,,
Nolly,2348038983839,2348030783839,noreply@example.com,NoCity,NoCountry,38758,My work,My Notes,,
Polly,2348030783839,2348030783839,noreply@example.com,NoCity,NoCountry,38758,My work,My Notes,,
Solly,2348030783457,2348030783839,3reply@example.com,NoCity,NoCountry,38758,My work,My Notes,,
Lolly,2347090783839,,noreply@example.com,NoCity,NoCountry,38758,My work,My Notes,,
Wolly,2347090783234,,1reply@example.com,NoCity,NoCountry,38758,My work,My Notes,,
CSV;
  }

}
