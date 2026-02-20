<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Statement - {{ $slot->user?->name ?? 'Member' }} - Month {{ $monthNumber }}</title>
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #ecf1fb;
            color: #18375f;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
        }

        .sheet {
            width: min(980px, calc(100% - 36px));
            margin: 24px auto;
            border: 1px solid #d2def4;
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 20px 44px rgba(18, 54, 108, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #163f88 0%, #2a61c4 52%, #4f84dd 100%);
            color: #fff;
            padding: 26px 30px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            margin: 0;
            font-size: 27px;
            font-weight: 800;
            letter-spacing: 0.01em;
        }

        .sub {
            margin-top: 7px;
            opacity: 0.92;
            font-size: 13px;
            line-height: 1.5;
        }

        .invoice-chip {
            border: 1px solid rgba(255, 255, 255, 0.45);
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            background: rgba(255, 255, 255, 0.14);
            white-space: nowrap;
        }

        .content {
            padding: 22px 30px 24px;
        }

        .top-grid {
            display: grid;
            grid-template-columns: 1.35fr 1fr;
            gap: 14px;
            margin-bottom: 14px;
        }

        .panel {
            border: 1px solid #d8e4f7;
            border-radius: 12px;
            background: #fbfdff;
            padding: 14px 15px;
        }

        .title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b83aa;
            font-weight: 700;
            margin: 0 0 8px;
        }

        .kv {
            display: grid;
            grid-template-columns: 130px 1fr;
            gap: 8px;
            margin-bottom: 6px;
        }

        .kv:last-child {
            margin-bottom: 0;
        }

        .k {
            color: #6a83a6;
            font-size: 13px;
        }

        .v {
            color: #173d73;
            font-size: 14px;
            font-weight: 700;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid #d4e1f7;
            background: #edf4ff;
            color: #2a5599;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 6px 11px;
        }

        .status-pill.paid {
            border-color: #c7e6d3;
            background: #ebf8f0;
            color: #1f7a45;
        }

        .status-pill.due {
            border-color: #f3dfb5;
            background: #fff7e7;
            color: #966305;
        }

        .status-pill.not-paid {
            border-color: #f0cad1;
            background: #fff1f3;
            color: #a7303b;
        }

        .amount-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .amount-card {
            border: 1px solid #dbe6f8;
            border-radius: 12px;
            padding: 11px 12px;
            background: #fff;
        }

        .amount-label {
            color: #6d85a8;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .amount-value {
            margin: 0;
            color: #163c71;
            font-size: 22px;
            line-height: 1.2;
            font-weight: 800;
        }

        .amount-value.subtle {
            font-size: 19px;
        }

        .foot {
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px dashed #cfdcf3;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .muted {
            color: #6b83a7;
            font-size: 13px;
        }

        .actions {
            display: inline-flex;
            gap: 9px;
            flex-wrap: wrap;
        }

        .btn {
            border: 1px solid #c9d8ef;
            border-radius: 10px;
            background: #fff;
            color: #28508f;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 14px;
            cursor: pointer;
        }

        .btn.primary {
            border-color: #2f67d7;
            background: #2f67d7;
            color: #fff;
        }

        @media (max-width: 900px) {
            .top-grid {
                grid-template-columns: 1fr;
            }

            .amount-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .sheet {
                width: calc(100% - 18px);
            }

            .header,
            .content {
                padding-left: 15px;
                padding-right: 15px;
            }

            .amount-grid {
                grid-template-columns: 1fr;
            }

            .kv {
                grid-template-columns: 1fr;
                gap: 3px;
            }
        }

        @media print {
            body {
                background: #fff;
            }

            .sheet {
                width: 100%;
                margin: 0;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    @php
        $memberName = trim((string) ($slot->user?->name ?? 'Member'));
        $statusKey = (string) $invoice['status_key'];
        $statusClass = $statusKey === 'paid' ? 'paid' : ($statusKey === 'due' ? 'due' : 'not-paid');
        $statementNo = 'Month '.$monthNumber.' Statement';
    @endphp

    <section class="sheet">
        <header class="header">
            <div>
                <h1 class="brand">Goud Sangam Chit Funds</h1>
                <p class="sub">
                    Monthly Collection Statement<br>
                    {{ $chit->plan_label }} | Month {{ $monthNumber }}
                </p>
            </div>
            <span class="invoice-chip">{{ $statementNo }}</span>
        </header>

        <div class="content">
            <section class="top-grid">
                <article class="panel">
                    <h2 class="title">Member Details</h2>
                    <div class="kv">
                        <span class="k">Name</span>
                        <span class="v">{{ $memberName }}</span>
                    </div>
                    <div class="kv">
                        <span class="k">Phone</span>
                        <span class="v">{{ $slot->user?->mobile_number ?: ($slot->user?->primary_phone ?: 'Not available') }}</span>
                    </div>
                    <div class="kv">
                        <span class="k">Chit Name</span>
                        <span class="v">{{ $chit->plan_label }}</span>
                    </div>
                    <div class="kv">
                        <span class="k">Generated</span>
                        <span class="v">{{ now()->format('d M Y h:i A') }}</span>
                    </div>
                </article>

                <article class="panel">
                    <h2 class="title">Payment Status</h2>
                    <div class="kv">
                        <span class="k">Status</span>
                        <span class="v"><span class="status-pill {{ $statusClass }}">{{ $invoice['status_label'] }}</span></span>
                    </div>
                    <div class="kv">
                        <span class="k">Payment Date</span>
                        <span class="v">{{ $invoice['payment_date'] ? $invoice['payment_date']->format('d M Y h:i A') : 'Not paid yet' }}</span>
                    </div>
                </article>
            </section>

            <section class="amount-grid">
                <article class="amount-card">
                    <div class="amount-label">Expected Amount</div>
                    <p class="amount-value">Rs {{ number_format((int) $invoice['expected_amount']) }}</p>
                </article>
                <article class="amount-card">
                    <div class="amount-label">Paid Amount</div>
                    <p class="amount-value subtle">Rs {{ number_format((int) $invoice['paid_amount']) }}</p>
                </article>
                <article class="amount-card">
                    <div class="amount-label">Due Amount</div>
                    <p class="amount-value subtle">Rs {{ number_format((int) $invoice['due_amount']) }}</p>
                </article>
                <article class="amount-card">
                    <div class="amount-label">Extra Paid</div>
                    <p class="amount-value subtle">Rs {{ number_format((int) $invoice['extra_paid_amount']) }}</p>
                </article>
            </section>

            <footer class="foot">
                <span class="muted">Official digital statement generated by Goud Sangam Chit platform.</span>
                <div class="actions">
                    <a class="btn" href="{{ route('admin.chits.show', ['chit' => $chit->id, 'tab' => 'payments', 'month' => $monthNumber]) }}">Back to Payments</a>
                    <button class="btn primary" type="button" onclick="window.print()">Print / Save PDF</button>
                </div>
            </footer>
        </div>
    </section>
@if (request()->boolean('auto_print'))
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 250);
        });
    </script>
@endif
</body>
</html>
