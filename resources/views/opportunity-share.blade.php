<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Winja Opportunity</title>
    <meta name="description" content="Check out this amazing opportunity on Winja">
    <meta property="og:title" content="Winja Opportunity">
    <meta property="og:description" content="Check out this amazing opportunity on Winja">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('winja-icon.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 20px;
        }
        
        h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .subtitle {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .opportunity-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            border-left: 4px solid #5b2be7;
        }
        
        .opportunity-title {
            color: #333;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .opportunity-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .download-section {
            margin-top: 30px;
        }
        
        .download-btn {
            background: linear-gradient(135deg, #5b2be7 0%, #667eea 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            display: inline-block;
            margin: 10px;
            transition: transform 0.2s ease;
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
        }
        
        .app-stores {
            margin-top: 20px;
        }
        
        .store-btn {
            display: inline-block;
            margin: 5px;
            padding: 10px 20px;
            background: #f8f9fa;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
            transition: background 0.2s ease;
        }
        
        .store-btn:hover {
            background: #e9ecef;
        }
        
        .footer {
            margin-top: 30px;
            color: #999;
            font-size: 12px;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .opportunity-title {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="{{ asset('winja-icon.png') }}" alt="Winja" class="logo">
        
        <h1>Winja Opportunity</h1>
        <p class="subtitle">Discover amazing opportunities and grow your career with Winja</p>
        
        <div class="opportunity-card">
            <div class="opportunity-title">🎯 Amazing Opportunity Available</div>
            <div class="opportunity-description">
                We have an exciting opportunity waiting for you! Download the Winja app to view full details, 
                apply directly, and discover thousands of other opportunities tailored to your interests.
            </div>
        </div>
        
        <div class="download-section">
            <a href="#" class="download-btn" onclick="openInApp()">
                📱 Open in Winja App
            </a>
            
            <div style="margin-top: 15px; font-size: 14px; color: #666;">
                <strong>💡 Mobile users:</strong> The app will open automatically if installed.
            </div>
            
            <div class="app-stores">
                <a href="#" class="store-btn" onclick="openAppStore()">
                    🍎 App Store
                </a>
                <a href="#" class="store-btn" onclick="openPlayStore()">
                    🤖 Google Play
                </a>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px; font-size: 14px; color: #666;">
                <strong>💡 Tip:</strong> If the app doesn't open automatically, tap "Open in Winja App" again or download from the app stores below.
            </div>
        </div>
        
        <div class="footer">
            <p>© 2024 Winja. All rights reserved.</p>
            <p>Share this opportunity with friends and family!</p>
        </div>
    </div>
    
    <script>
        // Get opportunity ID from URL
        const opportunityId = window.location.pathname.split('/').pop();
        
        // Deep link URLs for the app
        const deepLinkUrl = `winja://opportunity/${opportunityId}`;
        
        // Check if we're on mobile
        const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
        
        function openInApp() {
            if (isMobile) {
                // Try to open the app first
                window.location.href = deepLinkUrl;
                
                // Show a message that we're trying to open the app
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '🔄 Opening App...';
                btn.style.opacity = '0.7';
                
                // If app doesn't open within 2 seconds, redirect to app store
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.opacity = '1';
                    downloadApp();
                }, 2000);
            } else {
                // On desktop, just redirect to app store
                downloadApp();
            }
        }
        
        function downloadApp() {
            // Detect platform and redirect to appropriate store
            const userAgent = navigator.userAgent || navigator.vendor || window.opera;
            
            if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
                // iOS - redirect to App Store
                window.location.href = 'https://apps.apple.com/app/winja';
            } else {
                // Android or other - redirect to Google Play
                window.location.href = 'https://play.google.com/store/apps/details?id=com.winja.app';
            }
        }
        
        function openAppStore() {
            window.location.href = 'https://apps.apple.com/app/winja';
        }
        
        function openPlayStore() {
            window.location.href = 'https://play.google.com/store/apps/details?id=com.winja.app';
        }
        
        // Add social sharing functionality
        if (navigator.share) {
            // Use native sharing if available
            navigator.share({
                title: 'Winja Opportunity',
                text: 'Check out this amazing opportunity on Winja!',
                url: window.location.href
            });
        }
        
        // Auto-attempt to open app when page loads on mobile
        if (isMobile) {
            setTimeout(function() {
                // Try to open the app automatically
                window.location.href = deepLinkUrl;
                
                // If app doesn't open within 2 seconds, show the page
                setTimeout(function() {
                    // App didn't open, show the page normally
                    console.log('App not installed, showing web page');
                }, 2000);
            }, 500);
        }
    </script>
</body>
</html> 