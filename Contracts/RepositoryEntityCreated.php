<?php

namespace MbRepository\Contracts;

use MbRepository\BaseRepository;
use MocaBonita\tools\eloquent\MbModel;

/**
 * Interface RepositoryEntityCreated
 *
 * @package MbRepository\Contracts
 */
interface RepositoryEntityCreated extends RepositoryEvent
{
    /**
     * RepositoryEntityCreated constructor.
     *
     * @param BaseRepository $baseRepository
     * @param MbModel        $model
     */
    public function apply(BaseRepository $baseRepository, MbModel $model);
}