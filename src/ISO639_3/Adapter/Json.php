<?php
/**
 * ISO 639-3
 *
 * Copyright © 2016 Juan Pedro Gonzalez Gutierrez
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */
namespace ISOCodes\ISO639_3\Adapter;

use ISOCodes\Adapter\AbstractAdapter;
use ISOCodes\Exception;
use ISOCodes\ISO639_3\Model\ISO639_3;
use ISOCodes\ISO639_3\Model\ISO639_3Interface;

class Json extends AbstractAdapter implements AdapterInterface
{
    /**
     * @var array
     */
    protected $data;
    
    /**
     * Get an object by its code.
     * 
     * @param string $code
     * @return ISO639_3Interface
     * @throws Exception\InvalidArgumentException
     */
    public function get($code)
    {
        if (null === $this->data) {
            $this->loadFile();
        }
        
        // Detect code
        if (preg_match('/^[a-zA-Z]{2}$/', $code)) {
            foreach ($this->data as $current) {
                if (strcasecmp($current->alpha2, $code)) {
                    return $current;
                }
            }
        } elseif (preg_match('/^[a-zA-Z]{3}$/', $code)) {
            return (isset($this->data[strtoupper($code)]) ? $this->data[strtoupper($code)] : null);
        } else {
            throw new Exception\InvalidArgumentException('code must be a valid alpha-2 or alpha-3 code.');
        }
        
        return null;
    }
    
    /**
     * Get all the objects.
     * 
     * @param string|null $scope The scope of the language: I(ndividual), M(acrolanguage), S(pecial)
     * @param string|null $type  The type of the language: A(ncient), C(onstructed), E(xtinct), H(istorical), L(iving), S(pecial)
     * @return ISO639_3Interface[]
     */
    public function getAll($scope = null, $type = null)
    {
        if (null === $this->data) {
            $this->loadFile();
        }
        
        if (empty($scope) && empty($type)) {
            return $this->data;
        } else {
            $results = array();
            
            if ((!empty($scope)) && (!empty($type))) {
                foreach ($this->data as $current) {
                    if ((strcasecmp($scope, $current->scope) === 0) && (strcasecmp($type, $current->type) === 0)) {
                        $results[] = $current;
                    }
                }
            } elseif(!empty($scope)) {
                foreach ($this->data as $current) {
                    if (strcasecmp($scope, $current->scope) === 0) {
                        $results[] = $current;
                    }
                }
            } if(!empty($type)) {
                foreach ($this->data as $current) {
                    if (strcasecmp($type, $current->type) === 0) {
                        $results[] = $current;
                    }
                }
            }
         
            return $results;
        }
    }

    /**
     * Get an object by its code.
     *
     * @param string $code
     * @return ISO639_3Interface
     * @throws Exception\InvalidArgumentException
     */
    public function getBibliographic($code)
    {
        if (null === $this->data) {
            $this->loadFile();
        }
    
        // Detect code
        if (preg_match('/^[a-zA-Z]{3}$/', $code)) {
            foreach ($this->data as $current) {
                if (strcasecmp($current->bibliographic, $code)) {
                    return $current;
                }
            }
        } else {
            throw new Exception\InvalidArgumentException('bibliograhic code must be a 3 letter code.');
        }
    
        return null;
    }
    
    /**
     * Check if an object with the given code exists.
     * 
     * @param string|int $code
     * @return bool
     * @throws Exception\InvalidArgumentException
     */
    public function has($code)
    {
        if (null === $this->data) {
            $this->loadFile();
        }
        
        // Detect code
        if (preg_match('/^[a-zA-Z]{2}$/', $code)) {
            foreach ($this->data as $current) {
                if (strcasecmp($current->alpha2, $code)) {
                    return true;
                }
            }
        } elseif (preg_match('/^[a-zA-Z]{3}$/', $code)) {
            return isset($this->data[strtoupper($code)]);
        } else {
            throw new Exception\InvalidArgumentException('code must be a valid alpha-2 or alpha-3 code.');
        }
        
        return false;
    }
    
    /**
     * Check if an object with the given bibliographic code exists.
     * 
     * @param string|int $code
     * @return bool
     * @throws Exception\InvalidArgumentException
     */
    public function hasBibliographic($code)
    {
        if (null === $this->data) {
            $this->loadFile();
        }
    
        // Detect code
        if (preg_match('/^[a-zA-Z]{3}$/', $code)) {
            foreach ($this->data as $current) {
                if (strcasecmp($current->bibliographic, $code)) {
                    return true;
                }
            }
        } else {
            throw new Exception\InvalidArgumentException('bibliograhic code must be a 3 letter code.');
        }
    
        return false;
    }
    
    /**
     * Load the JSON file contents
     */
    protected function loadFile()
    {
        $filename = dirname(dirname(dirname(__DIR__))) . '/data/json/iso_639-3.json';
        
        if (!(file_exists($filename) && is_readable($filename))) {
            throw new Exception\FileNotFoundException(sprintf('%s not found or not readable.', $filename));
        }
        
        $data = json_decode(file_get_contents($filename), true);
        if (!is_array($data)) {
            throw new Exception\RuntimeException(sprintf('%s is not a valid JSON file.', $filename));
        }
        
        if (!array_key_exists('639-3' , $data)) {
            throw new Exception\RuntimeException(sprintf('%s is not a valid JSON file for ISO-639-3.', $filename));
        }
        
        $data = $data['639-3'];
        
        // Lazy load the protoype
        if (null === $this->modelPrototype) {
            $this->modelPrototype = new ISO639_3();
        } elseif (!$this->modelPrototype instanceof ISO639_3Interface) {
            throw new Exception\RuntimeException(sprintf('The model prototype for %s must be an instance of %s', __CLASS__, ISO639_3Interface::class));
        }
        
        // Setting objects and the primary key
        foreach ($data as $current) {
            $obj = clone $this->modelPrototype;
            $obj->exchangeArray($current);
            $obj->_translator = $this->getTranslator();
            
            $this->data[strtoupper($current['alpha_3'])] = $obj; 
        }
    }
}
