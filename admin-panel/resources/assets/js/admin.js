$(document).ready(function() {
    let apiUrl = $('#selectAdmin').attr('src');
    $('#selectAdmin').select2({
        ajax: {
            url: apiUrl,
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function(user) {
                        return {
                            id: user.id,
                            text: user.first_name+' '+user.last_name +' | '+ user.mobile
                        };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 1,
        placeholder: 'جهت انتخاب ادمین کلیک کنید',
        allowClear: true
    });
});
