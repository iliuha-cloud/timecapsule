// TimeCapsule — small browser script (the same file is used by every variant).
// The app works without it; the script only makes dates nicer:
//   1. a calendar with a time picker for the "Open at" field (flatpickr);
//   2. the chosen local time is also sent as UTC, so the server knows the exact moment;
//   3. dates on the pages are shown in the visitor's own time zone.

document.addEventListener('DOMContentLoaded', function () {
  // 1. Calendar + time picker.
  document.querySelectorAll('input[data-datetime-picker]').forEach(function (input) {
    if (typeof flatpickr !== 'function') return; // library missing: keep the browser's own field

    input.type = 'text'; // flatpickr works with a text field
    var picker = flatpickr(input, {
      enableTime: true,
      time_24hr: true,
      minuteIncrement: 5,
      defaultHour: 12,
      minDate: 'today',
      dateFormat: 'Y-m-d\\TH:i', // value sent to the server, e.g. 2027-01-01T13:30
      altInput: true,
      altFormat: 'j M Y, H:i', // what the user sees, e.g. 1 Jan 2027, 13:30
      disableMobile: true, // same calendar on phones
    });

    // flatpickr shows a new visible field and hides the original one. Move the id and the
    // accessibility attributes to the visible field, so <label for="open_at"> still points at it.
    var visible = picker.altInput;
    if (visible) {
      ['id', 'aria-describedby', 'aria-invalid', 'required'].forEach(function (name) {
        if (input.hasAttribute(name)) {
          visible.setAttribute(name, input.getAttribute(name));
          input.removeAttribute(name);
        }
      });
    }
  });

  // 2. Before the form is sent, put the chosen moment in UTC into the hidden "open_at_utc" field.
  document.querySelectorAll('form').forEach(function (form) {
    var local = form.querySelector('input[name="open_at"]');
    var utc = form.querySelector('input[name="open_at_utc"]');
    if (!local || !utc) return;

    form.addEventListener('submit', function () {
      // "2027-01-01T13:30" without a time zone is read as the visitor's local time.
      var date = new Date(local.value);
      utc.value = isNaN(date.getTime()) ? '' : date.toISOString();
    });
  });

  // 3. Show <time data-local datetime="...Z"> in the visitor's time zone, e.g. "1 Jan 2027, 13:30".
  var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  function pad(number) {
    return (number < 10 ? '0' : '') + number;
  }

  // getDate(), getHours()... return values in the browser's time zone.
  function localText(date) {
    return date.getDate() + ' ' + MONTHS[date.getMonth()] + ' ' + date.getFullYear() +
      ', ' + pad(date.getHours()) + ':' + pad(date.getMinutes());
  }

  document.querySelectorAll('time[data-local]').forEach(function (element) {
    var date = new Date(element.getAttribute('datetime'));
    if (isNaN(date.getTime())) return;
    element.textContent = localText(date);
    element.title = date.toISOString().slice(0, 16).replace('T', ' ') + ' UTC';
  });
});
