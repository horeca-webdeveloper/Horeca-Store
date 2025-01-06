@if (session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
        });
    </script>
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{ session('success') }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    @php(session()->forget('success'))
@endif


<x-core::card class="mb-3">
    <x-core::card.header>
        <x-core::card.title>
            {{ trans('plugins/ecommerce::shipping.additional_shipment_information') }}
        </x-core::card.title>
    </x-core::card.header>
    <x-core::card.body>
        {!! Botble\Ecommerce\Forms\ShipmentInfoForm::createFromModel($shipment)->renderForm() !!}
    </x-core::card.body>
</x-core::card>
