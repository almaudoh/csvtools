<?php

namespace CsvTools;

class CsvDataListMapper implements \Iterator {

  /**
   * The header of the parsed CSV file
   *
   * @var array
   */
  protected $header;

  /**
   * TRUE if the CSV is specified as having headers, FALSE otherwise.
   * @var bool
   */
  protected $hasHeader;

  /**
   * Pre-configured mapping of CSV headers to data list fields.
   *
   * This should be in the form
   *
   * @var array
   */
  protected $headerMap;

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
  protected $rowIndex;

  public function __construct() {
    $this->csvParser = new CsvListParser();
  }

  /**
   * Sets the CSV source text to be parsed and imported into the data list.
   *
   * If both source text and file are set, the source CSV text takes precedence.
   *
   * @return $this
   */
  public function setSourceText($csv_text) {
    $this->csvText = $csv_text;
    $this->csvData = NULL;
    return $this;
  }

  /**
   * Sets the CSV source file to be read and parsed into the data list.
   *
   * If both source text and file are set, the CSV text takes precedence.
   *
   * @return $this
   */
  public function setSourceFile($filename) {
    $this->csvFilename = $filename;
    $this->csvData = NULL;
    return $this;
  }

  /**
   * Sets the mapping of CSV headers to fields in the resulting CSV data list.
   *
   * @param array $header_mapping
   *   A mapping of the CSV headers to the various
   *
   * @return $this
   */
  public function setHeaderMap(array $header_mapping) {
    $this->headerMap = $header_mapping;
    $this->csvData = NULL;
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
    $this->hasHeader = $has_header;
    $this->csvData = NULL;
    return $this;
  }

  /**
   * Gets the header of the parsed CSV file or string.
   *
   * @return array
   */
  public function getHeader() {
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
   * Parses the CSV file or text.
   */
  protected function parseCsvData() {
    if (isset($this->csvText)) {
      $this->initializeParser();
      $csv = $this->csvParser->parseCsvString($this->csvText);
      if ($this->hasHeader) {
        $this->header = array_shift($csv);
        $this->csvData = $csv;
      }
      else {
        $this->header = array_keys($csv[0]);
        $this->csvData = $csv;
      }
    }
    elseif (isset($this->csvFilename)) {
      list($header, $data) = $this->csvParser->readCsvAsArray($this->csvFilename);
      $this->header = $header;
      $this->csvData = $data;
    }
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
    return $this->rowIndex >= 0 && $this->rowIndex < count($this->csvText);
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    $this->rowIndex = 0;
  }

  /**
   * Initializes the CSV parser with settings needed to parse the source string.
   */
  protected function initializeParser() {
    $this->csvParser
      ->setSetting('has_header', $this->hasHeader)
      ->setSetting('slice', $this->headerMap);
  }

}
