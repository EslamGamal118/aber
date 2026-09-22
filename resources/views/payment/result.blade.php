<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $paid ? 'تم الدفع بنجاح' : 'فشل عملية الدفع' }} - عابر</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f7fb;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .result-card {
            max-width: 460px;
            width: 100%;
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }
        .result-icon {
            font-size: 4.5rem;
            line-height: 1;
        }
    </style>
</head>
<body>
    <div class="container px-3">
        <div class="card result-card mx-auto" data-payment-status="{{ $status }}" data-order-id="{{ $order?->id }}">
            <div class="card-body text-center p-4 p-md-5">
                @if($paid)
                    <div class="result-icon text-success mb-3"><i class="bi bi-check-circle-fill"></i></div>
                    <h1 class="h3 fw-bold mb-2">تم الدفع بنجاح</h1>
                    <p class="text-muted mb-4">Payment completed successfully via Al Rajhi Bank.</p>
                @else
                    <div class="result-icon text-danger mb-3"><i class="bi bi-x-circle-fill"></i></div>
                    <h1 class="h3 fw-bold mb-2">لم تكتمل عملية الدفع</h1>
                    <p class="text-muted mb-4">Payment was not completed. You can try again from your order.</p>
                @endif

                @if($order)
                    <ul class="list-group list-group-flush text-start mb-4">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>رقم الطلب</span>
                            <strong>{{ $order->reference ?? '#' . $order->id }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>المبلغ</span>
                            <strong>{{ number_format((float) $order->total, 2) }} ر.س</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>طريقة الدفع</span>
                            <strong>مصرف الراجحي</strong>
                        </li>
                        @if($order->transaction_id)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>رقم العملية</span>
                                <strong dir="ltr">{{ $order->transaction_id }}</strong>
                            </li>
                        @endif
                    </ul>
                @endif

                <a href="{{ url('/') }}" class="btn btn-primary w-100">العودة للرئيسية</a>
            </div>
        </div>
    </div>
</body>
</html>
