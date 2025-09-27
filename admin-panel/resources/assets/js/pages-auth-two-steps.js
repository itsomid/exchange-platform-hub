/**
 *  Page auth two steps
 */

'use strict';

document.addEventListener('DOMContentLoaded', function (e) {
  (function () {
    let maskWrapper = document.querySelector('.numeral-mask-wrapper');


    for (let pin of maskWrapper.children) {
      pin.onkeyup = function (e) {
        // Check if the key pressed is a number (0-9)
        if (/^\d$/.test(e.key)) {
          // While entering value, go to next
          if (pin.nextElementSibling) {
            if (this.value.length === parseInt(this.attributes['maxlength'].value)) {
              pin.nextElementSibling.focus();
            }
          }
        } else if (e.key === 'Backspace') {
          // While deleting entered value, go to previous
          if (pin.previousElementSibling) {
            pin.previousElementSibling.focus();
          }
        }
      };
      // Prevent the default behavior for the minus key
      pin.onkeypress = function (e) {
        if (e.key === '-') {
          e.preventDefault();
        }
      };
      // Better navigation on mobile/desktop with arrow keys
      pin.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft' && pin.previousElementSibling) {
          pin.previousElementSibling.focus();
        } else if (e.key === 'ArrowRight' && pin.nextElementSibling) {
          pin.nextElementSibling.focus();
        }
      });
    }

      const form = document.querySelector('#twoStepsForm'); // Form element to submit
      const numeralMaskList = maskWrapper.querySelectorAll('.numeral-mask');
      const hiddenOtpInput = document.querySelector('.code');
      let isSubmitting = false;

      // Helper to compose code from inputs
      const composeOtp = () => {
        let otpValue = '';
        numeralMaskList.forEach(numeralMaskEl => {
          otpValue += numeralMaskEl.value;
        });
        return otpValue;
      };

      // Paste handling: allow pasting full OTP and distribute digits across inputs
      maskWrapper.addEventListener('paste', function (evt) {
        if (isSubmitting) return;
        const clipboardData = (evt.clipboardData || window.clipboardData);
        const text = clipboardData ? clipboardData.getData('text') : '';
        const digits = (text || '').replace(/\D/g, '').slice(0, numeralMaskList.length);
        if (!digits) return; // nothing to fill
        evt.preventDefault();
        // Fill inputs with pasted digits
        let i = 0;
        numeralMaskList.forEach(el => {
          el.value = digits[i] || '';
          i++;
        });
        // Update hidden input
        if (hiddenOtpInput) hiddenOtpInput.value = composeOtp();
        // Auto-submit if all digits provided
        if (digits.length === numeralMaskList.length) {
          isSubmitting = true;
          numeralMaskList.forEach(el => (el.disabled = true));
          const submitBtn = form.querySelector('[type="submit"]');
          if (submitBtn) submitBtn.disabled = true;
          form.submit();
        } else {
          // Focus next empty input
          numeralMaskList[digits.length]?.focus();
        }
      });

      // Ensure code is composed on manual submit as well
      form.addEventListener('submit', function (evt) {
        if (isSubmitting) {
          // prevent duplicate submissions
          evt.preventDefault();
          return;
        }
        const otpValue = composeOtp();
        if (hiddenOtpInput) hiddenOtpInput.value = otpValue;
        isSubmitting = true;
        // Disable all inputs to avoid further edits triggering extra submits
        numeralMaskList.forEach(el => (el.disabled = true));
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;
      });

      if (hiddenOtpInput) {
          const keyupHandler = function () {
              if (isSubmitting) return;
              const otpValue = composeOtp();
              hiddenOtpInput.value = otpValue;
              // Submit the form if all 6 digits are filled
              if (otpValue.length === 6) {
                  isSubmitting = true;
                  // Disable inputs to prevent additional keyups causing resubmits
                  numeralMaskList.forEach(el => (el.disabled = true));
                  const submitBtn = form.querySelector('[type="submit"]');
                  if (submitBtn) submitBtn.disabled = true;
                  form.submit();
              }
          };

          numeralMaskList.forEach(numeralMaskEle => {
              numeralMaskEle.addEventListener('keyup', keyupHandler);
              // Handle autofill or paste directly into an input (mobile one-time-code)
              numeralMaskEle.addEventListener('input', function () {
                  if (isSubmitting) return;
                  const val = this.value || '';
                  // If multiple digits land in a single box, distribute them
                  if (val.length > 1) {
                      const digits = val.replace(/\D/g, '');
                      const startIndex = Array.from(numeralMaskList).indexOf(this);
                      let idx = 0;
                      for (let i = startIndex; i < numeralMaskList.length; i++) {
                          numeralMaskList[i].value = digits[idx] || '';
                          idx++;
                      }
                      hiddenOtpInput.value = composeOtp();
                      const total = composeOtp();
                      if (total.length === numeralMaskList.length) {
                          isSubmitting = true;
                          numeralMaskList.forEach(el => (el.disabled = true));
                          const submitBtn = form.querySelector('[type="submit"]');
                          if (submitBtn) submitBtn.disabled = true;
                          form.submit();
                      } else {
                          // focus next empty input
                          for (let i = startIndex; i < numeralMaskList.length; i++) {
                              if (!numeralMaskList[i].value) {
                                  numeralMaskList[i].focus();
                                  break;
                              }
                          }
                      }
                  }
              });
              // Convenience: select text on focus for quicker overwrite
              numeralMaskEle.addEventListener('focus', function () {
                  try { this.select(); } catch (_) {}
              });
          });
      }

  })();
});
