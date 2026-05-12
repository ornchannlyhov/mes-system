<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8fafc;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #05624e 0%, #045645 100%);
            padding: 32px 24px;
            text-align: center;
        }
        .header h1 {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }
        .content {
            padding: 40px 24px;
        }
        .message {
            color: #6b7280;
            font-size: 16px;
            text-align: center;
            margin-bottom: 32px;
        }
        .otp-container {
            background: #f3f4f6;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin: 0 auto 32px;
            max-width: 400px;
            position: relative;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 700;
            color: #1f2937;
            letter-spacing: 12px;
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
            text-align: center;
            margin-bottom: 16px;
        }
        .copy-button-container {
            text-align: center;
        }
        .copy-button {
            background: #05624e;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
        }
        .copy-button:hover {
            background: #045645;
        }
        .copy-icon {
            width: 16px;
            height: 16px;
        }
        .security-notice {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 32px;
        }
        .security-notice h3 {
            color: #92400e;
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .security-notice p {
            color: #78350f;
            font-size: 14px;
            margin: 0;
            line-height: 1.5;
        }
        .footer {
            background: #f9fafb;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            color: #6b7280;
            font-size: 14px;
            margin: 0 0 8px 0;
        }
        .footer .brand {
            color: #9ca3af;
            font-size: 12px;
        }
        .icon {
            width: 20px;
            height: 20px;
            display: inline-block;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Verification Code</h1>
        </div>

        <!-- Main Content -->
        <div class="content">
            <p class="message">
                {{ $messageLine }}
            </p>
            
            <!-- OTP Code Display -->
            <div class="otp-container">
                <div class="otp-code">{{ $otp }}</div>
                <div class="copy-button-container">
                    <button class="copy-button" onclick="copyToClipboard('{{ $otp }}')">
                        <svg class="copy-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Copy Code
                    </button>
                </div>
            </div>
            
            <!-- Security Notice -->
            <div class="security-notice">
                <h3>
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Security Notice
                </h3>
                <p>This code will expire in 10 minutes. Never share this code with anyone.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>If you didn't request this code, please ignore this email.</p>
            <p class="brand">{{ config('app.name') }} • {{ date('Y') }}</p>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Code copied to clipboard!');
            }).catch(function(err) {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('Code copied to clipboard!');
            });
        }
    </script>
</body>
</html>