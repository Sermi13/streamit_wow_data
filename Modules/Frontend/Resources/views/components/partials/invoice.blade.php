<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Invoice</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <style>
        h1, h2, h3, h4, h5, h6 { margin: 0; color: #010A0F; font-weight: 500; }
        p { margin: 0; }
        body { font-family: 'DejaVu Sans'; color: #6B6B6B; font-size: 14px; }
        .header, .main-content, .header-content, .bottom-section { margin-bottom: 15px; }
        .c-row { display: flex; flex-wrap: wrap; margin-bottom: 10px; }
        .c-align-center { align-items: center; }
        .c-justify-between { justify-content: space-between; }
        .c-col-6 { flex: 0 0 50%; }
        .c-col-7 { flex: 0 0 58.33333333%; }
        .c-col-5 { flex: 0 0 41.66666667%; }
        .p-badge { background-color: #32A071; color: #fff; padding: 6px 10px; border-radius: 6px; }
        .c-text-end { text-align: right; }
        ul { margin: 0; padding: 0; list-style: none; }
        ul li:not(:last-child) { margin-bottom: 5px; }
        table { width: 100%; border: 1px solid #CCC; border-radius: 6px; margin: 20px 0 15px; border-collapse: collapse; }
        table thead { background-color: #F2F2F2; }
        table th, table td { padding: 6px 8px; border: 1px solid #CCC; }
        hr { margin: 15px 0; border-color: #CCC; }
    </style>
</head>
<body>

<div class="header">
    <div style="float: left">
        @php
            $logo = GetSettingValue('dark_logo') ?? 'img/logo/dark_logo.png';
            $logoSrc = '';
            try {
                if (filter_var($logo, FILTER_VALIDATE_URL)) {
                    $parsedUrl = parse_url($logo);
                    $path = preg_replace('/^\/storage/', '', $parsedUrl['path']);
                    $logoPath = storage_path('app/public' . $path);
                } elseif (Str::startsWith($logo, 'storage/')) {
                    $logoPath = storage_path('app/public/' . str_replace('storage/', '', $logo));
                } else {
                    $logoPath = public_path($logo);
                }
                if (file_exists($logoPath)) {
                    $imageData = base64_encode(file_get_contents($logoPath));
                    $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $logoPath);
                    $logoSrc = "data:{$mimeType};base64,{$imageData}";
                }
            } catch (\Exception $e) {
                \Log::error('Error loading logo: ' . $e->getMessage());
            }
            if (empty($logoSrc)) $logoSrc = asset('img/logo/dark_logo.png');
        @endphp
        @if($logoSrc)
            <img src="{{ $logoSrc }}" style="max-height: 30px; width: auto;" alt="logo" />
        @endif
    </div>
    <div style="float: right">
        <p>
            {{ __('frontend.invoice_date') }} - <span class="text-black">{{ now()->format('d/m/Y') }}</span>
            <span style="padding-left: 10px;">{{ __('frontend.invoice_id') }} - <span class="text-black">#{{ $data->id }}</span></span>
        </p>
    </div>
</div>

<div style="clear: both"><hr></div>

<div class="main-content">
    <div class="c-row c-align-center c-justify-between" style="padding: 15px 0 20px;">
        <div class="c-col-6" style="float: left">
            <p>{{ __('frontend.thanks_payment') }}</p>
        </div>
        <div class="c-col-6 c-text-end" style="float: right">
            <span>{{ __('frontend.payment_status') }}:</span>
            <span class="p-badge" style="margin-left: 10px;">{{ optional($data->subscription_transaction)->payment_status ?? '-' }}</span>
        </div>
    </div>
</div>

<div style="clear: both"><hr></div>

<div class="header-content">
    <div class="c-row">
        <div class="c-col-7" style="float: left">
            <h3>{{ App\Models\Setting::where('name', 'app_name')->value('val') ?? '-' }}</h3>
        </div>
        <div class="c-col-5 c-text-end" style="float: right">
            <p>{{ App\Models\Setting::where('name', 'inquriy_email')->value('val') ?? '-' }}</p>
            <p style="margin: 10px 0 0;">{{ App\Models\Setting::where('name', 'helpline_number')->value('val') ?? '-' }}</p>
        </div>
    </div>
</div>

<div style="clear: both"><hr></div>

<div class="c-row">
    <div class="c-col-7">
        <h4>{{ __('frontend.customer_detail') }}:</h4>
        <ul style="margin-top: 10px;">
            <li>{{ $data->user->full_name ?? default_user_name() }}</li>
            <li>{{ $data->user->email ?? '-' }}</li>
            <li>{{ $data->user->mobile ?? '-' }}</li>
        </ul>
    </div>
</div>

@php
    // Base price from the data
    $baseAmount = floatval($data->amount ?? 0);

    // Plan discount percentage
    $discountPercent = floatval($data->discount_percentage ?? 0);

    // Coupon discount amount
    $couponDiscountAmount = floatval($data->coupon_discount ?? 0);

    // Calculate total discount amount
    $planDiscountAmount = ($discountPercent > 0) ? ($baseAmount * $discountPercent / 100) : 0;

    // Amount after discounts applied
    $amountAfterDiscount = max(0, $baseAmount - $planDiscountAmount - $couponDiscountAmount);

    // Fetch active taxes
    $taxes = \Modules\Tax\Models\Tax::active()->get();

    // Calculate tax amounts based on amountAfterDiscount
    $taxData = [];
    $totalTaxAmount = 0;
    foreach ($taxes as $tax) {
        if (strtolower($tax->type) === 'percentage') {
            $taxAmount = ($amountAfterDiscount * $tax->value) / 100;
        } else {
            // fixed amount
            $taxAmount = $tax->value;
        }
        $taxData[] = [
            'title' => $tax->title,
            'amount' => $taxAmount,
        ];
        $totalTaxAmount += $taxAmount;
    }

    // Calculate grand total = discounted amount + taxes
    $grandTotal = $amountAfterDiscount + $totalTaxAmount;
@endphp

<table>
    <thead>
        <tr>
            <th>Plan Name</th>
            <th>Plan Price</th>
            <th>Tax Name</th>
            <th>Tax Amount</th>
            <th class="c-text-end">Total</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $data->name }} - {{ $data->duration }} {{ $data->type }}</td>
            <td>{{ \Currency::format($baseAmount) }}</td>
            <td>-</td>
            <td>-</td>
            <td class="c-text-end">{{ \Currency::format($baseAmount) }}</td>
        </tr>

        @if($planDiscountAmount > 0)
        <tr>
            <td colspan="3"></td>
            <td>Plan Discount ({{ $discountPercent }}%)</td>
            <td class="c-text-end">-{{ \Currency::format($planDiscountAmount) }}</td>
        </tr>
        @endif

        @if($couponDiscountAmount > 0)
        <tr>
            <td colspan="3"></td>
            <td>Coupon Discount</td>
            <td class="c-text-end">-{{ \Currency::format($couponDiscountAmount) }}</td>
        </tr>
        @endif

        @foreach ($taxData as $tax)
        <tr>
            <td colspan="2"></td>
            <td>{{ $tax['title'] }}</td>
            <td>{{ \Currency::format($tax['amount']) }}</td>
            <td></td>
        </tr>
        @endforeach

        <tr>
            <th colspan="4" style="text-align: left;">Grand Total</th>
            <th class="c-text-end">{{ \Currency::format($grandTotal) }}</th>
        </tr>
    </tbody>
</table>


<div style="text-align: center; margin-top: 10px;">
    <strong>{{ __('frontend.thank_you_for_visiting') }}</strong>
</div>

</body>
</html>
