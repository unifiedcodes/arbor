<?php

namespace Arbor\facades;

use Arbor\facades\Facade;
use Arbor\database\Database;

/**
 * Facade for the Arbor Database service.
 *
 * Provides a static interface to the underlying
 * Arbor\database\Database service instance.
 *
 * Example usage:
 * 
 * DB::select('users')->where('id', 1)->get();
 *
 * @method static \Arbor\database\QueryBuilder table(string $table)
 * @method static mixed query(string $sql, array $params = [])
 * @method static \PDO getPdo()
 * 
 */
class DB extends Facade
{
    /**
     * Get the service accessor string used to resolve the instance from the container.
     *
     * @return string
     */
    protected static function getAccessor(): string|object
    {
        return 'Arbor\\database\\DatabaseResolver';
    }


    public static function on(string $name): Database
    {
        /** @var DatabaseResolver $resolver */
        $instance = static::resolveInstance();
        return $instance->get($name);
    }


    public static function __callStatic($method, $args)
    {
        $db = static::on('default');
        return $db->$method(...$args);
    }
}
