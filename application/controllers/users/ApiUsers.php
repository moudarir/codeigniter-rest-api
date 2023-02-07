<?php

use Firebase\JWT\JWT;
use Moudarir\CodeigniterApi\Helpers\ArrayHelper;
use Moudarir\CodeigniterApi\Models\Users\User;

/**
 * @property ApiUsers
 */
class ApiUsers extends CoreServer
{

    /**
     * ApiUsers constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see ApiUsers::indexGet()
     */
    public function indexGet()
    {
        $id = $this->get('id');
        $with = $this->get('with');
        $options = ArrayHelper::formatApiWith($with);
        $entity = new User();

        if ($id !== null) {
            if ((int)$id <= 0) {
                self::getResponse()->badRequest();
            }

            $item = $entity->find($id, $options);

            if ($item === null) {
                self::getResponse()->notFound();
            }

            self::getResponse()->ok(['item' => $item->normalize()]);
        }

        $total = $entity->count($options);
        $options['page'] = $this->get('page');
        $options['limit'] = $this->get('limit');
        $items = $entity->collect($options)->toArray();
        $response = [
            'total' => $total,
            'items' => $total === 0 ? [] : $entity->normalizeAll($items),
        ];

        if ($options['page'] !== null) {
            $response['page'] = (int)$options['page'] === 0 ? 1 : (int)$options['page'];
        }

        self::getResponse()->ok($response);
    }

    /**
     * @see ApiUsers::indexPost()
     */
    public function indexPost()
    {
        $secret = getenv("JWT_SECRET");
        $payload = [
            'iss' => 'http://example.org',
            'aud' => 'http://example.com',
            'iat' => 1356999524,
            'nbf' => 1357000000,
            'exp' => time() + (60 * 60),
            'user' => [
                'id' => $this->getApiKey()->getUserId()
            ]
        ];
        self::getResponse()->ok([
            'data' => [
                'jwt_key' => JWT::encode($payload, $secret, 'HS256'),
                'user_id' => $this->getApiKey()->getUserId()
            ]
        ]);
    }

    /**
     * @see ApiUsers::indexPut()
     */
    public function indexPut()
    {
        $args = [$this->post(), $this->put('firstname'), $this->get()];
        self::getResponse()->ok(['data' => $args]);
    }
}
