<?php
/**
 * Authentication Handler
 * Handles: User registration, login, logout
 *
 * @package CannaBuddy
 */
class AuthHandler implements HandlerInterface {

    /**
     * Check if this handler can process the request
     *
     * @param string $route Current route from route.php
     * @param array $request Aggregated request data
     * @return bool True if handler can process this route
     */
    public function canHandle(string $route, array $request): bool {
        // Admin logout can be GET or POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $route === 'admin/logout';
        }

        $authRoutes = ['register', 'user/login', 'admin/logout'];
        return in_array($route, $authRoutes, true);
    }

    /**
     * Process the request
     *
     * @param string $route Current route from route.php
     * @param array $request Aggregated request data
     * @return void
     * @throws HandlerException If processing fails
     */
    public function handle(string $route, array $request): void {
        $userAuth = Services::userAuth();
        $adminAuth = Services::adminAuth();

        switch ($route) {
            case 'register':
                $this->handleRegister($userAuth, $request);
                break;
            case 'user/login':
                $this->handleUserLogin($userAuth, $request);
                break;
            case 'admin/logout':
                $this->handleAdminLogout($adminAuth);
                break;
            default:
                throw new HandlerException("Unsupported auth route: {$route}");
        }
    }

    /**
     * Handle user registration
     *
     * @param UserAuth|null $userAuth User auth service
     * @param array $request Request data
     * @return void
     */
    private function handleRegister(?UserAuth $userAuth, array $request): void {
        if (!$userAuth) {
            throw new HandlerException("Authentication service unavailable");
        }

        $userData = [
            'email' => trim($request['email'] ?? ''),
            'password' => $request['password'] ?? '',
            'first_name' => trim($request['first_name'] ?? ''),
            'last_name' => trim($request['last_name'] ?? ''),
            'phone' => $request['phone'] ?? null
        ];

        $result = $userAuth->register($userData);

        if ($result['success'] ?? false) {
            $_SESSION['registration_success'] = $result['message'] ?? 'Registration successful!';
        } else {
            $_SESSION['registration_error'] = $result['message'] ?? 'Registration failed.';
        }
    }

    /**
     * Handle user login
     *
     * @param UserAuth|null $userAuth User auth service
     * @param array $request Request data
     * @return void
     */
    private function handleUserLogin(?UserAuth $userAuth, array $request): void {
        if (!$userAuth) {
            throw new HandlerException("Authentication service unavailable");
        }

        $result = $userAuth->login(
            trim($request['email'] ?? ''),
            $request['password'] ?? ''
        );

        if ($result['success'] ?? false) {
            header('Location: ' . url('user/'));
            exit;
        } else {
            $_SESSION['user_login_error'] = $result['message'] ?? 'Login failed.';
        }
    }

    /**
     * Handle admin logout
     *
     * @param AdminAuth|null $adminAuth Admin auth service
     * @return void
     */
    private function handleAdminLogout(?AdminAuth $adminAuth): void {
        if ($adminAuth) {
            $adminAuth->logout();
        }
        header('Location: ' . adminUrl('login/'));
        exit;
    }
}
