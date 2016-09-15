<?php

namespace CsvTools;

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
   *   - slice (integer|array|null): The segment of the csv file to be returned.
   *     An integer specifies the first n columns to be returned, an array
   *     specifies which particular columns to be returned, while null (default)
   *     returns everything.
   *   - max_length (integer): The maximum number of rows to be returned. Leave
   *     null (default) to return everything.
   *   - record_length (integer): The maximum length of a record in the input
   *     file.
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
      'slice' => NULL,
      'max_length' => NULL,
      'record_length' => 0,
      'has_header' => TRUE,
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
   * Parse a csv text into array of rows (each row being an array of values).
   *
   * @param string $text
   *   A string containing the CSV format to be parsed.
   *
   * @return array
   */
  public function parseCsvString($text) {
    $rows = str_getcsv($text, "\n", $this->settings['quote']);
    foreach ($rows as $key => $row) {
      $rows[$key] = array_map(function ($value) {
        return trim($value);
      }, str_getcsv($row, $this->settings['delimiter']), $this->settings['quote']);
    }
    return $rows;
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
          case ON_COLLISION_OVERWRITE:
            $rows[$index_by] = array_combine($header, $data);
            break;
          case ON_COLLISION_SKIP:
            break;
          case ON_COLLISION_ABORT:
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
   * Reads a CSV file and stores it as a two-dimensional array.
   *
   * The two-dimensional array corresponds to the rows and columns of the csv
   * file.
   *
   * @param  string $csv_file
   *   The CSV file to read.
   *
   * @return array
   *   A two value array with containing the header and the body of the CSV file.
   *
   * @throws \InvalidArgumentException
   *   If the file supplied is not in the valid CSV format.
   */
  public function readCsvAsArray($csv_file) {

//    $handle = fopen($csv_file, 'r');
//    if ($handle === NULL || ($data = fgetcsv($handle, $this->settings['record_length'], $this->settings['delimiter'], $this->settings['quotes'])) === FALSE) {
//      // Couldn't open/read from CSV file.
//      throw new \Exception(sprintf('Failed to read from CSV file "%s"', $csv_file));
//    }

    $file = new \SplFileObject($csv_file);
    $file->setFlags(\SplFileObject::READ_CSV);
    $file->setCsvControl($this->settings['delimiter'], $this->settings['quote']);

    if (!$this->isValidCsv($file)) {
      throw new \InvalidArgumentException(sprintf('Invalid CSV file provided: "%s"', $csv_file));
    }

    $header = array(); $indexes = array();
    $slice = $this->settings['slice'];

    // If the CSV is specified as having a header, then assume the first line
    // contains the header.
    if ($this->settings['has_header']) {
      foreach ($file->current() as $index => $field) {
        if (is_numeric($slice)) {
          if ($index < $slice) {
            $header[] = trim($field);
          }
        }
        elseif (is_array($slice)) {
          if (in_array($index, $slice) || in_array($field, $slice)) {
            $header[$index] = trim($field);
            $indexes[] = $index;
          }
        }
        else {
          $header[] = trim($field);
        }
      }
      $file->next();
    }

    // Process the body of the CSV file.
    $rows = array();
    $ct = 0;
    while (!$file->eof()) {
      if (!is_null($this->settings['max_length']) && ++$ct > $this->settings['max_length']) {
        break;
      }

      $data = $file->current();
      if (is_integer($slice)) {
        $rows[] = array_slice($data, 0, $slice);
      }
      elseif (is_array($slice)) {
        $arr = array();
        foreach ($data as $k => $v) {
          if (in_array($k, $indexes)) {
            $arr[$k] = $v;
          }
        }
        $rows[] = $arr;
      }
      else {
        $rows[] = $data;
      }
      $file->next();
    }
    $file = NULL;

    return array($header, $rows);
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
  public function isValidCsv($file) {
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

}
