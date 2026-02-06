<?php
// Include error handler
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/url_helper.php';

function renderResetPasswordPage() {
    // In a real app, you'd verify the token here
    $token = $_GET['token'] ?? '';

    $message = $_SESSION['reset_password_message'] ?? null;
    $error = $_SESSION['reset_password_error'] ?? null;

    if ($message) unset($_SESSION['reset_password_message']);
    if ($error) unset($_SESSION['reset_password_error']);

    $alert = '';

    if ($message) {
        $alert = '<div style="
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            text-align: center;
        ">
            <p style="color: #15803d; font-size: 14px; margin: 0;">' . htmlspecialchars($message) . '</p>
        </div>';
    }

    if ($error) {
        $alert = '<div style="
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            text-align: center;
        ">
            <p style="color: #dc2626; font-size: 14px; margin: 0;">' . htmlspecialchars($error) . '</p>
        </div>';
    }

    return '<body style="
        margin: 0;
        padding: 0;
        min-height: 100vh;
        background: linear-gradient(135deg, rgba(0,0,0,0.7), rgba(0,0,0,0.5)),
        url(\'https://picsum.photos/seed/cannabuddy-reset/1920/1080\');
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
                .reset-container {
                    padding: 30px 20px !important;
                }
                .reset-title {
                    font-size: 24px !important;
                }
                .reset-subtitle {
                    font-size: 14px !important;
                }
                .reset-input {
                    padding: 16px 14px 16px 40px !important;
                    font-size: 16px !important;
                }
                .reset-button {
                    padding: 16px !important;
                    font-size: 16px !important;
                }
                .back-link {
                    font-size: 14px !important;
                    padding: 12px !important;
                }
            }
        </style>
        <div class="reset-container" style="
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
                <h1 class="reset-title" style="color: #7c3aed; font-size: 28px; font-weight: bold; margin-bottom: 8px;">
                    🔒 Set New Password
                </h1>
                <p class="reset-subtitle" style="color: #6b7280; font-size: 14px;">
                    Enter your new password below
                </p>
            </div>

            ' . $alert . '

            <!-- Reset Password Form -->
            <form method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                <input type="hidden" name="token" value="' . htmlspecialchars($token) . '">

                <div style="position: relative;">
                    <input
                        type="password"
                        name="new_password"
                        placeholder="New Password"
                        required
                        class="reset-input"
                        id="newPassword"
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
                        onclick="togglePassword(\'newPassword\', this)"
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

                <div style="position: relative;">
                    <input
                        type="password"
                        name="confirm_password"
                        placeholder="Confirm New Password"
                        required
                        class="reset-input"
                        id="confirmPassword"
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
                        onclick="togglePassword(\'confirmPassword\', this)"
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
                    class="reset-button"
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
                >
                    🔐 Update Password
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <a href="<?php echo  userUrl('login/') ?>" class="back-link" style="color: #7c3aed; text-decoration: none; font-size: 14px; padding: 10px; display: inline-block;">
                    ← Back to Login
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
                    Secure Password Reset
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
