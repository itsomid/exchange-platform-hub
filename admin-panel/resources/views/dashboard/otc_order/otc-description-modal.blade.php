<div class="modal fade" id="otc-description-{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" dir="ltr">
                <h5 class="modal-title font-number">OTC Order #{{ $order->id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                    <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات صرافی مرجع (دلیل لغو)</h6>
                    <div class="text-wrap font-number">
                        {{ $order->ref_exchange_description }}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">بستن</button>
            </div>
        </div>
    </div>
</div>