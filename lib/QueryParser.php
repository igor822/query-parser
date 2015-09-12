<?php

namespace QueryParser;

use Zend\Config\Reader;

class QueryParser
{
    private $filename;

    private $reader;

    private $data = [];

    private $resource;

    private $prefixPath = '';

    /**
     * Constructor of class
     *
     * @access public
     * @param string $file Path of file
     * @param array $options
     */
    public function __construct($file = '', $options = [])
    {
        $this->filename = $file;
        if (!empty($options['prefix'])) {
            $this->setPrefixPath($options['prefix']);
        }

        if (!empty($options['resource'])) {
            $this->resource = $options['resource'];
        }

        $this->reader = new Reader\Yaml(array('Spyc','YAMLLoadString'));
        if ($file != '') {
            $this->configure($file);
        }
    }

    public function setPrefixPath($prefixPath)
    {
        $this->prefixPath = $prefixPath;
    }

    public function getPrefixPath()
    {
        return $this->prefixPath;
    }

    public function openFile($file)
    {
        $this->data = array_merge($this->data, $this->reader->fromFile($file));
    }

    /**
     * Convert yaml file to array and store into $_data
     *
     * @access public
     * @param {string} $file Path of file with all queries
     * @return $this
     */
    public function configure($file)
    {
        if (is_file($file)) {
            $this->openFile($file);
            return $this;
        }

        foreach (new \DirectoryIterator(realpath($file)) as $f) {
            if ($f->isDot()) {
                continue;
            }

            $filename = $this->setResourceContent($f) ? '' : $f->getPath() . DIRECTORY_SEPARATOR . $f->getFilename();
            $this->openFile($filename);
        }

        return $this;
    }

    private function setResourceContent($f)
    {
        if ($this->resource == '' && pathinfo($f->getFilename(), PATHINFO_FILENAME) === $this->resource) {
            $this->openFile($f->getPath() . DIRECTORY_SEPARATOR . $f->getFilename());
            return true;
        }

        return false;
    }

    /**
     * Retrieve all data stored at file
     *
     * @access public
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Find and replace the field:type into query
     * /<(.*):([a-z]+)>/ Get parameters of query
     *
     * @access static
     * @param {string} $query Query to be replaced
     * @param {array} $values Array with 'key' to find into query and value to be replaced
     * @return string
     */
    public static function replaceValues($query, $values = null)
    {
        $replaced = false;
        if (!empty($values) &&
            preg_match_all('/<([a-zA-Z0-9_]+):([a-zA-Z0-9_]+)>/', $query, $matches, PREG_SET_ORDER) !== 0) {
            foreach ($matches as $match) {
                if (array_key_exists($match[1], $values) && !empty($values[$match[1]])) {
                    switch ($match[2]) {
                        case 'int':
                            $val = $values[$match[1]];
                            break;
                        case 'like':
                            $val = '\'%'.$values[$match[1]].'%\'';
                            break;
                        case 'in_array':
                            $val = implode(',', $values[$match[1]]);
                            $val = '('.$val.')';
                            break;
                        case 'str':
                        default:
                            $val = '\''.$values[$match[1]].'\'';
                            break;
                    }
                    $query = preg_replace('/<'.$match[1].':([a-zA-Z0-9_]+)>/', $val, $query);
                    $replaced = true;
                } else {
                    self::removeNamedConditional($query, $match[1]);
                }
            }
        }
        if (false === $replaced) {
            return self::removeConditionals($query);
        }
        return self::removeConditionalParameter($query);
    }

    /**
     * Find and remove conditional parts of query in case of none parameters has replaced
     *
     * @access static
     * @param {string} $query Query to be formated
     * @return string
     */
    public static function removeConditionals(&$query)
    {
        $pattern = '/(\S+:)?\[(.*)|\]/';
        if (preg_match_all($pattern, $query, $matches, PREG_SET_ORDER) !== 0) {
            $query = preg_replace($pattern, '', $query);
        }
        return $query;
    }

    /**
     * Find and remove specific conditionals with name
     *
     * @access static
     * @param {string} $query Query to be formated
     * @param {string} $name Name of conditional
     * @return string
     */
    public static function removeNamedConditional(&$query, $name = '')
    {
        $pattern = '/'.$name.':\[(.*)|\]/';
        if (preg_match_all($pattern, $query, $matches, PREG_SET_ORDER) !== 0) {
            $query = preg_replace($pattern, '', $query);
        }
        return $query;
    }

    /**
     * Find and remove conditional characters
     *
     * @access static
     * @param {string} $query Query to be formated
     * @return string
     */
    public static function removeConditionalParameter(&$query)
    {
        $pattern = '/(\S+:)?\[|\]/';
        if (preg_match_all($pattern, $query, $matches, PREG_SET_ORDER) !== 0) {
            $query = preg_replace($pattern, '', $query);
        }
        return $query;
    }

    /**
     * Find query into array, setting the 'path' of array keys
     * /[^\s]+(\.\w+)/ Match string concatenated by '.'
     *
     * @access public
     * @param {string} $path Path of keys into array
     * @return array
     */
    public function findQuery($path = '')
    {
        if (preg_match('/[^\s]+(\.\w+)/', $path, $matches) !== 0) {
            if ($this->getPrefixPath() != '' && strpos($this->getPrefixPath(), $path) === false) {
                $path = $this->getPrefixPath().'.'.$path;
            }
            $parts = explode('.', $path);
            $data = $this->iterateData($parts, $this->data);
            return $data;
        }
        return array();
    }

    /**
     * Method to iterate all data and retrieve query content with path of keys
     *
     * @access private
     * @param {array} $parts Keys of data to get
     * @param {array} $data Array with all content
     * @return {mixed} Get all content with path iterated
     */
    private function iterateData($parts, $data)
    {
        $qData = array();
        if (is_array($data) && count($data) > 0 && !empty($parts[0])) {
            $qData = array('source' => $data);
            foreach ($parts as $key) {
                if (!empty($qData['source'][$key])) {
                    $qData['source'] = $qData['source'][$key];
                }
            }
        }
        return $qData['source'];
    }
}
