<?php

namespace App\Action;

use App\Filter\SafeBoxFilter;
use App\Model\SafeBoxModel;
use Fig\Http\Message\StatusCodeInterface;
use Zend\Crypt\BlockCipher;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;

class SafeBoxAction extends AbstractAction
{
    /**
     * @var SafeBoxModel
     */
    public $dynamo;
    /**
     * @var BlockCipher
     */
    public $blockCipher;
    /**
     * @var SafeBoxFilter
     */
    public $filter;

    /**
     * @param ServerRequestInterface $request
     * @return EmptyResponse|JsonResponse
     */
    public function safeBoxRead(ServerRequestInterface $request)
    {
        $name = $request->getAttribute('name');

        if ($name) {
            $filtered = $this->filter->filterSafeBoxRead(['name' => $name]);
            if (is_array($filtered)) {
                return new JsonResponse($filtered, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
            }

            $data = $this->dynamo->get('SAFE_BOX', ['safe_box_id' => $this->primaryKey($name)]);
            if ($data) {
                if ($this->user['root']) {
                    if ($data['user_id'] != $this->user['user_id']) {
                        return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
                    }
                } else {
                    if ($data['user_id'] != $this->user['user_id'] || $data['username'] != $this->user['username']) {
                        return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
                    }
                }
                $data['value'] = $this->blockCipher->decrypt($data['value']);
                return new JsonResponse($data);
            } else {
                return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
            }
        } else {
            if ($this->user['root']) {
                $data = $this->dynamo->getCollection(
                    'SAFE_BOX',
                    '#user_id = :user_id',
                    ['user_id' => $this->user['user_id']]
                );
            } else {
                $data = $this->dynamo->getCollection(
                    'SAFE_BOX',
                    '#username = :username',
                    ['username' => $this->user['username']]
                );
            }
            foreach ($data as &$current) {
                $current['value'] = $this->blockCipher->decrypt($current['value']);
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function safeBoxCreate(ServerRequestInterface $request): JsonResponse
    {
        $body = (array)json_decode($request->getBody()->getContents());

        $filtered = $this->filter->filterSafeBoxCreate($body);
        if (is_array($filtered)) {
            return new JsonResponse($filtered, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $data['user_id']     = $this->user['user_id'];
        $data['name']        = $body['name'];
        $data['safe_box_id'] = $this->primaryKey($body['name']);
        $data['value']       = $this->blockCipher->encrypt($body['value']);
        if (! $this->user['root']) {
            $data['username'] = $this->user['username'];
        }
        $data['create_time'] = (new \DateTime)->format(DATE_ATOM);
        $data['update_time'] = $data['create_time'];

        $this->dynamo->put('SAFE_BOX', $data);

        $location = $this->headerLocation("safe-box/{$data['safe_box_id']}");

        return new JsonResponse($data, StatusCodeInterface::STATUS_CREATED, ['Location' => $location]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse | EmptyResponse
     * @throws \Exception
     */
    public function safeBoxUpdate(ServerRequestInterface $request)
    {
        $name         = $request->getAttribute('name');
        $primaryKey   = $this->primaryKey($name);
        $body         = (array)json_decode($request->getBody()->getContents());
        $body['name'] = $name;

        $filtered = $this->filter->filterSafeBoxUpdate($body);
        if (is_array($filtered)) {
            return new JsonResponse($filtered, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        unset($body['name']);

        $condition['user_id'] = $this->user['user_id'];
        if (! $this->user['root']) {
            $condition['username'] = $this->user['username'];
        }
        $body['value']       = $this->blockCipher->encrypt($body['value']);
        $body['update_time'] = (new \DateTime)->format(DATE_ATOM);

        $data = $this->dynamo->update('SAFE_BOX', ['safe_box_id' => $primaryKey], $body, $condition);
        if ($data === false) {
            return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
        }

        $location = $this->headerLocation("safe-box/{$primaryKey}");

        return new JsonResponse($data, StatusCodeInterface::STATUS_OK, ['Location' => $location]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return EmptyResponse | JsonResponse
     * @throws \Exception
     */
    public function safeBoxDelete(ServerRequestInterface $request)
    {
        $name = $request->getAttribute('name');

        $filtered = $this->filter->filterSafeBoxDelete(['name' => $name]);
        if (is_array($filtered)) {
            return new JsonResponse($filtered, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $condition['user_id'] = $this->user['user_id'];
        if (! $this->user['root']) {
            $condition['username'] = $this->user['username'];
        }

        $result = $this->dynamo->delete('SAFE_BOX', ['safe_box_id' => $this->primaryKey($name)], $condition);

        return new EmptyResponse(
            ($result) ? StatusCodeInterface::STATUS_NO_CONTENT : StatusCodeInterface::STATUS_NOT_FOUND
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function safeBoxEncrypt(ServerRequestInterface $request): JsonResponse
    {
        $body = (array)json_decode($request->getBody()->getContents());

        $filtered = $this->filter->filterSafeBoxEncrypt($body);
        if (is_array($filtered)) {
            return new JsonResponse($filtered, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $data['value'] = $this->blockCipher->encrypt($body['value']);

        return new JsonResponse($data);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function safeBoxDecrypt(ServerRequestInterface $request): JsonResponse
    {
        $body = (array)json_decode($request->getBody()->getContents());

        $filtered = $this->filter->filterSafeBoxDecrypt($body);
        if (is_array($filtered)) {
            return new JsonResponse($filtered, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $data['value'] = $this->blockCipher->decrypt($body['value']);

        return new JsonResponse($data);
    }

    /**
     * Generate a primary key for safe box table
     *
     * @param $name
     * @return string
     */
    private function primaryKey($name): string
    {
        $name = base64_encode($name);
        $key  = "safe-{$name}-{$this->user['user_id']}";
        if (! $this->user['root']) {
            $key .= "-{$this->user['username']}";
        }

        return $key;
    }
}
