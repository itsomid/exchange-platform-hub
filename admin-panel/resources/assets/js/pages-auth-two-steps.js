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

      if (hiddenOtpInput) {
          const keyupHandler = function () {
              let otpValue = '';
              numeralMaskList.forEach(numeralMaskEl => {
                  otpValue += numeralMaskEl.value;
              });
              hiddenOtpInput.value = otpValue;
              // Submit the form if all 6 digits are filled
              if (otpValue.length === 6) {
                  form.submit();
              }
          };

          numeralMaskList.forEach(numeralMaskEle => {
              numeralMaskEle.addEventListener('keyup', keyupHandler);
          });
      }

  })();
});
