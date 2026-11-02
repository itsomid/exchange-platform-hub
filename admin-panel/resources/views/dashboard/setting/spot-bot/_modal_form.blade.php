<form id="spotBotSettingForm" action="{{ route('admin.setting.spot-bot.update', $currency) }}" method="POST">
    @csrf
    @method('PUT')

    <div id="spotBotSettingErrors" class="alert alert-danger d-none"></div>

    @include('dashboard.setting.spot-bot._fields')

    <div class="d-flex justify-content-end gap-2 flex-wrap">
        @unless ($spotBotSetting->is_active)
            <button type="button" class="btn btn-outline-danger me-auto" id="spotBotCancelOrders"
                data-url="{{ route('admin.setting.spot-bot.cancel-orders', $currency) }}">
                <i class="fa-solid fa-ban me-1"></i>
                بستن سفارش‌های باز
            </button>
        @endunless
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
        <button type="submit" class="btn btn-primary" id="spotBotSettingSubmit">
            <i class="fa-solid fa-save me-1"></i>
            ذخیره تنظیمات
        </button>
    </div>
</form>
