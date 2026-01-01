<?php

namespace Musonza\Chat\Transformers;

use League\Fractal;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\ArraySerializer;

abstract class Transformer extends Fractal\TransformerAbstract
{
    public function transformCollection($items, $paginator = null, $meta = null)
    {
        $resource = new Collection($items, $this);

        if ($paginator) {
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        }

        if ($meta) {
            $resource->setMeta($meta);
        }

        return $this->fractalManager($resource);
    }

    public function fractalManager($resource)
    {
        $fractal = new Manager;

        $fractal->setSerializer(new ArraySerializer);

        if ($includes = request('include', null)) {
            $fractal->parseIncludes($includes);
        }

        return $fractal->createData($resource)->toArray();
    }

    public function transformItem($item)
    {
        $resource = new Item($item, $this);

        return $this->fractalManager($resource);
    }

    abstract public function transform($item);
}
