<?php

namespace Sta\Commons;

use Sta\Commons\Exception\StdClassInvalidArgument;

class StdClass
{
    protected $__failIfAttributeIsNotEncapsulated = true;

    public function __construct(array $initialData = [])
    {
        $this->fromArray($initialData);
    }

    /**
     * @ignore
     *
     * @param string $attributeName
     * @param $value
     *
     * @throws StdClassInvalidArgument
     */
    public function set($attributeName, $value)
    {
        $method = 'set' . ucfirst($attributeName);
        if (is_callable([$this, $method])) {
            $this->$method($value);

            return;
        }

        if ($this->__failIfAttributeIsNotEncapsulated) {
            throw new StdClassInvalidArgument(
                'Não existe um método para definir o valor do atributo: "' . $attributeName . '"'
            );
        }

        $this->$attributeName = $value;
    }

    /**
     * @param $attributeName
     *
     * @return mixed
     */
    public function get($attributeName)
    {
        $method = 'get' . ucfirst($attributeName);
        if (is_callable([$this, $method])) {
            return $this->$method();
        }
        $method = 'is' . ucfirst($attributeName);
        if (is_callable([$this, $method])) {
            return $this->$method();
        }

        if ($this->__failIfAttributeIsNotEncapsulated) {
            throw new StdClassInvalidArgument(
                'Não existe um método para retornar o valor do atributo: "'
                . $attributeName . '"'
            );
        }

        return $this->$attributeName;
    }

    public function fromArray(array $data)
    {
        foreach ($data as $attr => $value) {
            if (is_string($value) && trim($value) && $date = \DateTime::createFromFormat(DATE_ISO8601, $value)) {
                $value = $date;
            }
            $this->set($attr, $value);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->_toArray($this);
    }

    /**
     * @param $value
     *
     * @return array
     */
    private function _toArray($value)
    {
        $result = [];
        if ($value instanceof \DateTime) {
            $result = $value->format(DATE_ISO8601);
        } else if (is_object($value) || is_array($value)) {
            $isMyOwnInstance = false;
            if (is_object($value)) {
                $vars            = get_object_vars($value);
                $isMyOwnInstance = ($value instanceof StdClass);
            } else {
                $vars = $value;
            }
            foreach ($vars as $var => $val) {
                try {
                    if ($isMyOwnInstance) {
                        $val = $this->get($var);
                    }
                } catch (StdClassInvalidArgument $e) {
                    // Ignora essa propiedade se ela não tiver um método get definido.
                    continue;
                }

                if (is_object($val) || is_array($val)) {
                    $val = $this->_toArray($val);
                }
                $result[$var] = $val;
            }
        }

        return $result;
    }
}
