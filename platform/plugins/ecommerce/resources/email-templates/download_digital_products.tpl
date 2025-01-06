{{ header }}

<div class="bb-main-content">
    <table class="bb-box" cellpadding="0" cellspacing="0">
        <tbody>
        <tr>
            <td class="bb-content bb-pb-0" align="center">
                <table class="bb-icon bb-icon-lg bb-bg-blue" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <td valign="middle" align="center">
                                <img src="{{ 'cloud-download' | icon_url }}" class="bb-va-middle" width="40" height="40" alt="Icon" />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <h1 class="bb-text-center bb-m-0 bb-mt-md">Download digital products</h1>
            </td>
        </tr>
        <tr>
            <td class="bb-content">
                <p>Dear {{ customer_name }},</p>
                <div>Thank you for purchasing our product.</div>
                <div>You can now download digital product(s) you have purchased here:</div>
            </td>
        </tr>
        <tr>
            <td class="bb-content bb-pt-0">
                <h4>Here's what you ordered:</h4>

                <div class="table">
                    <table>
                        <tr>
                            <th>
                                &nbsp;
                            </th>
                            <th>
                                &nbsp;
                            </th>
                            <th style="text-align: left">
                                Download
                            </th>
                        </tr>

                        {% for product in digital_products %}
                            <tr>
                                <td>
                                    <img
                                        src="{{ product.product_image_url }}"
                                        alt="image"
                                        width="50"
                                    >
                                </td>
                                <td>
                                    <span>{{ product.product_name }}</span>
                                </td>
                                <td>
                                    {% if product.product_file_internal_count %}
                                        <div>
                                            <a href="{{ product.download_hash_url }}">All files</a>
                                        </div>
                                    {% endif %}
                                    {% if product.product_file_external_count %}
                                        <div>
                                            <a href="{{ product.download_external_url }}">External link downloads</a>
                                        </div>
                                    {% endif %}
                                </td>
                            </tr>
                        {% endfor %}
                    </table><br>
                </div>
            </td>
        </tr>
        {% if payment_method %}
            <tr>
                <td class="bb-content bb-pt-0">
                    <h4>Payment Method</h4>
                    {{ payment_method }}
                </td>
            </tr>
        {% endif %}
        </tbody>
    </table>
</div>

{{ footer }}
