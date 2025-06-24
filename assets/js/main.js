// Main JavaScript file for the vehicle lookup system

document.addEventListener("DOMContentLoaded", () => {
  // Search form validation
  const searchForm = document.getElementById("searchForm")
  if (searchForm) {
    searchForm.addEventListener("submit", (e) => {
      const licensePlate = document.getElementById("license_plate").value.trim()

      // Simple validation for license plate format
      const licensePlateRegex = /^[0-9]{2}[A-Z]-[0-9]{5}$/

      if (!licensePlateRegex.test(licensePlate)) {
        e.preventDefault()
        alert("Vui lòng nhập biển số xe đúng định dạng (VD: 29A-123.45)")
      }
    })
  }

  // Payment modal functionality
  const paymentModal = document.getElementById("paymentModal")
  if (paymentModal) {
    paymentModal.addEventListener("show.bs.modal", (event) => {
      const button = event.relatedTarget
      const violationId = button.getAttribute("data-id")
      const amount = button.getAttribute("data-amount")

      document.getElementById("violationId").value = violationId
      document.getElementById("paymentAmount").textContent = Number(amount).toLocaleString("vi-VN")
    })

    // Handle payment method change
    const paymentMethods = document.querySelectorAll('input[name="paymentMethod"]')
    paymentMethods.forEach((method) => {
      method.addEventListener("change", function () {
        // In a real application, we would change the QR code image based on the selected payment method
        console.log("Payment method changed to: " + this.value)
      })
    })

    // Handle payment confirmation
    const confirmButton = document.getElementById("confirmPayment")
    if (confirmButton) {
      confirmButton.addEventListener("click", () => {
        const violationId = document.getElementById("violationId").value
        const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value

        // Send AJAX request to process payment
        fetch("process_payment.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: `violation_id=${violationId}&payment_method=${paymentMethod}`,
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              alert("Thanh toán thành công! Trạng thái thanh toán sẽ được cập nhật sau khi xác nhận.")
              location.reload()
            } else {
              alert("Có lỗi xảy ra: " + data.message)
            }
          })
          .catch((error) => {
            alert("Có lỗi xảy ra khi xử lý thanh toán.")
            console.error("Error:", error)
          })
      })
    }
  }
})
