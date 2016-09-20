<?php

namespace Alma\CsvTools;

class CsvDataListMapper implements \Iterator, \ArrayAccess, \Countable {

  /**
   * The header of the parsed CSV file
   *
   * @var array
   */
  protected $header;

  /**
   * TRUE if the CSV is specified as having headers, FALSE otherwise.
   *
   * @var bool
   */
  protected $hasHeader = TRUE;

  /**
   * Pre-configured mapping of CSV headers to data list fields.
   *
   * This should be in the form
   *
   * @var array
   */
  protected $dataMap;

  /**
   * The source CSV text to be parsed.
   *
   * @var string
   */
  protected $csvText;

  /**
   * The filename of the CSV file to be parsed.
   *
   * @var string
   */
  protected $csvFilename;

  /**
   * The parsed CSV data.
   *
   * @var array
   */
  protected $csvData;

  /**
   * The index of the current row for the iterator.
   *
   * @var int
   */
  protected $rowIndex = 0;

  /**
   * The number of records to be read from the CSV file.
   *
   * @var int
   */
  protected $maxRecords = NULL;

  /**
   * The CSV parser.
   *
   * @var \Alma\CsvTools\CsvListParser
   */
  protected $csvParser;

  public function __construct() {
    $this->csvParser = new CsvListParser();
  }

  /**
   * Sets the CSV source text to be parsed and imported into the data list.
   *
   * If both source text and file are set, the source CSV text takes precedence.
   *
   * @param string $csv_text
   *   The CSV text to be parsed.
   *
   * @return $this
   */
  public function setSourceText($csv_text) {
    if ($this->csvText != $csv_text) {
      $this->csvText = $csv_text;
      $this->csvData = NULL;
    }
    return $this;
  }

  /**
   * Sets the CSV source file to be read and parsed into the data list.
   *
   * If both source text and file are set, the CSV text takes precedence.
   *
   * @param string $filename
   *   The name of the file containing the CSV text to be parsed.
   *
   * @return $this
   */
  public function setSourceFile($filename) {
    if ($this->csvFilename != $filename) {
      $this->csvFilename = $filename;
      $this->csvData = NULL;
    }
    return $this;
  }

  /**
   * Sets the mapping of CSV headers to fields in the resulting CSV data list.
   *
   * @param array $mapping
   *   A mapping of the CSV headers to the data fields for each row.
   *
   * @return $this
   */
  public function setDataMap(array $mapping) {
    if (!isset($this->dataMap) || array_diff($this->dataMap, $mapping) != []) {
      $this->dataMap = $mapping;
      $this->csvData = NULL;
    }
    return $this;
  }

  /**
   * Sets whether the source CSV has a header row or not.
   *
   * @param bool $has_header
   *   Will be TRUE if the source CSV has a header row.
   *
   * @return $this
   */
  public function setHasHeader($has_header = TRUE) {
    if ($this->hasHeader != $has_header) {
      $this->hasHeader = $has_header;
      $this->csvData = NULL;
    }
    return $this;
  }

  /**
   * Sets the maximum number of records to be read from the CSV file.
   *
   * The actual number of records may be less if the CSV is small.
   *
   * @param int $size
   *   The maximum number of records.
   *
   * @return $this
   */
  public function setMaxRecords($size = NULL) {
    if ($this->maxRecords != $size) {
      $this->recordCount = $size;
      $this->csvData = NULL;
    }
    return $this;
  }

  /**
   * Gets the header of the parsed CSV file or string.
   *
   * @return array
   */
  public function getHeader() {
    if (!isset($this->csvData)) {
      $this->parseCsvData();
    }
    return $this->header;
  }

  /**
   * Gets the row data of the parsed CSV file or string.
   *
   * @return array
   */
  public function getCsvData() {
    if (!isset($this->csvData)) {
      $this->parseCsvData();
    }
    return $this->csvData;
  }

  /**
   * Parses the CSV text or file.
   */
  protected function parseCsvData() {
    $this->initializeParser();
    if (isset($this->csvText)) {
      list($this->header, $this->csvData) = $this->csvParser->parseCsvString($this->csvText);
    }
    elseif (isset($this->csvFilename)) {
      list($this->header, $this->csvData) = $this->csvParser->parseCsvFile($this->csvFilename);
    }
  }

  /**
   * Initializes the CSV parser with settings needed to parse the source string.
   */
  protected function initializeParser() {
    $this->csvParser
      ->setSetting('has_header', $this->hasHeader)
      ->setSetting('max_records', $this->maxRecords)
      ->setSetting('header_map', $this->dataMap);
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    if (!isset($this->csvData)) {
      $this->parseCsvData();
    }
    return array_combine($this->header, $this->csvData[$this->rowIndex]);
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    $this->rowIndex++;
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return $this->rowIndex;
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    if (!isset($this->csvData)) {
      $this->parseCsvData();
    }
    return $this->rowIndex >= 0 && $this->rowIndex < count($this->csvData);
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    $this->rowIndex = 0;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    if (!isset($this->csvData)) {
      $this->parseCsvData();
    }
    return array_key_exists($offset, $this->csvData);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    if (!isset($this->csvData)) {
      $this->parseCsvData();
    }
    return array_combine($this->header, $this->csvData[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    throw new \RuntimeException("Data mapper is read-only.");
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    throw new \RuntimeException("Data mapper is read-only.");
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    if (!isset($this->csvData)) {
      $this->parseCsvData();
    }
    return count($this->csvData);
  }

}
