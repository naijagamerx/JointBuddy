<?php
/**
 * Handler Interface
 * All request handlers must implement this interface
 *
 * @package CannaBuddy
 */
interface HandlerInterface {
    /**
     * Check if this handler can process the request
     *
     * @param string $route Current route from route.php
     * @param array $request Aggregated request data (POST + GET + FILES)
     * @return bool True if handler can process this route
     */
    public function canHandle(string $route, array $request): bool;

    /**
     * Process the request
     *
     * @param string $route Current route from route.php
     * @param array $request Aggregated request data (POST + GET + FILES)
     * @return void
     * @throws HandlerException If processing fails
     */
    public function handle(string $route, array $request): void;
}
