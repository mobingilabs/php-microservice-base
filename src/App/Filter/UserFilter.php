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
                        'min' => 4,
                        'max' => 18,
                    ],
                ],
                [
                    'name'    => 'Regex',
                    'options' => [
                        'pattern' => '/^[a-zA-Z0-9_.-]+$/',
                        'message' => "It is only allowed 'letters', 'numbers', '_', '.', '-'",
                    ],
                ],
            ],
        ];
    const FILTER_USERNAME
        = [
            'name'       => 'username',
            'required'   => true,
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'min' => 4,
                        'max' => 18,
                    ],
                ],
                [
                    'name'    => 'Regex',
                    'options' => [
                        'pattern' => '/^[a-zA-Z0-9_.-]+$/',
                        'message' => "It is only allowed 'letters', 'numbers', '_', '.', '-'",
                    ],
                ],
            ],
        ];
    const FILTER_PASSWORD
        = [
            'name'       => 'password',
            'required'   => true,
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'min' => 6,
                        'max' => 18,
                    ],
                ],
            ],
        ];
    const FILTER_PASSWORD_OPTIONAL
        = [
            'name'       => 'password',
            'required'   => false,
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'min' => 6,
                        'max' => 18,
                    ],
                ],
            ],
        ];
    const FILTER_EMAIL
        = [
            'name'       => 'email',
            'required'   => false,
            'validators' => [
                [
                    'name'    => 'EmailAddress',
                    'options' => [
                        'allow'          => \Zend\Validator\Hostname::ALLOW_DNS,
                        'useMxCheck'     => true,
                        'useDeepMxCheck' => true,
                    ],
                ],
            ],
        ];
    const FILTER_NOTIFICATION_EMAIL
        = [
            'name'       => 'email',
            'required'   => true,
            'validators' => [
                [
                    'name'    => 'InArray',
                    'options' => [
                        'haystack' => ['true', 'false'],
                        'message'  => "It can only be 'true' or 'false'",
                    ],
                ],
            ],
        ];
    const FILTER_NOTIFICATION_EMAIL_OPTIONAL
        = [
            'name'       => 'email',
            'required'   => false,
            'validators' => [
                [
                    'name'    => 'InArray',
                    'options' => [
                        'haystack' => ['true', 'false'],
                        'message'  => "It can only be 'true' or 'false'",
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
        $this->filter->add(self::FILTER_USERNAME);
        $this->filter->add(self::FILTER_PASSWORD);
        $this->filter->add(self::FILTER_EMAIL);

        $notificationFilter = new InputFilter();
        $notificationFilter->add(self::FILTER_NOTIFICATION_EMAIL);

        $this->filter->add($notificationFilter, 'notification');
        $this->filter->setData($data);
        if ($this->filter->isValid()) {
            return true;
        }

        return $this->filter->getMessages();
    }

    public function filterUserUpdate($data)
    {
        $this->filter->add(self::FILTER_ID);
        $this->filter->add(self::FILTER_PASSWORD_OPTIONAL);
        $this->filter->add(self::FILTER_EMAIL);

        $notificationFilter = new InputFilter();
        $notificationFilter->add(self::FILTER_NOTIFICATION_EMAIL_OPTIONAL);

        $this->filter->add($notificationFilter, 'notification');
        $this->filter->setData($data);
        if ($this->filter->isValid()) {
            return true;
        }

        return $this->filter->getMessages();
    }

    public function filterUserDelete($data)
    {
        $this->filter->add(self::FILTER_USERNAME);

        $this->filter->setData($data);
        if ($this->filter->isValid()) {
            return true;
        }

        return $this->filter->getMessages();
    }
}
