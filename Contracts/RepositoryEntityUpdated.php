<?php

namespace MbRepository\Contracts;

use MbRepository\BaseRepository;
use MocaBonita\tools\eloquent\MbModel;

interface RepositoryEntityUpdated extends RepositoryEvent
{

    /**
     * RepositoryEvent constructor.
     *
     * @param BaseRepository $baseRepository
     * @param MbModel        $model
     * @param MbModel|null   $originalModel
     */
    public function apply(BaseRepository $baseRepository, MbModel $model, MbModel $originalModel);

    /**
     * @return MbModel
     */
    public function getOriginalModel();
}