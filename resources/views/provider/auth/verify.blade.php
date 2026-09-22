<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .verification-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .verification-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .verification-header h1 {
            font-size: 24px;
            color: #333;
        }
        .btn-primary {
            background-color: #4361ee;
            border-color: #4361ee;
        }
        .btn-primary:hover {
            background-color: #3a56d4;
            border-color: #3a56d4;
        }
        .otp-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .otp-input {
            width: 50px;
            height: 50px;
            margin: 0 5px;
            text-align: center;
            font-size: 20px;
            border-radius: 5px;
            border: 1px solid #ced4da;
        }
        .resend-link {
            color: #4361ee;
            text-decoration: none;
            cursor: pointer;
        }
        .resend-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-container">
            <div class="verification-header">
                <h1>Verify Your Phone Number</h1>
                <p class="text-muted">We've sent a verification code to <strong>{{ $phone }}</strong></p>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            
            <form method="POST" action="{{ route('provider.verify-otp') }}">
                @csrf
                
                <div class="mb-3">
                    <label for="otp" class="form-label">Enter 6-digit Verification Code</label>
                    <input type="text" class="form-control @error('otp') is-invalid @enderror" 
                           id="otp" name="otp" maxlength="6" required>
                    @error('otp')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="d-grid gap-2 mb-3">
                    <button type="submit" class="btn btn-primary">Verify Code</button>
                </div>
                
                <div class="text-center">
                    <p>Didn't receive a code?</p>
                    <form method="POST" action="{{ route('provider.resend-otp') }}" id="resendForm">
                        @csrf
                        <button type="submit" class="btn btn-link resend-link">Resend Code</button>
                    </form>
                </div>
                
                <div class="mt-3 text-center">
                    <a href="{{ route('provider.phone') }}" class="btn btn-outline-secondary btn-sm">
                        Change Phone Number
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 