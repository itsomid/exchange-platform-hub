$(document).ready(function () {
    $('select.js-user-select').each(function () {
        const $el = $(this);
        if ($el.hasClass('select2-hidden-accessible')) {
            return;
        }

        const apiUrl = $el.attr('src');
        const selectedIds = $el.data('selected');

        $el.select2({
            ajax: {
                url: apiUrl,
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    return {
                        results: data.map(function (user) {
                            return {
                                id: user.id,
                                text: '(' + user.id + '#) ' + user.first_name + ' ' + user.last_name + ' | ' + user.email
                            };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 2,
            placeholder: 'جهت انتخاب کاربر کلیک کنید',
            allowClear: true,
            language: {
                inputTooShort: function () {
                    return "لطفا ۲ یا بیشتر کاراکتر وارد کن";
                },
                noResults: function () {
                    return "نتیجه ای نداشت.";
                },
            }
        });

        if (selectedIds && selectedIds.length) {
            const ids = Array.isArray(selectedIds) ? selectedIds : [selectedIds];
            $.ajax({
                type: 'GET',
                url: apiUrl,
                dataType: 'json'
            }).then(function (data) {
                ids.forEach(function (selectedId) {
                    const selectedUser = data.find(user => String(user.id) === String(selectedId));
                    if (!selectedUser) return;
                    const option = new Option(
                        '(' + selectedUser.id + '#) ' + selectedUser.first_name + ' ' + selectedUser.last_name + ' | ' + selectedUser.email,
                        selectedUser.id,
                        true,
                        true
                    );
                    $el.append(option).trigger('change');
                });
            });
        }
    });
});
