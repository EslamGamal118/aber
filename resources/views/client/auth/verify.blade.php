<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification - Client</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .auth-card {
            max-width: 450px;
            width: 100%;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 25px;
        }
        .brand-logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #4361ee;
            margin-bottom: 10px;
        }
        .auth-form {
            margin-top: 20px;
        }
        .otp-input {
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="brand-logo">ServiceBooking</div>
                        <h4>Verify Your Phone</h4>
                        <p class="text-muted">Enter the verification code sent to your phone: <strong>{{ $phone }}</strong></p>
                    </div>
                    
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    @if(session('warning'))
                        <div class="alert alert-warning">
                            {{ session('warning') }}
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    <form class="auth-form" action="{{ route('client.verify-otp') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="otp" class="form-label">Verification Code</label>
                            <input type="text" class="form-control otp-input @error('otp') is-invalid @enderror" id="otp" name="otp" placeholder="------" maxlength="6">
                            @error('otp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                Verify <i class="bi bi-check-lg"></i>
                            </button>
                        </div>
                        
                        <div class="text-center mt-4">
                            <p>
                                <a href="{{ route('client.phone') }}">
                                    <i class="bi bi-arrow-left-short"></i> Back
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 