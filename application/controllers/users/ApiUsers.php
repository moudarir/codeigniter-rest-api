<?php

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
     * @see ApiUsers::loginPost()
     */
    public function loginPost()
    {
        self::getResponse()->ok(['data' => $this->getAuthData()]);
    }
}
