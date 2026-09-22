<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .registration-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .registration-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .registration-header h1 {
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
        .document-upload {
            border: 2px dashed #ced4da;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-container">
            <div class="registration-header">
                <h1>Complete Your Registration</h1>
                <p class="text-muted">Your phone number <strong>{{ $phone }}</strong> has been verified.</p>
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
            
            <form method="POST" action="{{ route('provider.register.post') }}" enctype="multipart/form-data">
                @csrf
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" 
                               id="password_confirmation" name="password_confirmation" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Provider Type</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="type" id="type_personal" 
                               value="personal" {{ old('type') == 'personal' ? 'checked' : '' }} checked>
                        <label class="form-check-label" for="type_personal">
                            Personal (Individual)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="type" id="type_company" 
                               value="company" {{ old('type') == 'company' ? 'checked' : '' }}>
                        <label class="form-check-label" for="type_company">
                            Company
                        </label>
                    </div>
                    @error('type')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <div id="personalDocs" class="mb-4">
                    <label for="id_card" class="form-label">ID Card / National ID</label>
                    <div class="document-upload">
                        <input type="file" class="form-control @error('id_card') is-invalid @enderror" 
                               id="id_card" name="id_card">
                        <div class="mt-2 text-muted">
                            <small>Upload a clear copy of your national ID card (JPG, PNG, PDF max 2MB)</small>
                        </div>
                    </div>
                    @error('id_card')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <div id="companyDocs" class="mb-4 d-none">
                    <label for="commercial_register" class="form-label">Commercial Register</label>
                    <div class="document-upload">
                        <input type="file" class="form-control @error('commercial_register') is-invalid @enderror" 
                               id="commercial_register" name="commercial_register">
                        <div class="mt-2 text-muted">
                            <small>Upload a clear copy of your commercial register (JPG, PNG, PDF max 2MB)</small>
                        </div>
                    </div>
                    @error('commercial_register')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
                    <label class="form-check-label" for="agree_terms">
                        I agree to the Terms of Service and Privacy Policy
                    </label>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Complete Registration</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Toggle between personal and company document fields
        document.addEventListener('DOMContentLoaded', function() {
            const personalRadio = document.getElementById('type_personal');
            const companyRadio = document.getElementById('type_company');
            const personalDocs = document.getElementById('personalDocs');
            const companyDocs = document.getElementById('companyDocs');
            
            function toggleDocs() {
                if (personalRadio.checked) {
                    personalDocs.classList.remove('d-none');
                    companyDocs.classList.add('d-none');
                    document.getElementById('commercial_register').removeAttribute('required');
                    document.getElementById('id_card').setAttribute('required', 'required');
                } else {
                    personalDocs.classList.add('d-none');
                    companyDocs.classList.remove('d-none');
                    document.getElementById('id_card').removeAttribute('required');
                    document.getElementById('commercial_register').setAttribute('required', 'required');
                }
            }
            
            personalRadio.addEventListener('change', toggleDocs);
            companyRadio.addEventListener('change', toggleDocs);
            
            // Initial toggle
            toggleDocs();
        });
    </script>
</body>
</html> 