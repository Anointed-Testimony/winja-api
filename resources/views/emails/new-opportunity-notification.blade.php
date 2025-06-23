<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Opportunity - Winja</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #5b2be7;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            font-size: 16px;
        }
        .opportunity-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #5b2be7;
        }
        .opportunity-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .opportunity-type {
            display: inline-block;
            background-color: #5b2be7;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .opportunity-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .opportunity-details {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .detail {
            flex: 1;
            min-width: 150px;
        }
        .detail-label {
            font-weight: bold;
            color: #333;
            font-size: 14px;
        }
        .detail-value {
            color: #666;
            font-size: 14px;
        }
        .cta-button {
            display: inline-block;
            background-color: #5b2be7;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .premium-badge {
            background-color: #ffd700;
            color: #333;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Winja</div>
            <div class="subtitle">Your Premium Opportunity Alert</div>
        </div>

        <h2>Hello {{ $user->name }}! 👋</h2>
        
        <p>Great news! A new <strong>{{ $opportunityType->name }}</strong> opportunity has just been posted that matches your interests.</p>

        <div class="opportunity-card">
            <div class="opportunity-type">{{ $opportunityType->name }}</div>
            <div class="opportunity-title">{{ $opportunity->title }}</div>
            
            @if($opportunity->sponsor)
                <div class="opportunity-details">
                    <div class="detail">
                        <div class="detail-label">Sponsor:</div>
                        <div class="detail-value">{{ $opportunity->sponsor }}</div>
                    </div>
                </div>
            @endif

            <div class="opportunity-description">
                {{ Str::limit($opportunity->description, 200) }}
            </div>

            @if($opportunity->eligibility)
                <div class="opportunity-details">
                    <div class="detail">
                        <div class="detail-label">Eligibility:</div>
                        <div class="detail-value">{{ $opportunity->eligibility }}</div>
                    </div>
                </div>
            @endif

            @if($opportunity->expiry)
                <div class="opportunity-details">
                    <div class="detail">
                        <div class="detail-label">Deadline:</div>
                        <div class="detail-value">{{ \Carbon\Carbon::parse($opportunity->expiry)->format('M d, Y') }}</div>
                    </div>
                </div>
            @endif

            <a href="{{ $opportunityUrl }}" class="cta-button">View Opportunity</a>
        </div>

        <p><strong>Why you received this:</strong> As a <span class="premium-badge">Premium User</span>, you get instant notifications for new opportunities. Free users receive digest emails every 5 hours.</p>

        <div class="footer">
            <p>© {{ date('Y') }} Winja. All rights reserved.</p>
            <p>You're receiving this because you're a premium user. <a href="{{ config('app.frontend_url') }}/settings">Manage your notification preferences</a></p>
        </div>
    </div>
</body>
</html> 