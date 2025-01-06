{{ header }}

<p>Hello Admin,</p>

<p>The customer <strong>{{ customer_name }}</strong> (Email: <a href="mailto:{{ customer_email }}">{{ customer_email }}</a>) has uploaded a payment proof for their order with ID <strong>{{ order_id }}</strong>.</p>

<p>You can view the payment details <a href="{{ payment_link }}">here</a> and the order details <a href="{{ order_link }}">here</a>.</p>

{{ footer }}
