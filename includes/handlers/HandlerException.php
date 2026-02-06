<?php
/**
 * Handler Exception
 * Thrown when handler processing fails
 *
 * @package CannaBuddy
 */
class HandlerException extends Exception {
    protected ?array $context = null;

    /**
     * HandlerException constructor
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param Throwable|null $previous Previous exception
     * @param array|null $context Additional context for debugging
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,
        ?array $context = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get debug context
     *
     * @return array|null Context data or null
     */
    public function getContext(): ?array {
        return $this->context;
    }
}
