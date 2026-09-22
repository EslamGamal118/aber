<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Verification</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-container">
            <div class="verification-header">
                <h1>Phone Verification</h1>
                <p class="text-muted">Please enter your phone number to verify your account</p>
            </div>
            
            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            
            <form method="POST" action="{{ route('provider.send-otp') }}">
                @csrf
                
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text">+966</span>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                               id="phone" name="phone" value="{{ old('phone') }}" 
                               placeholder="5XXXXXXXX" required>
                    </div>
                    @error('phone')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Enter your Saudi mobile number without the country code prefix.
                    </small>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Send Verification Code</button>
                </div>
                
                <div class="mt-4 text-center">
                    <p>Already have an account? <a href="{{ route('provider.login') }}">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 