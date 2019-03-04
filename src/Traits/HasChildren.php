<?php

namespace Wdgt\NestedForm\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasChildren
{

    /**
     * The related children.
     *
     * @var array
     */
    protected $children;

    /**
     * Set the related children.
     *
     * @return self
     */
    public function setChildren(Model $resource)
    {
        //se la risorsa è ordinabile con spatie/eloquent-sortable: prendo le risorse ordinate per la colonna d'ordine
        $relatedClassName = get_class($resource->{$this->viaRelationship}()->getRelated());
        $relatedClassInstance = new $relatedClassName;
        $sortable_column = $relatedClassInstance->sortable['order_column_name'] ?? null;

        if($sortable_column != null) 
            $children = $resource->{$this->viaRelationship}()->orderBy($sortable_column)->get();
        else 
            $children = $resource->{$this->viaRelationship}()->get();
        

        return $this->withMeta([
            'children' => $children->map(function ($item, $index) {
                return [
                    'fields' => $this->getFields('showOnUpdate', $item, $index),
                    'heading' => $this->getHeading(),
                    'opened' => $this->meta['opened'] ?? false,
                    'attribute' => $this->attribute . '[' . $index . ']',
                    self::ID => $item->id,
                ];
            }),
        ]);
        
    }
}
