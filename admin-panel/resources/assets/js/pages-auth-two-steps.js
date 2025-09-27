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
          });
      }

  })();
});
