<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .alert-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .file-list {
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 15px 0;
        }
        .file-item {
            margin: 10px 0;
            padding: 10px;
            background-color: white;
            border-radius: 4px;
        }
        .file-path {
            font-family: monospace;
            font-weight: bold;
            color: #dc3545;
        }
        .file-meta {
            font-size: 0.9em;
            color: #666;
        }
        .code-preview {
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 0.85em;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        .action-required {
            background-color: #dc3545;
            color: white;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
<h1>🚨 Security Alert: Suspicious PHP Files Detected</h1>

<div class="alert-box">
    <strong>⚠ Warning:</strong> {{ count($files) }} suspicious PHP file(s) have been detected in your application directories.
</div>

<div class="action-required">
    <h3>⚡ Immediate Action Required</h3>
    <p>Please investigate these files immediately. They may indicate a security breach.</p>
</div>

<h2>Detected Files:</h2>

<div class="file-list">
    @foreach($files as $file)
        <div class="file-item">
            <div class="file-path">📄 {{ $file['full_path'] }}</div>
            <div class="file-meta">
                <strong>Size:</strong> {{ number_format($file['size']) }} bytes |
                <strong>Created:</strong> {{ $file['created']->format('Y-m-d H:i:s') }}
                                                                          ({{ $file['created']->diffForHumans() }})
            </div>
            <div class="code-preview">{{ $file['content_preview'] }}</div>
        </div>
    @endforeach
</div>

<h2>Recommended Actions:</h2>
<ol>
    <li><strong>Review each file immediately</strong> - Check if these are legitimate files or malicious code</li>
    <li><strong>Delete suspicious files</strong> - Remove any unauthorized PHP files</li>
    <li><strong>Check access logs</strong> - Identify how these files were uploaded</li>
    <li><strong>Scan for webshells</strong> - Run security scanner to check for more malicious files</li>
    <li><strong>Change credentials</strong> - Update admin passwords, SSH keys, and API tokens</li>
    <li><strong>Review file upload code</strong> - Ensure file upload validation is properly implemented</li>
</ol>

<p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666;">
    This is an automated security alert from {{ config('app.name') }}<br>
    Generated at {{ now()->format('Y-m-d H:i:s') }}
</p>
</body>
</html>
