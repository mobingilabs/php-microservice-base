<?php

namespace App\Model;

use Aws\DynamoDb\DynamoDbClient;
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

    public function update($tableName, array $key, array $data)
    {
        $temp = $this->marshaller->marshalItem($data);

        $ue  = 'SET';
        $eav = $ean = [];
        foreach ($temp as $index => $value) {
            $eav[":${index}"] = $value;
            $ean["#{$index}"] = $index;
            $ue               .= " #{$index}=:${index}";
        }

        $params = [
            'TableName'                 => $tableName,
            'Key'                       => $this->marshaller->marshalItem($key),
            'UpdateExpression'          => $ue,
            'ExpressionAttributeNames'  => $ean,
            'ExpressionAttributeValues' => $eav,
            'ReturnValues'              => 'ALL_NEW'
        ];

        $result = $this->dynamo->updateItem($params);

        $update = ($result['Attributes']) ? $this->marshaller->unmarshalItem($result['Attributes']) : [];

        return $update;
    }

    public function delete($tableName, array $key)
    {
        $params = [
            'TableName' => $tableName,
            'Key'       => $this->marshaller->marshalItem($key),
        ];
        $result = $this->dynamo->deleteItem($params);

        return $result;
    }
}
