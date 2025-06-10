<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verify Your Email - Winja</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', Arial, sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .header {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #5b2be7 0%, #7c4dff 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><circle cx="2" cy="2" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
            opacity: 0.1;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .content {
            padding: 40px 30px;
            background-color: #ffffff;
        }
        .welcome-text {
            font-size: 24px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
            letter-spacing: -0.5px;
        }
        .message {
            color: #4a4a4a;
            font-size: 16px;
            margin-bottom: 30px;
        }
        .otp-container {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f3ff 100%);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            border: 1px solid rgba(91, 43, 231, 0.1);
        }
        .otp-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 700;
            letter-spacing: 8px;
            color: #5b2be7;
            margin: 15px 0;
            font-family: 'Inter', monospace;
        }
        .expiry-text {
            color: #666;
            font-size: 14px;
            margin-top: 15px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #5b2be7 0%, #7c4dff 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 6px rgba(91, 43, 231, 0.2);
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(91, 43, 231, 0.3);
        }
        .footer {
            text-align: center;
            padding: 30px;
            background-color: #f8f9ff;
            border-top: 1px solid rgba(91, 43, 231, 0.1);
        }
        .footer p {
            margin: 5px 0;
            color: #666;
            font-size: 13px;
        }
        .logo {
            margin-bottom: 20px;
        }
        .logo img {
            height: 40px;
        }
        .social-links {
            margin-top: 20px;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #5b2be7;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(91, 43, 231, 0.2), transparent);
            margin: 30px 0;
        }
        .security-note {
            background-color: #fff8f0;
            border-left: 4px solid #ffa726;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .security-note p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="{{ config('app.frontend_url') }}/images/winja-logo-white.png" alt="Winja Logo">
            </div>
            <h1>Welcome to Winja!</h1>
        </div>
        <div class="content">
            <div class="welcome-text">Hello {{ $user->name }},</div>
            <div class="message">
                Thank you for joining Winja! We're excited to have you on board. To ensure the security of your account and complete your registration, please verify your email address using the code below.
            </div>

            <div class="otp-container">
                <div class="otp-label">Your Verification Code</div>
                <div class="otp-code">{{ $otp }}</div>
                <div class="expiry-text">This code will expire in 10 minutes</div>
            </div>

            <div class="security-note">
                <p>🔒 For your security, never share this code with anyone. Winja will never ask for your verification code via email, phone, or text message.</p>
            </div>

            <div class="divider"></div>

            <div class="message">
                If you didn't request this verification code or if you're having trouble, you can request a new one by clicking the button below:
            </div>

            <div class="button-container">
                <a href="{{ config('app.frontend_url') }}/resend-otp" class="button">Request New Code</a>
            </div>
        </div>
        <div class="footer">
            <p>This is an automated message, please do not reply to this email.</p>
            <p>If you have any questions, please contact our support team.</p>
            <div class="social-links">
                <a href="#">Twitter</a>
                <a href="#">LinkedIn</a>
                <a href="#">Instagram</a>
            </div>
            <p>&copy; {{ date('Y') }} Winja. All rights reserved.</p>
        </div>
    </div>
</body>
</html> 