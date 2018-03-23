<?php

namespace App\Model;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use Aws\Sdk;

class AbstractModel
{
    /**
     * @var DynamoDbClient
     */
    public $dynamo;

    /**
     * @var Marshaler
     */
    public $marshaller;

    public function __construct($construct)
    {
        $sdk = new Sdk([
            'region'      => getenv('DYNAMO_REGION'),
            'version'     => getenv('DYNAMO_VERSION'),
            'credentials' => [
                'key'    => getenv('DYNAMO_KEY'),
                'secret' => getenv('DYNAMO_SECRET'),
            ]
        ]);

        $this->dynamo     = $sdk->createDynamoDb();
        $this->marshaller = new Marshaler();
    }

    public function get($tableName, array $key)
    {
        $params = [
            'TableName' => $tableName,
            'Key'       => $this->marshaller->marshalItem($key),
        ];

        $result = $this->dynamo->getItem($params);
        $data   = ($result['Item']) ? $this->marshaller->unmarshalItem($result['Item']) : [];

        return $data;
    }

    public function getCollection($tableName, $filter, array $data)
    {
        $eav = $ean = [];
        foreach ($data as $index => $value) {
            $eav[":${index}"] = $value;
            $ean["#{$index}"] = $index;
        }

        $params = [
            'TableName'                 => $tableName,
            'FilterExpression'          => $filter,
            'ExpressionAttributeNames'  => $ean,
            'ExpressionAttributeValues' => $this->marshaller->marshalItem($eav),
            'ReturnConsumedCapacity'    => 'TOTAL',
        ];

        $result = $this->dynamo->scan($params);

        $data = [];
        foreach ($result['Items'] as $item) {
            $data[] = $this->marshaller->unmarshalItem($item);
        }

        return $data;
    }

    public function put($tableName, array $data)
    {
        $params = [
            'TableName' => $tableName,
            'Item'      => $this->marshaller->marshalItem($data)
        ];

        $result = $this->dynamo->putItem($params);

        return $result;
    }

    /**
     * @param $tableName
     * @param array $key
     * @param array $data
     * @param array $condition
     * @return array|bool|\stdClass
     * @throws \Exception
     */
    public function update($tableName, array $key, array $data, array $condition = [])
    {
        $ue = $eav = $ean = [];
        foreach ($data as $index => $value) {
            $eav[":${index}"] = $value;
            $ean["#{$index}"] = $index;
            $ue[]             = "#{$index}=:${index}";
        }
        $ue = implode(', ', $ue);
        $ue = 'SET ' . $ue;

        if (! empty($condition)) {
            $ce = [];
            foreach ($condition as $index => $value) {
                $eav[":${index}"] = $value;
                $ean["#{$index}"] = $index;
                $ce[]             = "(#{$index}=:${index})";
            }
            $ce = implode(' and ', $ce);

            $params['ConditionExpression'] = $ce;
        }

        $params['TableName']                 = $tableName;
        $params['Key']                       = $this->marshaller->marshalItem($key);
        $params['UpdateExpression']          = $ue;
        $params['ExpressionAttributeNames']  = $ean;
        $params['ExpressionAttributeValues'] = $this->marshaller->marshalItem($eav);
        $params['ReturnValues']              = 'ALL_NEW';

        try {
            $result = $this->dynamo->updateItem($params);
            $update = ($result['Attributes']) ? $this->marshaller->unmarshalItem($result['Attributes']) : [];

            return $update;
        } catch (DynamoDbException $e) {
            if ($e->getAwsErrorCode() === 'ConditionalCheckFailedException') {
                return false;
            } else {
                throw new \Exception($e);
            }
        }
    }

    /**
     * @param $tableName
     * @param array $key
     * @param array $condition
     * @return bool
     * @throws \Exception
     */
    public function delete($tableName, array $key, array $condition = [])
    {
        $params = [
            'TableName' => $tableName,
            'Key'       => $this->marshaller->marshalItem($key),
        ];

        if (! empty($condition)) {
            $ce = $eav = $ean = [];
            foreach ($condition as $index => $value) {
                $eav[":${index}"] = $value;
                $ean["#{$index}"] = $index;
                $ce[]             = "(#{$index}=:${index})";
            }
            $ce = implode(' and ', $ce);

            $params['ExpressionAttributeNames']  = $ean;
            $params['ExpressionAttributeValues'] = $this->marshaller->marshalItem($eav);
            $params['ConditionExpression']       = $ce;
        }

        try {
            $this->dynamo->deleteItem($params);
        } catch (DynamoDbException $e) {
            if ($e->getAwsErrorCode() === 'ConditionalCheckFailedException') {
                return false;
            } else {
                throw new \Exception($e);
            }
        }

        return true;
    }
}
