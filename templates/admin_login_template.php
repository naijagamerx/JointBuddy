<?php
// Include error handler
require_once __DIR__ . '/../includes/error_handler.php';

function renderAdminLoginPage() {
    // Demo credentials for testing
    $demoUsername = 'admin';
    $demoPassword = 'admin123';
    
    return '<body style="
        margin: 0;
        padding: 0;
        min-height: 100vh;
        background: linear-gradient(135deg, rgba(0,0,0,0.7), rgba(0,0,0,0.5)), 
        url(\'https://picsum.photos/seed/cannabuddy-admin/1920/1080\');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;
    ">
        <div style="
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
                <h1 style="color: #065f46; font-size: 28px; font-weight: bold; margin-bottom: 8px;">
                    🏪 JointBuddy Admin
                </h1>
                <p style="color: #6b7280; font-size: 14px;">
                    Administrator Login Panel
                </p>
            </div>
            
            <!-- Demo Credentials Info -->
            <div style="
                background: rgba(59, 130, 246, 0.1);
                border: 1px solid rgba(59, 130, 246, 0.3);
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 24px;
            ">
                <h3 style="color: #1e40af; font-size: 14px; font-weight: bold; margin-bottom: 8px;">
                    📋 Demo Credentials
                </h3>
                <div style="display: grid; gap: 6px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #374151;">Username:</span>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <code style="background: rgba(0,0,0,0.1); padding: 2px 8px; border-radius: 4px; font-size: 12px;">' . htmlspecialchars($demoUsername) . '</code>
                            <button onclick="copyText(\'' . htmlspecialchars($demoUsername) . '\')" style="
                                background: #3b82f6;
                                color: white;
                                border: none;
                                padding: 4px 8px;
                                border-radius: 4px;
                                font-size: 11px;
                                cursor: pointer;
                                transition: background 0.2s;
                            " onmouseover="this.style.background=\'#2563eb\'" onmouseout="this.style.background=\'#3b82f6\'">
                                Copy
                            </button>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #374151;">Password:</span>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <code style="background: rgba(0,0,0,0.1); padding: 2px 8px; border-radius: 4px; font-size: 12px;">' . htmlspecialchars($demoPassword) . '</code>
                            <button onclick="copyText(\'' . htmlspecialchars($demoPassword) . '\')" style="
                                background: #3b82f6;
                                color: white;
                                border: none;
                                padding: 4px 8px;
                                border-radius: 4px;
                                font-size: 11px;
                                cursor: pointer;
                                transition: background 0.2s;
                            " onmouseover="this.style.background=\'#2563eb\'" onmouseout="this.style.background=\'#3b82f6\'">
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
                <div id="copyFeedback" style="
                    color: #059669;
                    font-size: 11px;
                    margin-top: 8px;
                    opacity: 0;
                    transition: opacity 0.3s;
                ">
                    ✓ Copied to clipboard!
                </div>
            </div>
            
            <!-- Login Form -->
            <form method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                <div style="position: relative;">
                    <input 
                        type="text" 
                        name="username" 
                        placeholder="Username or Email" 
                        required
                        style="
                            width: 100%;
                            padding: 14px 16px 14px 45px;
                            border: 2px solid #e5e7eb;
                            border-radius: 12px;
                            font-size: 16px;
                            transition: all 0.2s;
                            outline: none;
                        "
                        onfocus="this.style.borderColor=\'#10b981\'; this.style.boxShadow=\'0 0 0 3px rgba(16, 185, 129, 0.1)\'"
                        onblur="this.style.borderColor=\'#e5e7eb\'; this.style.boxShadow=\'none\'"
                    >
                    <span style="
                        position: absolute;
                        left: 16px;
                        top: 50%;
                        transform: translateY(-50%);
                        color: #6b7280;
                        font-size: 16px;
                    ">👤</span>
                </div>
                
                <div style="position: relative;">
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="Password" 
                        required
                        style="
                            width: 100%;
                            padding: 14px 50px 14px 45px;
                            border: 2px solid #e5e7eb;
                            border-radius: 12px;
                            font-size: 16px;
                            transition: all 0.2s;
                            outline: none;
                        "
                        id="adminPassword"
                        onfocus="this.style.borderColor=\'#10b981\'; this.style.boxShadow=\'0 0 0 3px rgba(16, 185, 129, 0.1)\'"
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
                        onclick="togglePassword(\'adminPassword\', this)"
                        style="
                            position: absolute;
                            right: 12px;
                            top: 50%;
                            transform: translateY(-50%);
                            background: none;
                            border: none;
                            color: #6b7280;
                            cursor: pointer;
                            padding: 4px;
                            transition: color 0.2s;
                        "
                        onmouseover="this.style.color=\'#10b981\'"
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
                    style="
                        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                        color: white;
                        padding: 14px;
                        border: none;
                        border-radius: 12px;
                        font-size: 16px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.2s;
                        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
                    "
                    onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 6px 20px rgba(16, 185, 129, 0.4)\'"
                    onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 4px 15px rgba(16, 185, 129, 0.3)\'"
                >
                    🔐 Secure Login
                </button>
            </form>
            
            <!-- Footer -->
            <div style="
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e5e7eb;
            ">
                <p style="color: #6b7280; font-size: 12px;">
                    Protected by JointBuddy Security
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
            
            function copyText(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(function() {
                        showCopyFeedback();
                    }).catch(function(err) {
                        console.error(\'Could not copy text: \', err);
                        fallbackCopy(text);
                    });
                } else {
                    fallbackCopy(text);
                }
            }
            
            function fallbackCopy(text) {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.left = "-999999px";
                textArea.style.top = "-999999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand(\'copy\');
                    showCopyFeedback();
                } catch (err) {
                    console.error(\'Fallback: Oops, unable to copy\', err);
                }
                document.body.removeChild(textArea);
            }
            
            function showCopyFeedback() {
                const feedback = document.getElementById(\'copyFeedback\');
                feedback.style.opacity = \'1\';
                setTimeout(() => {
                    feedback.style.opacity = \'0\';
                }, 2000);
            }
        </script>
    </body>';
}
?>