<?php

namespace MbRepository;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use MbRepository\Contracts\RepositoryEntityCreated;
use MbRepository\Contracts\RepositoryEntityDeleted;
use MbRepository\Contracts\RepositoryEntityUpdated;
use MbRepository\Contracts\RepositoryEvent;
use MbRepository\Contracts\RepositoryInterface;
use MocaBonita\tools\eloquent\MbModel;
use MocaBonita\tools\MbException;

/**
 * Class BaseRepository
 *
 * @package MbRepository\Repository
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @var RepositoryEvent
     */
    protected $event;

    /**
     * @var RepositoryEntityCreated
     */
    protected $eventCreated;

    /**
     * @var RepositoryEntityUpdated
     */
    protected $eventUpdated;

    /**
     * @var RepositoryEntityDeleted
     */
    protected $eventDeleted;

    /**
     * @var MbModel
     */
    protected $model;

    /**
     * @var \Closure
     */
    protected $scopeQuery = null;

    /**
     *
     * @throws MbException
     */
    public function __construct()
    {
        $this->makeModel();
        $this->boot();
    }

    /**
     * @return RepositoryEvent
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return RepositoryEntityCreated
     */
    public function getEventCreated()
    {
        return $this->eventCreated;
    }

    /**
     * @param RepositoryEntityCreated $eventCreated
     *
     * @return BaseRepository
     */
    public function setEventCreated(RepositoryEntityCreated $eventCreated)
    {
        $this->eventCreated = $eventCreated;

        return $this;
    }

    /**
     * @return RepositoryEntityUpdated
     */
    public function getEventUpdated()
    {
        return $this->eventUpdated;
    }

    /**
     * @param RepositoryEntityUpdated $eventUpdated
     *
     * @return BaseRepository
     */
    public function setEventUpdated(RepositoryEntityUpdated $eventUpdated)
    {
        $this->eventUpdated = $eventUpdated;

        return $this;
    }

    /**
     * @return RepositoryEntityDeleted
     */
    public function getEventDeleted()
    {
        return $this->eventDeleted;
    }

    /**
     * @param RepositoryEntityDeleted $eventDeleted
     *
     * @return BaseRepository
     */
    public function setEventDeleted(RepositoryEntityDeleted $eventDeleted)
    {
        $this->eventDeleted = $eventDeleted;

        return $this;
    }

    /**
     * @return MbModel
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param MbModel $model
     *
     * @return BaseRepository
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @return Closure
     */
    public function getScopeQuery()
    {
        return $this->scopeQuery;
    }

    /**
     * @param Closure $scopeQuery
     *
     * @return BaseRepository
     */
    public function setScopeQuery($scopeQuery)
    {
        $this->scopeQuery = $scopeQuery;

        return $this;
    }

    /**
     *
     */
    public function boot()
    {
        //
    }

    /**
     * @param RepositoryEvent $event
     * @param MbModel         $model
     * @param MbModel|null    $originalModel
     *
     * @return $this
     */
    protected function event($event, MbModel $model, MbModel $originalModel = null)
    {
        if ($event instanceof RepositoryEntityCreated || $event instanceof RepositoryEntityDeleted) {
            $this->event = $event->apply($this, $model);
            $this->event->dispatcher();
        } elseif ($event instanceof RepositoryEntityUpdated) {
            $this->event = $event->apply($this, $model, $originalModel);
            $this->event->dispatcher();
        }

        return $this;
    }

    /**
     * @throws MbException
     */
    public function resetModel()
    {
        $this->makeModel();
    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    abstract public function model();

    /**
     * @return MbModel
     *
     * @throws MbException
     */
    public function makeModel()
    {
        $className = $this->model();
        $model = new $className();

        if (!$model instanceof MbModel) {
            throw new MbException("Class {$this->model()} must be an instance of MocaBonita\\tools\\eloquent\\MbModel");
        }

        return $this->model = $model;
    }

    /**
     * Query Scope
     *
     * @param \Closure $scope
     *
     * @return $this
     */
    public function scopeQuery(\Closure $scope)
    {
        $this->scopeQuery = $scope;

        return $this;
    }

    /**
     * Retrieve data array for populate field select
     * Compatible with Laravel 5.3
     *
     * @param string      $column
     * @param string|null $key
     *
     * @return \Illuminate\Support\Collection|array
     */
    public function pluck($column, $key = null)
    {
        return $this->model->pluck($column, $key);
    }

    /**
     * Sync relations
     *
     * @param      $id
     * @param      $relation
     * @param      $attributes
     * @param bool $detaching
     *
     * @return mixed
     * @throws MbException
     */
    public function sync($id, $relation, $attributes, $detaching = true)
    {
        return $this->find($id)->{$relation}()->sync($attributes, $detaching);
    }

    /**
     * SyncWithoutDetaching
     *
     * @param $id
     * @param $relation
     * @param $attributes
     *
     * @return mixed
     * @throws MbException
     */
    public function syncWithoutDetaching($id, $relation, $attributes)
    {
        return $this->sync($id, $relation, $attributes, false);
    }

    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|MbModel[]
     *
     * @throws MbException
     */
    public function all($columns = ['*'])
    {
        $this->applyScope();

        if ($this->model instanceof Builder) {
            $results = $this->model->get($columns);
        } else {
            $results = $this->model->all($columns);
        }

        $this->resetModel();
        $this->resetScope();

        return $results;
    }

    /**
     * Alias of All method
     *
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|MbModel[]
     *
     * @throws MbException
     */
    public function get($columns = ['*'])
    {
        return $this->all($columns);
    }


    /**
     * Retrieve first data of repository
     *
     * @param array $columns
     *
     * @return MbModel
     *
     * @throws MbException
     */
    public function first($columns = ['*'])
    {
        $this->applyScope();

        $results = $this->model->first($columns);

        $this->resetModel();

        return $results;
    }

    /**
     * Retrieve first data of repository, or return new Entity
     *
     * @param array $attributes
     *
     * @return MbModel
     *
     * @throws MbException
     */
    public function firstOrNew(array $attributes = [])
    {
        $this->applyScope();

        $model = $this->model->firstOrNew($attributes);

        $this->resetModel();

        return $model;
    }

    /**
     * Retrieve first data of repository, or create new Entity
     *
     * @param array $attributes
     *
     * @return MbModel
     *
     * @throws MbException
     */
    public function firstOrCreate(array $attributes = [])
    {
        $this->applyScope();

        $model = $this->model->firstOrCreate($attributes);

        $this->resetModel();

        return $model;
    }

    /**
     * Retrieve all data of repository, paginated
     *
     * @param null   $limit
     * @param array  $columns
     * @param string $method
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator | \Illuminate\Pagination\Paginator
     *
     * @throws MbException
     */
    public function paginate($limit = null, $columns = ['*'], $method = "paginate")
    {
        $this->applyScope();
        $limit = is_null($limit) ? 15 : $limit;
        $results = $this->model->{$method}($limit, $columns);
        $this->resetModel();

        return $results;
    }

    /**
     * Retrieve all data of repository, simple paginated
     *
     * @param null  $limit
     * @param array $columns
     *
     * @return \Illuminate\Pagination\Paginator
     *
     * @throws MbException
     */
    public function simplePaginate($limit = null, $columns = ['*'])
    {
        return $this->paginate($limit, $columns, "simplePaginate");
    }

    /**
     * Find data by id
     *
     * @param       $id
     * @param array $columns
     *
     * @return MbModel
     *
     * @throws MbException
     */
    public function find($id, $columns = ['*'])
    {
        $this->applyScope();
        $model = $this->model->findOrFail($id, $columns);
        $this->resetModel();

        return $model;
    }

    /**
     * Find data by field and value
     *
     * @param       $field
     * @param       $value
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|MbModel[]
     * @throws MbException
     */
    public function findByField($field, $value = null, $columns = ['*'])
    {
        $this->applyScope();
        $model = $this->model->where($field, '=', $value)->get($columns);
        $this->resetModel();

        return $model;
    }

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|MbModel[]
     * @throws MbException
     */
    public function findWhere(array $where, $columns = ['*'])
    {
        $this->applyScope();

        $this->applyConditions($where);

        $model = $this->model->get($columns);
        $this->resetModel();

        return $model;
    }

    /**
     * Find data by multiple values in one field
     *
     * @param       $field
     * @param array $values
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|MbModel[]
     * @throws MbException
     */
    public function findWhereIn($field, array $values, $columns = ['*'])
    {
        $this->applyScope();
        $model = $this->model->whereIn($field, $values)->get($columns);
        $this->resetModel();

        return $model;
    }

    /**
     * Find data by excluding multiple values in one field
     *
     * @param       $field
     * @param array $values
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|MbModel[]
     * @throws MbException
     */
    public function findWhereNotIn($field, array $values, $columns = ['*'])
    {
        $this->applyScope();
        $model = $this->model->whereNotIn($field, $values)->get($columns);
        $this->resetModel();

        return $model;
    }

    /**
     * Save a new entity in repository
     *
     *
     * @param array $attributes
     *
     * @return MbModel
     *
     * @throws MbException
     */
    public function create(array $attributes)
    {
        $model = $this->model->newInstance($attributes);
        $model->save();
        $this->resetModel();

        $this->event($this->getEventCreated(), $model);

        return $model;
    }

    /**
     * Update a entity in repository by id
     *
     * @param array $attributes
     * @param       $id
     *
     * @return MbModel
     *
     * @throws MbException
     */
    public function update(array $attributes, $id)
    {
        $this->applyScope();

        $model = $this->model->findOrFail($id);
        $originalModel = clone $model;
        $model->fill($attributes);
        $model->save();

        $this->resetModel();

        $this->event($this->getEventUpdated(), $model, $originalModel);

        return $model;
    }

    /**
     * Update or Create an entity in repository
     *
     * @param array $attributes
     * @param array $values
     *
     * @return MbModel
     * @throws MbException
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        $this->applyScope();

        $model = $this->model->updateOrCreate($attributes, $values);

        $this->resetModel();

        $this->event($this->getEventUpdated(), $model);

        return $model;
    }

    /**
     * Delete a entity in repository by id
     *
     * @param $id
     *
     * @return int
     * @throws MbException
     * @throws Exception
     */
    public function delete($id)
    {
        $this->applyScope();

        $model = $this->find($id);
        $originalModel = clone $model;
        $this->resetModel();

        $deleted = $model->delete();

        $this->event($this->getEventDeleted(), $originalModel);

        return $deleted;
    }

    /**
     * Delete multiple entities by given criteria.
     *
     * @param array $where
     *
     * @return int
     * @throws MbException
     * @throws Exception
     */
    public function deleteWhere(array $where)
    {
        $this->applyScope();

        $this->applyConditions($where);

        $deleted = $this->model->delete();

        $this->resetModel();

        $this->event($this->getEventDeleted(), $this->model->getModel());

        return $deleted;
    }

    /**
     * Check if entity has relation
     *
     * @param string $relation
     *
     * @return $this
     */
    public function has($relation)
    {
        $this->model = $this->model->has($relation);

        return $this;
    }

    /**
     * Load relations
     *
     * @param array|string $relations
     *
     * @return $this
     */
    public function with($relations)
    {
        $this->model = $this->model->with($relations);

        return $this;
    }

    /**
     * Add subselect queries to count the relations.
     *
     * @param  mixed $relations
     *
     * @return $this
     */
    public function withCount($relations)
    {
        $this->model = $this->model->withCount($relations);

        return $this;
    }

    /**
     * Load relation with closure
     *
     * @param string  $relation
     * @param closure $closure
     *
     * @return $this
     */
    public function whereHas($relation, Closure $closure)
    {
        $this->model = $this->model->whereHas($relation, $closure);

        return $this;
    }

    /**
     * Set hidden fields
     *
     * @param array $fields
     *
     * @return $this
     */
    public function hidden(array $fields)
    {
        $this->model->setHidden($fields);

        return $this;
    }

    /**
     * @param string $column
     * @param string $direction
     *
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->model = $this->model->orderBy($column, $direction);

        return $this;
    }

    /**
     * Set visible fields
     *
     * @param array $fields
     *
     * @return $this
     */
    public function visible(array $fields)
    {
        $this->model->setVisible($fields);

        return $this;
    }


    /**
     * Reset Query Scope
     *
     * @return $this
     */
    public function resetScope()
    {
        $this->scopeQuery = null;

        return $this;
    }

    /**
     * Apply scope in current Query
     *
     * @return $this
     */
    protected function applyScope()
    {
        if (isset($this->scopeQuery) && is_callable($this->scopeQuery)) {
            $callback = $this->scopeQuery;
            $this->model = $callback($this->model);
        }

        return $this;
    }

    /**
     * Applies the given where conditions to the model.
     *
     * @param array $where
     *
     * @return void
     */
    protected function applyConditions(array $where)
    {
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                list($field, $condition, $val) = $value;
                $this->model = $this->model->where($field, $condition, $val);
            } else {
                $this->model = $this->model->where($field, '=', $value);
            }
        }
    }
}