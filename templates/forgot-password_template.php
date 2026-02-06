<?php
// Include error handler
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/url_helper.php';

function renderForgotPasswordPage() {
    $message = $_SESSION['forgot_password_message'] ?? null;
    $error = $_SESSION['forgot_password_error'] ?? null;

    if ($message) unset($_SESSION['forgot_password_message']);
    if ($error) unset($_SESSION['forgot_password_error']);

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
        url(\'https://picsum.photos/seed/cannabuddy-forgot/1920/1080\');
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
                .forgot-container {
                    padding: 30px 20px !important;
                }
                .forgot-title {
                    font-size: 24px !important;
                }
                .forgot-subtitle {
                    font-size: 14px !important;
                }
                .forgot-input {
                    padding: 16px 14px !important;
                    font-size: 16px !important;
                }
                .forgot-button {
                    padding: 16px !important;
                    font-size: 16px !important;
                }
                .back-link {
                    font-size: 14px !important;
                    padding: 12px !important;
                }
            }
        </style>
        <div class="forgot-container" style="
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
                <h1 class="forgot-title" style="color: #7c3aed; font-size: 28px; font-weight: bold; margin-bottom: 8px;">
                    🔐 Reset Password
                </h1>
                <p class="forgot-subtitle" style="color: #6b7280; font-size: 14px;">
                    Enter your email to receive reset instructions
                </p>
            </div>

            ' . $alert . '

            <!-- Forgot Password Form -->
            <form method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                <div style="position: relative;">
                    <input
                        type="email"
                        name="email"
                        placeholder="Email Address"
                        required
                        class="forgot-input"
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

                <button
                    type="submit"
                    class="forgot-button"
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
                    📬 Send Reset Link
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
                    Need help? Contact support
                </p>
            </div>
        </div>
    </body>';
}
?>
