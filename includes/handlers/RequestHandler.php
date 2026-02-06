<?php
/**
 * Request Handler (Dispatcher)
 * Main dispatcher that routes POST requests to appropriate handlers
 *
 * @package CannaBuddy
 */
class RequestHandler {

    /** @var HandlerInterface[] Registered handlers */
    private array $handlers = [];

    /**
     * Constructor - registers all handlers
     */
    public function __construct() {
        $this->registerHandler(new AuthHandler());
        $this->registerHandler(new WishlistHandler());
    }

    /**
     * Register a handler
     *
     * @param HandlerInterface $handler Handler to register
     * @return void
     */
    private function registerHandler(HandlerInterface $handler): void {
        $this->handlers[] = $handler;
    }

    /**
     * Handle POST request
     *
     * @param string $route Current route
     * @return bool True if handled, false if no handler found
     */
    public function handlePost(string $route): bool {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }

        // Validate CSRF for all POST requests
        CsrfMiddleware::validate();

        // Aggregate request data
        $request = array_merge($_POST, $_GET, $_FILES);

        // Find and execute matching handler
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($route, $request)) {
                try {
                    $handler->handle($route, $request);
                    return true;
                } catch (HandlerException $e) {
                    error_log("Handler error: " . $e->getMessage());
                    if ($e->getContext()) {
                        error_log("Handler context: " . json_encode($e->getContext()));
                    }
                    $_SESSION['handler_error'] = $e->getMessage();
                    return true;
                }
            }
        }

        return false;
    }
}
