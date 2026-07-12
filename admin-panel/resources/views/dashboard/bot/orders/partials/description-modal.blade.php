<div class="modal fade" id="botOrderDescriptionModal" tabindex="-1" aria-labelledby="botOrderDescriptionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="botOrderDescriptionModalLabel">
                    <i class="fas fa-pen-to-square me-1 text-primary"></i>
                    یادداشت ادمین
                    <small class="text-muted ms-2" id="botOrderDescriptionModalOrderId"></small>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label for="botOrderDescriptionInput" class="form-label small text-muted">
                    یادداشت داخلی ادمین (پیگیری، تماس با کاربر، اقدام بعدی و …)
                </label>
                <textarea id="botOrderDescriptionInput" class="form-control" rows="6"
                    placeholder="یادداشت شخصی ادمین برای این سفارش…"></textarea>
                <div class="form-text">
                    توضیحات سیستمی (خطاهای CoinEx و …) به‌صورت خودکار ثبت می‌شوند و قابل ویرایش نیستند.
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
