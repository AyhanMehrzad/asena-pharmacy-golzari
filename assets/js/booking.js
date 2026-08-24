/**
 * ASENA Booking — Real-time slot polling + UX enhancements.
 */

// ── Real-time slot polling ─────────────────────────────────────────────────────
const SlotPoller = (() => {
    let intervalId   = null;
    let currentDoc   = null;
    let currentDate  = null;
    const INTERVAL   = 10_000; // 10 seconds

    function getBookedSlots(doctorId, date) {
        const url = `/petshop/actions/check_slots.php?doctor_id=${encodeURIComponent(doctorId)}&date=${encodeURIComponent(date)}`;
        fetch(url, { cache: 'no-store' })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then(data => {
                if (!Array.isArray(data.booked_slots)) return;
                applySlotAvailability(data.booked_slots);
            })
            .catch(err => console.warn('[SlotPoller] fetch failed:', err));
    }

    function applySlotAvailability(bookedSlots) {
        const allSlotBtns = document.querySelectorAll('[data-time]');
        allSlotBtns.forEach(btn => {
            const time = btn.getAttribute('data-time');
            if (!time) return;

            const isBooked = bookedSlots.includes(time);
            if (isBooked) {
                btn.disabled = true;
                btn.classList.add('cursor-not-allowed', 'opacity-40', 'line-through');
                btn.classList.remove('hover:bg-primary', 'hover:text-white');
                btn.setAttribute('title', 'این زمان رزرو شده است');
                // Deselect if currently selected
                if (btn.classList.contains('bg-primary')) {
                    btn.classList.remove('bg-primary', 'text-white');
                    const hiddenInput = document.getElementById('selected_time_input');
                    if (hiddenInput && hiddenInput.value === time) {
                        hiddenInput.value = '';
                    }
                }
            } else if (!btn.hasAttribute('data-doctor-disabled')) {
                // Only re-enable if not disabled by doctor shift settings
                btn.disabled = false;
                btn.classList.remove('cursor-not-allowed', 'opacity-40', 'line-through');
            }
        });
    }

    function start(doctorId, date) {
        stop(); // Clear any previous interval
        currentDoc  = doctorId;
        currentDate = date;

        // Immediate first poll
        getBookedSlots(doctorId, date);
        intervalId = setInterval(() => getBookedSlots(doctorId, date), INTERVAL);
    }

    function stop() {
        if (intervalId) {
            clearInterval(intervalId);
            intervalId = null;
        }
    }

    return { start, stop, applySlotAvailability };
})();

// ── DOM ready ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    // Button ripple effect
    document.querySelectorAll('button').forEach(button => {
        button.addEventListener('click', function (e) {
            if (!this.classList.contains('cursor-not-allowed') && !this.classList.contains('no-ripple')) {
                const ripple = document.createElement('div');
                ripple.className = 'absolute bg-white/20 rounded-full w-2 h-2 animate-ping';
                if (window.getComputedStyle(this).position === 'static') {
                    this.style.position = 'relative';
                }
                this.style.overflow = 'hidden';
                const rect = this.getBoundingClientRect();
                ripple.style.left = (e.clientX - rect.left - 4) + 'px';
                ripple.style.top  = (e.clientY - rect.top - 4) + 'px';
                this.appendChild(ripple);
                setTimeout(() => ripple.remove(), 500);
            }
        });
    });

    // Dates list drag-to-scroll
    const sliderList = document.querySelector('#dates-list');
    if (sliderList) {
        let isDown = false, startX, scrollLeft;
        sliderList.addEventListener('mousedown', e => {
            isDown     = true;
            startX     = e.pageX - sliderList.offsetLeft;
            scrollLeft = sliderList.scrollLeft;
        });
        sliderList.addEventListener('mouseleave', () => { isDown = false; });
        sliderList.addEventListener('mouseup',    () => { isDown = false; });
        sliderList.addEventListener('mousemove', e => {
            if (!isDown) return;
            e.preventDefault();
            sliderList.scrollLeft = scrollLeft - (e.pageX - sliderList.offsetLeft - startX) * 2;
        });
    }

    // ── Hook into booking page doctor/date selection ───────────────────────────
    // Start polling when doctor card is selected
    document.querySelectorAll('[data-id]').forEach(card => {
        card.addEventListener('click', () => {
            const docId = card.getAttribute('data-id');
            const dateInput = document.getElementById('appointment_date_input')
                           || document.querySelector('input[name="appointment_date"]');
            if (docId && dateInput && dateInput.value) {
                SlotPoller.start(docId, dateInput.value);
            }
        });
    });

    // Restart polling when date changes (handles flatpickr or native input)
    const dateInput = document.getElementById('appointment_date_input')
                   || document.querySelector('input[name="appointment_date"]');
    const docIdInput = document.querySelector('input[name="doctor_id"]');

    if (dateInput) {
        dateInput.addEventListener('change', () => {
            const docId = docIdInput ? docIdInput.value : null;
            if (docId && dateInput.value) {
                SlotPoller.start(docId, dateInput.value);
            }
        });
    }

    // Stop polling when user submits the form (no point polling mid-submit)
    const bookingForm = document.querySelector('form[action*="booking_action"]');
    if (bookingForm) {
        bookingForm.addEventListener('submit', () => SlotPoller.stop());
    }
});
