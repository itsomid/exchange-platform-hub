'use strict';

// Card reload (jquery)
// --------------------------------------------------------------------

$(function () {
    const cardReload = $('.card-reload');
    if (cardReload.length) {
        cardReload.on('click', function (e) {
            e.preventDefault();
            var $this = $(this);
            var card = $this.closest('.card');
            var walletChainId = card.data('wallet-chain-id');

            // Block the card with loading animation
            card.block({
                message:
                    '<div class="sk-fold sk-primary"><div class="sk-fold-cube"></div><div class="sk-fold-cube"></div><div class="sk-fold-cube"></div><div class="sk-fold-cube"></div></div><h5>LOADING...</h5>',
                css: {
                    backgroundColor: 'transparent',
                    border: '0'
                },
                overlayCSS: {
                    backgroundColor: $('html').hasClass('dark-style') ? '#000' : '#fff',
                    opacity: 0.55
                }
            });

            // Make AJAX request to refresh balance
            $.ajax({
                url: 'refresh-hot-wallet-balance',
                method: 'POST',
                data: {
                    wallet_chain_id: walletChainId,
                    _token: $('meta[name="csrf-token"]').attr('content') // CSRF token for Laravel
                },
                success: function (data) {
                    // Update the balance display

                    var balanceElement = card.find('.hot-balance');
                    balanceElement.html(data.amount);
                    card.unblock();
                    $this
                        .closest('.card')
                        .find('.card-alert')
                        .html(
                            '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                            '<span class="fw-medium">اطلاعات با موفقیت به روز رسانی شد</div>'
                        );


                },
                error: function (xhr) {
                    console.error('Error refreshing balance:', xhr.responseJSON);
                    card.unblock();
                    // Optional: Show an error message to the user
                    $this
                        .closest('.card')
                        .find('.card-alert')
                        .html(
                            '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                            '<span class="fw-medium">خطا در دریافت اطلاعات.</div>'
                        );
                }
            });
        });
    }
});
