<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Square Payment Form</title>
</head>
<body>
    <h1>Payment Form</h1>
    <form id="payment-form">
        <div id="card-container"></div>
        <button id="card-button">Pay Now</button>
    </form>

    <script src="https://sandbox.web.squarecdn.com/v1/square.js"></script>
    <script>
        const appId = "{{ env('SQUARE_APPLICATION_ID') }}";  // Your Square Application ID
        const locationId = "{{ env('SQUARE_LOCATION_ID') }}";  // Your Square Location ID

        async function initializeCard(payments) {
            const card = await payments.card();
            await card.attach('#card-container');
            return card;
        }

        async function createPayment(nonce) {
            const amount = 50; // Set the amount you want to charge (in dollars)
            const currency = 'USD';  // Currency (USD)
            const customerId = '1';  // Replace with actual customer ID
            const locationId = 'LYC7DVTNG32EC';  // Replace with actual location ID
            const teamMemberId = 'TMSyzQc-dIlWMlZe';  // Replace with actual team member ID
            const buyerEmailAddress = 'abc@gmail.com';  // Replace with actual buyer's email address

            const body = JSON.stringify({
                nonce: nonce,
                amount: amount,
                currency: currency,
                customer_id: customerId,
                location_id: locationId,
                team_member_id: teamMemberId,
                buyer_email_address: buyerEmailAddress
            });

            const response = await fetch('/api/payment-square', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: body,
            });

            return await response.json();
        }

        async function main() {
            const payments = Square.payments(appId, locationId);
            const card = await initializeCard(payments);

            const cardButton = document.getElementById('card-button');
            cardButton.addEventListener('click', async (event) => {
                event.preventDefault();
                const result = await card.tokenize();
                if (result.status === 'OK') {
                    const paymentResponse = await createPayment(result.token);
                    if (paymentResponse.success) {
                        alert('Payment successful!');
                    } else {
                        alert('Payment failed: ' + JSON.stringify(paymentResponse.errors));
                    }
                } else {
                    alert('Payment failed!');
                }
            });
        }

        main();
    </script>
</body>
</html>
