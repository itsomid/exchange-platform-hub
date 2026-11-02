<form id="spotBotSettingForm" action="{{ route('admin.setting.spot-bot.update', $currency) }}" method="POST">
    @csrf
    @method('PUT')

    <div id="spotBotSettingErrors" class="alert alert-danger d-none"></div>

    @include('dashboard.setting.spot-bot._fields')

    <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
        <button type="submit" class="btn btn-primary" id="spotBotSettingSubmit">
            <i class="fa-solid fa-save me-1"></i>
            ذخیره تنظیمات
        </button>
    </div>
</form>
