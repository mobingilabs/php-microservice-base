<?php

namespace App\Filter;

use Zend\InputFilter\InputFilter;

class SafeBoxFilter
{
    const FILTER_NAME
        = [
            'name'       => 'name',
            'required'   => true,
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'min' => 3,
                        'max' => 255,
                    ],
                ],
                [
                    'name'    => 'Regex',
                    'options' => [
                        'pattern' => '/^[a-zA-Z0-9_.-]+$/',
                        'message' => "It is only allowed 'letters', 'numbers', '_', '.', '-'"
                    ],
                ],
            ],
        ];
    const FILTER_VALUE
        = [
            'name'       => 'value',
            'required'   => true,
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => 21845,
                    ],
                ],
            ],
        ];
    const FILTER_ENCRYPTED_VALUE
        = [
            'name'       => 'value',
            'required'   => true,
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => 65000,
                    ],
                ],
            ],
        ];

    /**
     * @var InputFilter
     */
    public $filter;

    public function __construct()
    {
        $this->filter = new InputFilter();
    }

    public function filterSafeBoxRead($data)
    {
        $this->filter->add(self::FILTER_NAME);
        $this->filter->setData($data);
        if ($this->filter->isValid()) {
            return true;
        }

        return $this->filter->getMessages();
    }

    public function filterSafeBoxCreate($data)
    {
        $this->filter->add(self::FILTER_NAME);
        $this->filter->add(self::FILTER_VALUE);
        $this->filter->setData($data);
        if ($this->filter->isValid()) {
            return true;
        }

        return $this->filter->getMessages();
    }

    public function filterSafeBoxUpdate($data)
    {
        $this->filter->add(self::FILTER_NAME);
        $this->filter->add(self::FILTER_VALUE);

        $this->filter->setData($data);
        if ($this->filter->isValid()) {
            return true;
        }

        return $this->filter->getMessages();
    }

    public function filterSafeBoxDelete($data)
    {
        $this->filter->add(self::FILTER_NAME);

        $this->filter->setData($data);
        if ($this->filter->isValid()) {
            return true;
        }

        return $this->filter->getMessages();
    }

    public function filterSafeBoxEncrypt($data)
    {
        $this->filter->add(self::FILTER_VALUE);
        $this->filter->setData($data);
        if ($this->filter->isValid()) {
            return true;
        }

        return $this->filter->getMessages();
    }

    public function filterSafeBoxDecrypt($data)
    {
        $this->filter->add(self::FILTER_ENCRYPTED_VALUE);
        $this->filter->setData($data);
        if ($this->filter->isValid()) {
            return true;
        }

        return $this->filter->getMessages();
    }
}
