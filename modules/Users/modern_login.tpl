<!--
/**
 * SuiteCRM Modern Login Template with OAuth 2.0 and Enhanced Security
 */
-->
<script type='text/javascript'>
    var LBL_LOGIN_SUBMIT = '{sugar_translate module="Users" label="LBL_LOGIN_SUBMIT"}';
    var LBL_REQUEST_SUBMIT = '{sugar_translate module="Users" label="LBL_REQUEST_SUBMIT"}';
    var LBL_SHOWOPTIONS = '{sugar_translate module="Users" label="LBL_SHOWOPTIONS"}';
    var LBL_HIDEOPTIONS = '{sugar_translate module="Users" label="LBL_HIDEOPTIONS"}';
    
    // Modern Auth JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        // Password strength indicator
        const passwordField = document.getElementById('username_password');
        const strengthMeter = document.getElementById('password_strength');
        
        if (passwordField && strengthMeter) {
            passwordField.addEventListener('input', function() {
                updatePasswordStrength(this.value);
            });
        }
        
        // Form validation
        document.getElementById('form').addEventListener('submit', function(e) {
            const username = document.getElementById('user_name').value;
            const password = document.getElementById('username_password').value;
            
            if (!username || !password) {
                e.preventDefault();
                showError('Please enter both username and password');
                return false;
            }
            
            // Show loading
            showLoading(true);
        });
        
        // OAuth error handling
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('oauth_error');
        if (error) {
            showError('OAuth authentication failed: ' + error);
        }
    });
    
    function updatePasswordStrength(password) {
        // Simple client-side strength check
        let strength = 0;
        if (password.length >= 8) strength += 20;
        if (password.match(/[a-z]/)) strength += 20;
        if (password.match(/[A-Z]/)) strength += 20;
        if (password.match(/[0-9]/)) strength += 20;
        if (password.match(/[^a-zA-Z0-9]/)) strength += 20;
        
        const meter = document.getElementById('password_strength');
        const bar = meter.querySelector('.strength-bar');
        const text = meter.querySelector('.strength-text');
        
        bar.style.width = strength + '%';
        
        if (strength < 40) {
            bar.className = 'strength-bar weak';
            text.textContent = 'Weak';
        } else if (strength < 70) {
            bar.className = 'strength-bar medium';
            text.textContent = 'Medium';
        } else {
            bar.className = 'strength-bar strong';
            text.textContent = 'Strong';
        }
    }
    
    function showError(message) {
        const errorDiv = document.getElementById('error_message');
        errorDiv.innerHTML = '<span class="error">' + message + '</span>';
        errorDiv.style.display = 'block';
    }
    
    function showLoading(show) {
        const button = document.getElementById('login_button');
        const spinner = document.getElementById('login_spinner');
        
        if (show) {
            button.disabled = true;
            button.value = 'Signing In...';
            spinner.style.display = 'inline-block';
        } else {
            button.disabled = false;
            button.value = '{sugar_translate module="Users" label="LBL_LOGIN_BUTTON_LABEL"}';
            spinner.style.display = 'none';
        }
    }
    
    function initiateOAuth(provider) {
        showLoading(true);
        window.location.href = 'index.php?entryPoint=oauth_login&provider=' + provider;
    }
</script>

<!-- Modern Login Styles -->
<style>
.modern-login-container {
    max-width: 480px;
    margin: 50px auto;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.login-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    padding: 40px;
    border: 1px solid #e1e5e9;
}

.login-header {
    text-align: center;
    margin-bottom: 30px;
}

.login-logo {
    max-width: 200px;
    height: auto;
    margin-bottom: 20px;
}

.login-title {
    font-size: 24px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 8px;
}

.login-subtitle {
    color: #6b7280;
    font-size: 14px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
    font-size: 14px;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.password-strength {
    margin-top: 8px;
    display: none;
}

.strength-meter {
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 4px;
}

.strength-bar {
    height: 100%;
    transition: width 0.3s, background-color 0.3s;
    border-radius: 2px;
}

.strength-bar.weak { background: #ef4444; }
.strength-bar.medium { background: #f59e0b; }
.strength-bar.strong { background: #10b981; }

.strength-text {
    font-size: 12px;
    color: #6b7280;
}

.login-button {
    width: 100%;
    background: #3b82f6;
    color: white;
    border: none;
    padding: 14px 16px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
    position: relative;
}

.login-button:hover {
    background: #2563eb;
}

.login-button:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

.login-spinner {
    display: none;
    width: 16px;
    height: 16px;
    border: 2px solid #ffffff;
    border-top: 2px solid transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 8px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.divider {
    text-align: center;
    margin: 30px 0;
    position: relative;
    color: #6b7280;
    font-size: 14px;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e5e7eb;
    z-index: 1;
}

.divider span {
    background: white;
    padding: 0 16px;
    position: relative;
    z-index: 2;
}

.oauth-buttons {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}

.oauth-button {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    color: #374151;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
    cursor: pointer;
}

.oauth-button:hover {
    border-color: #d1d5db;
    background: #f9fafb;
    text-decoration: none;
    color: #374151;
}

.oauth-button img {
    width: 20px;
    height: 20px;
    margin-right: 12px;
}

.oauth-button.google {
    border-color: #ea4335;
    color: #ea4335;
}

.oauth-button.microsoft {
    border-color: #0078d4;
    color: #0078d4;
}

.oauth-button.github {
    border-color: #333;
    color: #333;
}

.error {
    color: #dc2626;
    font-size: 14px;
    margin-bottom: 16px;
    padding: 12px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 6px;
}

.forgot-password {
    text-align: center;
    margin-top: 20px;
}

.forgot-password a {
    color: #3b82f6;
    text-decoration: none;
    font-size: 14px;
}

.forgot-password a:hover {
    text-decoration: underline;
}

.security-notice {
    margin-top: 20px;
    padding: 12px;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 6px;
    font-size: 12px;
    color: #0369a1;
}

.language-selector {
    margin-bottom: 20px;
}

.language-select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: white;
    font-size: 14px;
}

@media (max-width: 640px) {
    .modern-login-container {
        margin: 20px;
        max-width: none;
    }
    
    .login-card {
        padding: 24px;
    }
}
</style>

<div class="modern-login-container">
    <div class="login-card">
        <div class="login-header">
            {$LOGIN_IMAGE}
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Sign in to your SuiteCRM account</p>
        </div>

        <!-- Error Messages -->
        <div id="error_message" style="display: none;"></div>
        {if $LOGIN_ERROR !=''}
            <div class="error">{$LOGIN_ERROR}</div>
        {/if}
        {if $WAITING_ERROR !=''}
            <div class="error">{$WAITING_ERROR}</div>
        {/if}

        <!-- Language Selector -->
        {if !empty($SELECT_LANGUAGE)}
            <div class="language-selector">
                <label class="form-label">{sugar_translate module="Users" label="LBL_LANGUAGE"}:</label>
                <select name='login_language' class="language-select" onchange="switchLanguage(this.value)">
                    {$SELECT_LANGUAGE}
                </select>
            </div>
        {/if}

        <!-- OAuth Login Buttons -->
        {if $OAUTH_PROVIDERS}
            <div class="oauth-buttons">
                {foreach from=$OAUTH_PROVIDERS item=provider}
                    <button type="button" class="oauth-button {$provider}" onclick="initiateOAuth('{$provider}')">
                        {if $provider == 'google'}
                            <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                            Continue with Google
                        {elseif $provider == 'microsoft'}
                            <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#f25022" d="M1 1h10v10H1z"/><path fill="#00a4ef" d="M13 1h10v10H13z"/><path fill="#7fba00" d="M1 13h10v10H1z"/><path fill="#ffb900" d="M13 13h10v10H13z"/></svg>
                            Continue with Microsoft
                        {elseif $provider == 'github'}
                            <svg width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                            Continue with GitHub
                        {/if}
                    </button>
                {/foreach}
            </div>

            <div class="divider">
                <span>or continue with email</span>
            </div>
        {/if}

        <!-- Traditional Login Form -->
        <form action="index.php" method="post" name="DetailView" id="form" autocomplete="off">
            <input type="hidden" name="module" value="Users">
            <input type="hidden" name="action" value="Authenticate">
            <input type="hidden" name="return_module" value="Users">
            <input type="hidden" name="return_action" value="Login">
            <input type="hidden" id="cant_login" name="cant_login" value="">
            {$CSRF_TOKEN}
            {foreach from=$LOGIN_VARS key=key item=var}
                <input type="hidden" name="{$key}" value="{$var}">
            {/foreach}

            <div class="form-group">
                <label for="user_name" class="form-label">{sugar_translate module="Users" label="LBL_USER_NAME"}</label>
                <input type="text" 
                       id="user_name" 
                       name="user_name" 
                       class="form-input" 
                       value='{$LOGIN_USER_NAME}' 
                       autocomplete="username"
                       required>
            </div>

            <div class="form-group">
                <label for="username_password" class="form-label">{sugar_translate module="Users" label="LBL_PASSWORD"}</label>
                <input type="password" 
                       id="username_password" 
                       name="username_password" 
                       class="form-input" 
                       value='{$LOGIN_PASSWORD}' 
                       autocomplete="current-password"
                       required>
                <div id="password_strength" class="password-strength">
                    <div class="strength-meter">
                        <div class="strength-bar"></div>
                    </div>
                    <div class="strength-text"></div>
                </div>
            </div>

            <button type="submit" id="login_button" class="login-button">
                {sugar_translate module="Users" label="LBL_LOGIN_BUTTON_LABEL"}
                <span id="login_spinner" class="login-spinner"></span>
            </button>
        </form>

        <!-- Forgot Password -->
        <div class="forgot-password" style="display:{$DISPLAY_FORGOT_PASSWORD_FEATURE};">
            <a href="javascript:void(0)" onclick='toggleDisplay("forgot_password_dialog");'>
                {sugar_translate module="Users" label="LBL_LOGIN_FORGOT_PASSWORD"}
            </a>
        </div>

        <!-- Forgot Password Form -->
        <div id="forgot_password_dialog" style="display:none; margin-top: 20px;">
            <form action="index.php" method="post" name="fp_form" id="fp_form" autocomplete="off">
                <input type="hidden" name="entryPoint" value="GeneratePassword">
                <div id="generate_success" class='error' style="display:none;"></div>
                
                <div class="form-group">
                    <label for="fp_user_name" class="form-label">{sugar_translate module="Users" label="LBL_USER_NAME"}</label>
                    <input type="text" id="fp_user_name" name="fp_user_name" class="form-input" value='{$LOGIN_USER_NAME}' autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="fp_user_mail" class="form-label">{sugar_translate module="Users" label="LBL_EMAIL"}</label>
                    <input type="email" id="fp_user_mail" name="fp_user_mail" class="form-input" autocomplete="email">
                </div>
                
                {$CAPTCHA}
                
                <button type="button" class="login-button" onclick="validateAndSubmit(); return document.getElementById('cant_login').value == ''" id="generate_pwd_button">
                    {sugar_translate module="Users" label="LBL_LOGIN_SUBMIT"}
                </button>
            </form>
        </div>

        <!-- Security Notice -->
        <div class="security-notice">
            <strong>Security Notice:</strong> Your connection is protected with modern encryption. 
            For enhanced security, enable two-factor authentication in your profile settings after logging in.
        </div>
    </div>
</div>
