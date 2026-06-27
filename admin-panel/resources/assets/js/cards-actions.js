"use strict";

// Card reload (jquery)
// --------------------------------------------------------------------

$(function () {
    const cardReload = $(".card-reload");
    if (cardReload.length) {
        cardReload.on("click", function (e) {
            e.preventDefault();
            var $this = $(this);
            var card = $this.closest(".card");
            var walletChainId = $this.data("wallet-chain-id");

            // Block the card with loading animation
            card.block({
                message:
                    '<div class="sk-fold sk-primary"><div class="sk-fold-cube"></div><div class="sk-fold-cube"></div><div class="sk-fold-cube"></div><div class="sk-fold-cube"></div></div><h5>LOADING...</h5>',
                css: {
                    backgroundColor: "transparent",
                    border: "0",
                },
                overlayCSS: {
                    backgroundColor: $("html").hasClass("dark-style")
                        ? "#000"
                        : "#fff",
                    opacity: 0.55,
                },
            });

            // Make AJAX request to refresh balance
            $.ajax({
                url: "refresh-hot-wallet-balance",
                method: "POST",
                data: {
                    wallet_chain_id: walletChainId,
                    _token: $('meta[name="csrf-token"]').attr("content"), // CSRF token for Laravel
                },
                success: function (data) {
                    card.unblock();
                    Toastify({
                        text: "موجودی با موفقیت به‌روزرسانی شد.",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#28C76F" },
                    }).showToast();
                },
                error: function (xhr) {
                    var errorMessage = "خطا در دریافت اطلاعات.";
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    } else if (xhr.responseText) {
                        try {
                            var parsed = JSON.parse(xhr.responseText);
                            if (parsed && parsed.error) {
                                errorMessage = parsed.error;
                            }
                        } catch (e) {
                            // Fallback remains generic
                        }
                    }

                    card.unblock();
                    Toastify({
                        text: errorMessage,
                        duration: 5000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#EA5455" },
                    }).showToast();
                },
            });
        });
    }
});
