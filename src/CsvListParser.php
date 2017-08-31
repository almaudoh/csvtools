<?php

namespace Alma\CsvTools;

/**
 * Provides utility functions for parsing CSV lists.
 */
class CsvListParser {

  /**
   * Constants to determine behavior of custom csv parser on collisions.
   */
  const ON_COLLISION_OVERWRITE = 1;
  const ON_COLLISION_SKIP = 2;
  const ON_COLLISION_ABORT = 3;

  /**
   * Settings to be used to parse the imported string.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * Returns the default settings for parsing CSV files.
   *
   * @return array
   *   An array containing the following:
   *   - delimiter (string): The character that is used in the CSV file as field
   *     separator.
   *   - quote (string): The character that is used to enclose long strings
   *     (including the delimiter).
   *   - separator_index (string): The character that separates the fields used
   *     to construct the hash table's index.
   *   - index_by (array): The array containing the columns to index the lookup
   *     table by, and the function to pre-process those columns with.
   *   - on_collision (integer): A constant that determines what to do when an
   *     index is already in use.
   *   - has_header: Specifies whether the CSV text has a header or not.
   *   - header_map: Maps the headers on the CSV text to a predefined set of
   *     fields. The returned data will have these new headers instead of the
   *     original headers that came with the CSV. For CSV without headers, the
   *     original zero-based column indices can be used. This value should be an
   *     array with the new headers as keys and the original headers or column
   *     indices as values. E.g.:
   *     ['name' => 'User name', 'phone' => 'telephone no.', etc.]
   *     If header_map is specified only those headers in the map and their
   *     corresponding columns will be returned.
   *   - max_records (integer): The maximum number of rows to be returned. Leave
   *     null (default) to return everything.
   *   - record_length (integer): The maximum length of a record in the input
   *     file.
   *   - skip_empty (boolean): Whether to skip parsed empty records or not.
   */
  protected function defaultSettings() {
    return [
      'delimiter' => ',',
      'quote' => '"',
      'separator_index' => '|',
      'index_by' => [
        0 => ''
      ],
      'on_collision' => static::ON_COLLISION_ABORT,
      'has_header' => TRUE,
      'header_map' => NULL,
      'max_records' => NULL,
      'record_length' => 0,
      'skip_empty' => FALSE,
    ];
  }

  /**
   * Creates a new CsvListParser.
   *
   * @param array $settings
   *   The settings for parsing the CSV files.
   *
   * @see static::defaultSettings().
   */
  public function __construct(array $settings = []) {
    $this->settings = $settings + $this->defaultSettings();
  }

  /**
   * Gets one of the settings for parsing CSV files.
   *
   * @param string $name
   *
   * @return mixed
   */
  public function getSetting($name) {
    return $this->settings[$name];
  }

  /**
   * Sets one of the settings for parsing CSV files.
   *
   * @param string $name
   *   The name of the setting to change.
   * @param mixed $value
   *   The new value of the setting
   *
   * @return $this
   *
   * @see ::defaultSettings() for the list of all settings and possible values.
   */
  public function setSetting($name, $value) {
    $this->settings[$name] = $value;
    return $this;
  }

  /**
   * Unsets a setting to allow the default values to be used.
   *
   * @param string $name
   *   The name of the setting to unset.
   *
   * @return $this
   *
   * @see ::defaultSettings() for the list of all default values.
   */
  public function unsetSetting($name) {
    $this->settings[$name] = $this->defaultSettings()[$name];
    return $this;
  }

  /**
   * Parses a CSV text into array of rows (each row being an array of values).
   *
   * @param string $text
   *   A string containing the text to be parsed in CSV format.
   *
   * @return array
   *   A two value array containing the header and the body of the CSV file.
   */
  public function parseCsvString($text) {
    // Parse the rows in the CSV string.
    $rows = str_getcsv($text, "\n", $this->settings['quote']);

    // Remove the header, use the column indices if no header is specified.
    if ($this->settings['has_header']) {
      $header = $this->parseCsvLine(array_shift($rows));
    }
    else {
      $header = array_keys($this->parseCsvLine($rows[0]));
    }

    if (is_array($this->settings['header_map'])) {
      // Select the header fields according to the header map.
      $new_header = [];
      foreach ($this->settings['header_map'] as $field => $column) {
        $new_header[] = $field;
      }

      // Parse the remaining rows and select only those in the header map.
      $records_read = 0; $new_rows = [];
      foreach ($rows as $key => $row) {
        if (isset($this->settings['max_records']) && $records_read++ >= $this->settings['max_records']) {
          break;
        }
        $parsed_row = $this->parseCsvLine($row);
        if ($parsed_row) {
          $parsed_row = array_combine($header, $this->equalizeColumns($parsed_row, $header));
          $new_row = [];
          foreach ($this->settings['header_map'] as $field => $column) {
            $new_row[] = $parsed_row[$column];
          }
          $new_rows[$key] = $new_row;
        }
      }
      return [$new_header, $new_rows];
    }
    else {
      // Parse the remaining rows.
      $records_read = 0; $new_rows = [];
      foreach ($rows as $key => $row) {
        if (isset($this->settings['max_records']) && $records_read++ >= $this->settings['max_records']) {
          break;
        }
        if ($parsed_row = $this->equalizeColumns($this->parseCsvLine($row), $header)) {
          $new_rows[$key] = $parsed_row;
        }
      }
      // Return combined array of header and rows.
      return [$header, $new_rows];
    }
  }

  /**
   * Helper function to parses a single line of CSV using the current settings.
   *
   * @param string $line
   *   A single line in a CSV string.
   *
   * @return array
   *   The parsed CSV line.
   */
  protected function parseCsvLine($line) {
    if (trim($line, $this->settings['delimiter']) || $this->settings['skip_empty'] == FALSE) {
      return str_getcsv($line, $this->settings['delimiter'], $this->settings['quote']);
    }
    return [];
  }

  /**
   * Parses a CSV file into array of rows (each row being an array of values).
   *
   * The two-dimensional array corresponds to the rows and columns of the CSV
   * file.
   *
   * @param  string $csv_file
   *   The name of the CSV file to read.
   *
   * @return array
   *   A two value array containing the header and the body of the CSV file.
   *
   * @throws \InvalidArgumentException
   *   If the file supplied is not in the valid CSV format.
   */
  public function parseCsvFile($csv_file) {
    $file = new \SplFileObject($csv_file);
    $file->setFlags(\SplFileObject::READ_CSV);
    $file->setCsvControl($this->settings['delimiter'], $this->settings['quote']);

    if (!$this->isValidCsvFile($file)) {
      throw new \InvalidArgumentException(sprintf('Invalid CSV file provided: "%s"', $csv_file));
    }

    // If the CSV is specified as having a header, then assume the first line
    // contains the header.
    if ($this->settings['has_header']) {
      $header = $file->current();
      $file->next();
    }
    else {
      $header = array_keys($file->current());
    }

    $rows = [];
    if (is_array($this->settings['header_map'])) {
      // Select the header fields according to the header map.
      $new_header = [];
      foreach ($this->settings['header_map'] as $field => $column) {
        $new_header[] = $field;
      }

      // Process the body of the CSV file.
      // Parse the remaining rows and select only those in the header map.
      $records_read = 0; $new_rows = [];
      while (!$file->eof()) {
        if (isset($this->settings['max_records']) && $records_read++ >= $this->settings['max_records']) {
          break;
        }
        $parsed_row = array_combine($header, $this->equalizeColumns($file->current(), $header));
        if (implode('', $parsed_row) || $this->settings['skip_empty'] == FALSE) {
          $new_row = [];
          foreach ($this->settings['header_map'] as $field => $column) {
            $new_row[] = $parsed_row[$column];
          }
          $rows[] = $new_row;
        }
        $file->next();
      }
      $file = NULL;
      return [$new_header, $rows];
    }
    else {
      $records_read = 0;
      while (!$file->eof()) {
        if (isset($this->settings['max_records']) && $records_read++ >= $this->settings['max_records']) {
          break;
        }
        $parsed_row = $this->equalizeColumns($file->current(), $header);
        if (implode('', $parsed_row) || $this->settings['skip_empty'] == FALSE) {
          $rows[] = $parsed_row;
        }
        $file->next();
      }
      $file = NULL;
      return [$header, $rows];
    }

  }

  /**
   * Reads a CSV file and stores it as a lookup implemented as a PHP hash table.
   *
   * @param string $csv_file
   *   The CSV file to read.
   *
   * @return mixed
   *   An error number or the resulting hash table.
   */
  public function readCsvAsHashTable($csv_file) {
    $handle = fopen($csv_file, 'r');
    if ($handle == NULL || ($data = fgetcsv($handle, $this->settings['record_length'], $this->settings['delimiter'])) === FALSE) {
      // Couldn't open/read from CSV file.
      return -1;
    }

    $header = array();
    foreach ($data as $field) {
      $header[] = trim($field);
    }

    $indexes = array();
    foreach ($this->settings['index_by'] as $index_in => $function) {
      if (is_int($index_in)) {
        if ($index_in < 0 || $index_in > count($data)) {
          // Index out of bounds.
          fclose($handle);
          return -2;
        }

        $index_out = $index_in;
      }
      else {
        // If a column that is used as part of the key to the hash table is supplied
        // as a name rather than as an integer, then determine that named column's
        // integer index in the $header array, because the integer index is used, below.
        $get_index = array_keys($header, $index_in);
        $index_out = $get_index[0];

        if (is_null($index_out)) {
          // A column name was given (as opposed to an integer index), but the
          // name was not found in the first row that was read from the CSV file.
          fclose($handle);
          return -3;
        }
      }

      $indexes[$index_out] = $function;
    }

    if (count($indexes) == 0) {
      // No columns were supplied to index by.
      fclose($handle);
      return -4;
    }

    $rows = array();
    while (($data = fgetcsv($handle, $this->settings['record_length'], $this->settings['delimiter'])) !== FALSE) {
      $index_by = '';
      foreach ($indexes as $index => $function) {
        $index_by .= ($function ? $function($data[$index]) : $data[$index]) . $this->settings['separator_index'];
      }
      $index_by = substr($index_by, 0, -1);

      if (isset($rows[$index_by])) {
        switch ($this->settings['on_collision']) {
          case CsvListParser::ON_COLLISION_OVERWRITE:
            $rows[$index_by] = array_combine($header, $data);
            break;
          case CsvListParser::ON_COLLISION_SKIP:
            break;
          case CsvListParser::ON_COLLISION_ABORT:
            return -5;
        }
      }
      else {
        $rows[$index_by] = array_combine($header, $data);
      }
    }
    fclose($handle);

    return $rows;
  }

  /**
   * @param \SplFileObject|string $file
   *   The file to check if it's valid CSV.
   *
   * @return bool
   *   true if the supplied file is valid CSV.
   *
   * @throws \InvalidArgumentException
   *   If an invalid $file object is given
   */
  public function isValidCsvFile($file) {
    if (is_string($file)) {
      $file = new \SplFileObject($file);
      $file->setFlags(\SplFileObject::READ_CSV);
      $file->setCsvControl($this->settings['delimiter'], $this->settings['quote']);
    }
    if (!$file instanceof \SplFileObject) {
      throw new \InvalidArgumentException('Invalid file type provided');
    }
    // Scan the first 10 lines only.
    $limit = new \LimitIterator($file, 0, 10);
    $columnCount = [];
    foreach ($limit as $row) {
      // Skip empty lines.
      if ($row === array(null)) continue;
      $columnCount[] = count($row);
    }
    $file->rewind();
    return count(array_unique($columnCount)) === 1;
  }

  /**
   * Makes up the number of columns in a parsed row to match the header.
   *
   * @param array $parsed_row
   *   The already parsed row, which may have more or less elements than header.
   * @param array $header
   *   The header.
   *
   * @return array
   *   The made up row.
   */
  protected function equalizeColumns(array $parsed_row, array $header) {
    // Empty row should not be considered as well.
    $diff = count($header) - count($parsed_row);
    if (empty($parsed_row) || $diff === 0) {
      return $parsed_row;
    }
    if ($diff > 0) {
      $parsed_row += array_fill(count($parsed_row), $diff, '');
    }
    else if ($diff < 0) {
      $parsed_row = array_slice($parsed_row, 0, $diff);
    }
    return $parsed_row;
  }

}
