<?php
namespace Api\V8\Manufacturing\Param;

use Api\V8\Param\BaseParam;

/**
 * Parameters for GET /products endpoint
 */
class GetProductsParams extends BaseParam
{
    /**
     * Get parameter rules
     *
     * @return array
     */
    public function getRules(): array
    {
        return [
            'page' => [
                'optional' => true,
                'type' => 'integer',
                'min' => 1,
                'default' => 1
            ],
            'limit' => [
                'optional' => true,
                'type' => 'integer',
                'min' => 1,
                'max' => 100,
                'default' => 20
            ],
            'search' => [
                'optional' => true,
                'type' => 'string',
                'max_length' => 255
            ],
            'category' => [
                'optional' => true,
                'type' => 'string',
                'max_length' => 100
            ],
            'in_stock' => [
                'optional' => true,
                'type' => 'boolean'
            ]
        ];
    }
}
