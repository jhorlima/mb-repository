<?php

namespace MbRepository\Contracts;

use MbRepository\BaseRepository;
use MocaBonita\tools\eloquent\MbModel;

/**
 * Interface RepositoryEvent
 *
 * @package MbRepository\Contracts
 */
interface RepositoryEvent
{

    /**
     * @return BaseRepository
     */
    public function getRepository();

    /**
     * @return MbModel
     */
    public function getModel();

    /**
     * @return mixed|null
     */
    public function dispatcher();
}