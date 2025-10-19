<template>
  <div class="relative">
    <!-- Trigger Button -->
    <button
      ref="triggerButton"
      type="button"
      class="flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
      style="width: 330px;"
    >
      <div class="flex items-center space-x-2">
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <span>{{ displayText }}</span>
      </div>
      <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
      </svg>
    </button>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'

export default {
  name: 'FlatpickrDateRangePicker',
  props: {
    modelValue: {
      type: Object,
      default: () => ({ start: null, end: null })
    }
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const triggerButton = ref(null)
    let flatpickrInstance = null

    const displayText = computed(() => {
      if (props.modelValue?.start && props.modelValue?.end) {
        const startDate = new Date(props.modelValue.start)
        const endDate = new Date(props.modelValue.end)
        return `${formatDate(startDate)} - ${formatDate(endDate)}`
      }
      return 'Chọn khoảng thời gian'
    })

    function formatDate(date) {
      if (!date) return ''
      return date.toLocaleDateString('vi-VN')
    }

    function initializeFlatpickr() {
      if (!triggerButton.value) return

      // Vietnamese locale
      const vietnameseLocale = {
        weekdays: {
          shorthand: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
          longhand: ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy']
        },
        months: {
          shorthand: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
          longhand: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12']
        },
        firstDayOfWeek: 1,
        rangeSeparator: ' - ',
        weekAbbreviation: 'Tuần',
        scrollTitle: 'Cuộn để tăng',
        toggleTitle: 'Click để chuyển đổi',
        amPM: ['AM', 'PM'],
        yearAriaLabel: 'Năm',
        monthAriaLabel: 'Tháng',
        hourAriaLabel: 'Giờ',
        minuteAriaLabel: 'Phút',
        time_24hr: false
      }

      flatpickrInstance = flatpickr(triggerButton.value, {
        mode: 'range',
        dateFormat: 'd/m/Y',
        locale: vietnameseLocale,
        allowInput: false,
        clickOpens: true,
        static: true,
        position: 'auto',
        appendTo: document.body,
        monthSelectorType: 'static',
        yearSelectorType: 'static',
        onChange: function(selectedDates, dateStr, instance) {
          if (selectedDates.length === 2) {
            // Only emit when both start and end dates are selected
            const start = selectedDates[0]
            const end = selectedDates[1]
            emit('update:modelValue', { start, end })
            // Close picker after selection
            instance.close()
          } else if (selectedDates.length === 1) {
            // User is selecting the first date, don't emit yet
            emit('update:modelValue', { start: selectedDates[0], end: null })
          }
        },
        onClose: function(selectedDates, dateStr, instance) {
          // Clean up if needed
        }
      })
    }

    function destroyFlatpickr() {
      if (flatpickrInstance) {
        flatpickrInstance.destroy()
        flatpickrInstance = null
      }
    }

    // Watch for external changes
    watch(() => props.modelValue, (newValue) => {
      if (flatpickrInstance && newValue?.start && newValue?.end) {
        flatpickrInstance.setDate([newValue.start, newValue.end])
      }
    }, { deep: true })

    onMounted(() => {
      initializeFlatpickr()
    })

    onUnmounted(() => {
      destroyFlatpickr()
    })

    return {
      triggerButton,
      displayText
    }
  }
}
</script>

<style>
/* Custom styles for Flatpickr - Modern Design */
.flatpickr-calendar {
  @apply shadow-2xl border-0 rounded-xl overflow-hidden;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: white;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.flatpickr-months {
  @apply bg-gradient-to-r from-blue-600 to-blue-700 text-white;
  padding: 16px;
}

.flatpickr-current-month {
  @apply text-white font-bold text-lg;
}

.flatpickr-prev-month,
.flatpickr-next-month {
  @apply text-white hover:text-blue-200 transition-colors;
  padding: 8px;
  border-radius: 8px;
}

.flatpickr-prev-month:hover,
.flatpickr-next-month:hover {
  @apply bg-white bg-opacity-20;
}

.flatpickr-weekdays {
  @apply bg-gray-50 border-b border-gray-200;
  padding: 12px 16px 8px;
}

.flatpickr-weekday {
  @apply text-gray-600 font-semibold text-sm;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.flatpickr-days {
  padding: 16px;
}

.flatpickr-day {
  @apply text-gray-800 font-medium;
  border-radius: 8px;
  margin: 2px;
  transition: all 0.2s ease;
  position: relative;
}

.flatpickr-day:hover {
  @apply bg-blue-50 text-blue-700;
  transform: scale(1.05);
}

.flatpickr-day.selected {
  @apply bg-blue-600 text-white shadow-lg;
  transform: scale(1.1);
}

.flatpickr-day.startRange {
  @apply bg-blue-600 text-white shadow-lg;
  transform: scale(1.1);
}

.flatpickr-day.endRange {
  @apply bg-blue-600 text-white shadow-lg;
  transform: scale(1.1);
}

.flatpickr-day.inRange {
  @apply bg-blue-100 text-blue-800;
}

.flatpickr-day.today {
  @apply border-2 border-blue-500 text-blue-700 font-bold;
}

.flatpickr-day.today:hover {
  @apply bg-blue-50;
}

.flatpickr-day.flatpickr-disabled {
  @apply text-gray-300 cursor-not-allowed;
}

.flatpickr-day.flatpickr-disabled:hover {
  @apply bg-transparent transform-none;
}

/* Arrow styles */
.flatpickr-prev-month svg,
.flatpickr-next-month svg {
  width: 16px;
  height: 16px;
}

/* Month navigation */
.flatpickr-current-month .flatpickr-monthDropdown-months {
  @apply bg-transparent text-white border-0;
  font-size: 16px;
  font-weight: bold;
}

.flatpickr-current-month .numInputWrapper {
  @apply bg-transparent text-white border-0;
}

.flatpickr-current-month .numInputWrapper input {
  @apply bg-transparent text-white border-0 text-lg font-bold;
}

/* Range selection indicator */
.flatpickr-day.startRange::after {
  content: '';
  position: absolute;
  top: 50%;
  right: -2px;
  width: 0;
  height: 0;
  border-left: 4px solid #2563eb;
  border-top: 4px solid transparent;
  border-bottom: 4px solid transparent;
  transform: translateY(-50%);
}

.flatpickr-day.endRange::before {
  content: '';
  position: absolute;
  top: 50%;
  left: -2px;
  width: 0;
  height: 0;
  border-right: 4px solid #2563eb;
  border-top: 4px solid transparent;
  border-bottom: 4px solid transparent;
  transform: translateY(-50%);
}
</style>
