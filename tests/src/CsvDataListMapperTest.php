<?php

namespace Alma\CsvTools\Tests;

use Alma\CsvTools\CsvDataListMapper;

/**
 * Tests the CsvDataListMapper.
 *
 * @group CsvTools
 */
class CsvDataListMapperTest extends \PHPUnit_Framework_TestCase {
  /**
   * Tests the setSourceText() method, optimization and return value.
   */
  public function testSetSourceText() {
    $mapper = new TestCsvDataListMapper();
    
    // Set the source text and ensure the value is set. Assert return value also.
    $object = $mapper->setSourceText($this->csvString());
    $this->assertEquals($mapper->getSourceText(), $this->csvString());
    $this->assertEquals($object, $mapper);

    // Ensure that the value is not changed if the same value is set again.
    $mapper->getCsvData();
    $mapper->setSourceText($this->csvString());
    $this->assertNotNull($mapper->peekCsvData());

    // Ensure that the value is changed if a different value is set.
    $mapper->setSourceText($this->anotherCsvString());
    $this->assertNull($mapper->peekCsvData());
  }

  /**
   * Tests the setSourceFile() method, optimization and return value.
   */
  public function testSetSourceFile() {
    $mapper = new TestCsvDataListMapper();
    
    // Set the source text and ensure the value is set. Assert return value also.
    $object = $mapper->setSourceFile(__DIR__ . '/../files/filename_a.csv');
    $this->assertEquals($mapper->getSourceFile(), __DIR__ . '/../files/filename_a.csv');
    $this->assertEquals($object, $mapper);

    // Ensure that the value is not changed if the same value is set again.
    $mapper->getCsvData();
    $mapper->setSourceFile(__DIR__ . '/../files/filename_a.csv');
    $this->assertNotNull($mapper->peekCsvData());

    // Ensure that the value is changed if a different value is set.
    $mapper->setSourceFile(__DIR__ . '/../files/filename_b.csv');
    $this->assertNull($mapper->peekCsvData());
  }

  /**
   * Tests the setDataMap() method, optimization and return value.
   */
  public function testSetDataMap() {
    $mapper = new TestCsvDataListMapper();
    $mapper->setSourceText($this->csvString());

    // Set the source text and ensure the value is set. Assert return value also.
    $object = $mapper->setDataMap(['name' => 'NAME']);
    $this->assertEquals($mapper->getDataMap(), ['name' => 'NAME']);
    $this->assertEquals($object, $mapper);

    // Ensure that the value is not changed if the same value is set again.
    $mapper->getCsvData();
    $mapper->setDataMap(['name' => 'NAME']);
    $this->assertNotNull($mapper->peekCsvData());

    // Ensure that the value is changed if a different value is set.
    $mapper->setDataMap(['name' => 0]);
    $this->assertNull($mapper->peekCsvData());
  }

  /**
   * Tests the setHasHeader() method, optimization and return value.
   */
  public function testSetHasHeader() {
    $mapper = new TestCsvDataListMapper();
    $mapper->setSourceText($this->csvString());

    // Set the source text and ensure the value is set. Assert return value also.
    $object = $mapper->setHasHeader();
    $this->assertEquals($mapper->getHasHeader(), TRUE);
    $this->assertEquals($object, $mapper);

    // Ensure that the value is not changed if the same value is set again.
    $mapper->getCsvData();
    $mapper->setHasHeader(TRUE);
    $this->assertNotNull($mapper->peekCsvData());

    // Ensure that the value is changed if a different value is set.
    $mapper->setHasHeader(FALSE);
    $this->assertNull($mapper->peekCsvData());
  }

  /**
   * Tests the setMaxRecords() method, optimization and return value.
   */
  public function testSetMaxRecords() {
    $mapper = new TestCsvDataListMapper();
    $mapper->setSourceText($this->csvString());

    // Set the source text and ensure the value is set. Assert return value also.
    $object = $mapper->setMaxRecords();
    $this->assertEquals($mapper->getMaxRecords(), NULL);
    $this->assertEquals($object, $mapper);

    // Ensure that the value is not changed if the same value is set again.
    $mapper->getCsvData();
    $mapper->setMaxRecords(NULL);
    $this->assertNotNull($mapper->peekCsvData());

    // Ensure that the value is changed if a different value is set.
    $mapper->setMaxRecords(10);
    $this->assertNull($mapper->peekCsvData());
  }

  public function testParseSingleColumnString() {
    $numbers = [];
    for ($i = 0; $i < 5; $i++) {
      $numbers[] = rand(10000000, 99999999);
    }
    $csv = implode("\n", $numbers);
    $mapping = [
      'field_0' => 0,
      'field_1' => 0,
    ];
    $mapper = new CsvDataListMapper();
    $mapper
      ->setSourceText($csv)
      ->setHasHeader(FALSE)
      ->setDataMap($mapping);

    // Test iterator.
    foreach ($mapper as $key => $value) {
      $this->assertEquals(['field_0' => $numbers[$key], 'field_1' => $numbers[$key]], $mapper[$key]);
    }

    // Test array access.
    $this->assertEquals(['field_0' => $numbers[0], 'field_1' => $numbers[0]], $mapper[0]);
    $this->assertEquals(['field_0' => $numbers[1], 'field_1' => $numbers[1]], $mapper[1]);
    $this->assertEquals(['field_0' => $numbers[2], 'field_1' => $numbers[2]], $mapper[2]);
    $this->assertEquals(['field_0' => $numbers[3], 'field_1' => $numbers[3]], $mapper[3]);
    $this->assertEquals(['field_0' => $numbers[4], 'field_1' => $numbers[4]], $mapper[4]);

  }

  public function testParseCsvFile() {
    $mapper = new TestCsvDataListMapper();
    $mapper->setSourceFile(__DIR__ . '/../files/large_file.csv');

    $mapper->setHasHeader(FALSE);
    $this->assertEquals(11, count($mapper->getHeader()));
    $this->assertEquals(4, count($mapper));
    $this->assertEquals('Polly', $mapper[3][0]);

    $mapper->setHasHeader(TRUE);
    $mapper->setDataMap([
      'contact_name' => 'NAME',
      'email_address' => 'EMAIL',
      'phone_number' => 'MOBILE',
    ]);

    $this->assertEquals(3, count($mapper->getHeader()));
    $this->assertEquals(3, count($mapper));
    $this->assertEquals('Polly', $mapper[2]['contact_name']);
    $this->assertEquals('noreply@example.com', $mapper[2]['email_address']);
    $this->assertEquals('2348030783839', $mapper[2]['phone_number']);
  }
  
  public function testArrayIterator() {
    $mapper = new TestCsvDataListMapper();
    $mapper
      ->setSourceText($this->csvString())
      ->setHasHeader();

    // Test the iterator functionality.
    foreach ($mapper as $row) {
      $this->assertEquals(count($row), 11);
      $this->assertEquals($row['EMAIL'], 'noreply@example.com');
    }
  }

  public function testArrayAccess() {
    $mapper = new TestCsvDataListMapper();
    $mapper
      ->setSourceText($this->csvString())
      ->setHasHeader();

    // Test the array access functionality.
    $this->assertEquals([
      'NAME' => 'Lolly',
      'MOBILE' => '2347090783839',
      'MOBILE2' => '',
      'EMAIL' => 'noreply@example.com',
      'CITY' => 'NoCity',
      'COUNTRY' => 'NoCountry',
      'BIRTH_DAY' => '38758',
      'WORK' => 'My work',
      'NOTES' => 'My Notes',
      'ACTIVE_ROLES' => '',
      'WANTED_ROLES' => ''
    ], $mapper[4]);
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testArraySetReadOnly() {
    $mapper = new TestCsvDataListMapper();
    $mapper->setSourceText($this->csvString());
    $mapper[0] = ['a', 'b', 'c'];
  }

  /**
   * @expectedException \RuntimeException
   */
  public function testArrayUnsetReadOnly() {
    $mapper = new TestCsvDataListMapper();
    $mapper->setSourceText($this->csvString());
    unset($mapper[0]);
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

  protected function anotherCsvString() {
    return <<<CSV
NAME,MOBILE,MOBILE2,EMAIL,CITY,COUNTRY,BIRTH_DAY,WORK,NOTES,ACTIVE_ROLES,WANTED_ROLES
Jolly,2348030783839,,noreply@example.com,NoCity,NoCountry,38758,My work,My Notes,,
Nolly,2348038983839,2348030783839,noreply@example.com,NoCity,NoCountry,38758,My work,My Notes,,
Polly,2348030783839,2348030783839,noreply@example.com,NoCity,NoCountry,38758,My work,My Notes,,
CSV;
  }

}

class TestCsvDataListMapper extends CsvDataListMapper {

  public function getSourceText() {
    return $this->csvText;
  }

  public function getSourceFile() {
    return $this->csvFilename;
  }

  public function getDataMap() {
    return $this->dataMap;
  }

  public function getHasHeader() {
    return $this->hasHeader;
  }

  public function getMaxRecords() {
    return $this->maxRecords;
  }
  
  public function peekCsvData() {
    return $this->csvData;
  }

}
