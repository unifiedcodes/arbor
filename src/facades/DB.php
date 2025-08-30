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
     * This method returns the service identifier that will be used by the
     * container to resolve the database service instance. The facade pattern
     * uses this accessor to lazy-load the underlying service only when needed.
     *
     * @return string The service accessor string for the DatabaseResolver
     */
    protected static function getAccessor(): string|object
    {
        return 'Arbor\\database\\DatabaseResolver';
    }

    /**
     * Get a named database connection instance.
     *
     * This method allows you to retrieve a specific database connection
     * by name, enabling support for multiple database connections within
     * the same application. The connection name should correspond to a
     * configuration defined in your database configuration.
     *
     * @param string $name The name of the database connection to retrieve
     * @return Database The database instance for the specified connection
     * 
     * @example
     * // Get a specific database connection
     * $userDb = DB::on('users_db');
     * $logDb = DB::on('logs_db');
     */
    public static function on(string $name): Database
    {
        /** @var DatabaseResolver $resolver */
        $instance = static::resolveInstance();
        return $instance->get($name);
    }

    /**
     * Handle dynamic static method calls to the default database instance.
     *
     * This magic method intercepts all static method calls that are not
     * explicitly defined in this class and forwards them to the default
     * database instance. This enables the facade to provide a clean static
     * interface while delegating the actual work to the underlying database
     * service.
     *
     * @param string $method The name of the method being called
     * @param array $args The arguments passed to the method
     * @return mixed The result of the method call on the database instance
     * 
     * @example
     * // These calls are forwarded to the default database instance:
     * DB::table('users')->where('active', 1)->get();
     * DB::query('SELECT * FROM users WHERE id = ?', [1]);
     * DB::beginTransaction();
     */
    public static function __callStatic($method, $args)
    {
        // get default database object.
        $instance = static::resolveInstance();
        $db = $instance->get();

        return $db->$method(...$args);
    }
}
