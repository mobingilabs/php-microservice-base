<?php

namespace App\Filter;

use Zend\InputFilter\InputFilter;

class UserFilter
{
    const FILTER_ID
        = [
            'name'       => 'id',
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

    public function filterUserRead($data)
    {
        $this->filter->add(self::FILTER_ID);
        $this->filter->setData($data);
        if ($this->filter->isValid()) {
            return true;
        }

        return $this->filter->getMessages();
    }

    public function filterUserCreate($data)
    {
        $this->filter->add(self::FILTER_ID);
        $this->filter->add(self::FILTER_VALUE);
        $this->filter->setData($data);
        if ($this->filter->isValid()) {
            return true;
        }

        return $this->filter->getMessages();
    }

    public function filterUserUpdate($data)
    {
        $this->filter->add(self::FILTER_ID);
        $this->filter->add(self::FILTER_VALUE);

        $this->filter->setData($data);
        if ($this->filter->isValid()) {
            return true;
        }

        return $this->filter->getMessages();
    }

    public function filterUserDelete($data)
    {
        $this->filter->add(self::FILTER_ID);

        $this->filter->setData($data);
        if ($this->filter->isValid()) {
            return true;
        }

        return $this->filter->getMessages();
    }

    public function filterUserEncrypt($data)
    {
        $this->filter->add(self::FILTER_VALUE);
        $this->filter->setData($data);
        if ($this->filter->isValid()) {
            return true;
        }

        return $this->filter->getMessages();
    }

    public function filterUserDecrypt($data)
    {
        $this->filter->add(self::FILTER_ENCRYPTED_VALUE);
        $this->filter->setData($data);
        if ($this->filter->isValid()) {
            return true;
        }

        return $this->filter->getMessages();
    }
}
