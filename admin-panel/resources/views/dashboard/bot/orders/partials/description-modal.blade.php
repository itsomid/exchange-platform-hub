<div class="modal fade" id="botOrderDescriptionModal" tabindex="-1" aria-labelledby="botOrderDescriptionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="botOrderDescriptionModalLabel">
                    <i class="fas fa-pen-to-square me-1 text-primary"></i>
                    ویرایش توضیحات سفارش
                    <small class="text-muted ms-2" id="botOrderDescriptionModalOrderId"></small>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label for="botOrderDescriptionInput" class="form-label small text-muted">
                    توضیحات داخلی ادمین (دلیل لغو، خطای صرافی، یادداشت پیگیری و …)
                </label>
                <textarea id="botOrderDescriptionInput" class="form-control" rows="6"
                    placeholder="مثال: coinex.sell.place_failed market=BTCUSDT code=3127 msg=amount too small | auto-liquidated | liquidation_exchange_order_id=12345"></textarea>
                <div class="form-text">
                    این متن فقط در پنل ادمین نمایش داده می‌شود و می‌تواند برای هر سفارش چند ارز، چند یادداشت با
                    <code>||</code> داشته باشد.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-primary" id="botOrderDescriptionSaveBtn">
                    <i class="fas fa-save me-1"></i> ذخیره
                </button>
            </div>
        </div>
    </div>
</div>
