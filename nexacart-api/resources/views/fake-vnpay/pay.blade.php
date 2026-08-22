<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">

    <title>VNPay Fake</title>
</head>

<body>

    <h1>VNPay Fake</h1>

    <hr>

    <p>
        Mã giao dịch:
        <strong>
            {{ $params['vnp_TxnRef'] }}
        </strong>
    </p>

    <p>
        Nội dung:
        <strong>
            {{ $params['vnp_OrderInfo'] }}
        </strong>
    </p>

    <p>
        Số tiền:
        <strong>
            {{ number_format($amount) }} VNĐ
        </strong>
    </p>

    <hr>

    <form
        method="POST"
        action="{{ route('fake-vnpay.process') }}"
    >
        @csrf

        @foreach ($params as $key => $value)
            <input
                type="hidden"
                name="params[{{ $key }}]"
                value="{{ $value }}"
            >
        @endforeach

        <button
            type="submit"
            name="result"
            value="success"
        >
            Thanh toán thành công
        </button>

        <button
            type="submit"
            name="result"
            value="failed"
        >
            Thanh toán thất bại
        </button>

        <button
            type="submit"
            name="result"
            value="cancelled"
        >
            Hủy thanh toán
        </button>
    </form>

</body>
</html>