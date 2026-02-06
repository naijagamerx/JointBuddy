<?php
// Include error handler
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/url_helper.php';

function renderUserLoginPage() {
    // Demo credentials for testing
    $demoEmail = 'user@example.com';
    $demoPassword = 'user123';

    return '<body style="
        margin: 0;
        padding: 0;
        min-height: 100vh;
        background: linear-gradient(135deg, rgba(0,0,0,0.7), rgba(0,0,0,0.5)),
        url(\'https://picsum.photos/seed/cannabuddy-user/1920/1080\');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;
        padding: 20px;
    ">
        <style>
            @media (max-width: 640px) {
                .login-container {
                    padding: 30px 20px !important;
                }
                .login-title {
                    font-size: 24px !important;
                }
                .login-subtitle {
                    font-size: 14px !important;
                }
                .login-input {
                    padding: 16px 14px 16px 40px !important;
                    font-size: 16px !important;
                }
                .login-button {
                    padding: 16px !important;
                    font-size: 16px !important;
                }
                .login-link {
                    font-size: 14px !important;
                }
                .register-link-box {
                    padding: 14px !important;
                    margin-bottom: 20px !important;
                }
            }
        </style>
        <div class="login-container" style="
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.2);
        ">
            <!-- Header -->
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 class="login-title" style="color: #7c3aed; font-size: 28px; font-weight: bold; margin-bottom: 8px;">
                    🌿 JointBuddy User
                </h1>
                <p class="login-subtitle" style="color: #6b7280; font-size: 14px;">
                    Welcome Back - Member Login
                </p>
            </div>

            <!-- Quick Registration Link -->
            <div class="register-link-box" style="
                background: rgba(124, 58, 237, 0.1);
                border: 1px solid rgba(124, 58, 237, 0.3);
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 24px;
                text-align: center;
            ">
                <p style="color: #4c1d95; font-size: 13px; margin-bottom: 10px;">
                    New to JointBuddy?
                </p>
                <a href="<?php echo  url('/register/') ?>" style="
                    display: inline-block;
                    background: #7c3aed;
                    color: white;
                    padding: 10px 20px;
                    text-decoration: none;
                    border-radius: 8px;
                    font-size: 14px;
                    font-weight: 600;
                    transition: background 0.2s;
                    min-height: 44px;
                    line-height: 24px;
                " onmouseover="this.style.background=\'#6d28d9\'" onmouseout="this.style.background=\'#7c3aed\'">
                    ✨ Create Account
                </a>
            </div>

            <!-- Login Form -->
            <form method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                <input type="hidden" name="csrf_token" value="<?php echo  htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div style="position: relative;">
                    <input
                        type="email"
                        name="email"
                        placeholder="Email Address"
                        required
                        class="login-input"
                        style="
                            width: 100%;
                            padding: 14px 16px 14px 45px;
                            border: 2px solid #e5e7eb;
                            border-radius: 12px;
                            font-size: 16px;
                            transition: all 0.2s;
                            outline: none;
                            box-sizing: border-box;
                        "
                        onfocus="this.style.borderColor=\'#8b5cf6\'; this.style.boxShadow=\'0 0 0 3px rgba(139, 92, 246, 0.1)\'"
                        onblur="this.style.borderColor=\'#e5e7eb\'; this.style.boxShadow=\'none\'"
                    >
                    <span style="
                        position: absolute;
                        left: 16px;
                        top: 50%;
                        transform: translateY(-50%);
                        color: #6b7280;
                        font-size: 16px;
                    ">📧</span>
                </div>

                <div style="position: relative;">
                    <input
                        type="password"
                        name="password"
                        placeholder="Password"
                        required
                        class="login-input"
                        id="userPassword"
                        style="
                            width: 100%;
                            padding: 14px 50px 14px 45px;
                            border: 2px solid #e5e7eb;
                            border-radius: 12px;
                            font-size: 16px;
                            transition: all 0.2s;
                            outline: none;
                            box-sizing: border-box;
                        "
                        onfocus="this.style.borderColor=\'#8b5cf6\'; this.style.boxShadow=\'0 0 0 3px rgba(139, 92, 246, 0.1)\'"
                        onblur="this.style.borderColor=\'#e5e7eb\'; this.style.boxShadow=\'none\'"
                    >
                    <span style="
                        position: absolute;
                        left: 16px;
                        top: 50%;
                        transform: translateY(-50%);
                        color: #6b7280;
                        font-size: 16px;
                    ">🔒</span>
                    <button
                        type="button"
                        onclick="togglePassword(\'userPassword\', this)"
                        style="
                            position: absolute;
                            right: 12px;
                            top: 50%;
                            transform: translateY(-50%);
                            background: none;
                            border: none;
                            color: #6b7280;
                            cursor: pointer;
                            padding: 8px;
                            transition: color 0.2s;
                            min-width: 44px;
                            min-height: 44px;
                        "
                        onmouseover="this.style.color=\'#8b5cf6\'"
                        onmouseout="this.style.color=\'#6b7280\'"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>

                <button
                    type="submit"
                    class="login-button"
                    style="
                        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
                        color: white;
                        padding: 14px;
                        border: none;
                        border-radius: 12px;
                        font-size: 16px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.2s;
                        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
                        min-height: 50px;
                    "
                    onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 6px 20px rgba(139, 92, 246, 0.4)\'"
                    onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 4px 15px rgba(139, 92, 246, 0.3)\'"
                >
                    🌿 Member Login
                </button>
            </form>

            <div style="text-align: center; margin-top: 16px;">
                <a href="<?php echo  userUrl('forgot-password/') ?>" class="login-link" style="color: #7c3aed; text-decoration: none; font-size: 14px; padding: 10px; display: inline-block;">
                    Forgot password?
                </a>
            </div>

            <!-- Footer -->
            <div style="
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e5e7eb;
            ">
                <p style="color: #6b7280; font-size: 12px;">
                    Secure Member Portal
                </p>
            </div>
        </div>

        <script>
            function togglePassword(inputId, button) {
                const input = document.getElementById(inputId);
                const isPassword = input.type === "password";

                input.type = isPassword ? "text" : "password";

                button.innerHTML = isPassword ?
                    \'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>\' :
                    \'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>\';
            }
        </script>
    </body>';
}
?>
