document.addEventListener('change', function (event) {
  if (event.target && event.target.matches('input[type="file"][data-max-size]')) {
    const input = event.target
    if (input.files && input.files.length > 0) {
      const maxSize = parseInt(input.dataset.maxSize, 10)

      for (let i = 0; i < input.files.length; i++) {
        const file = input.files[i]
        if (file.size > maxSize) {
          const maxSizeMb = maxSize / (1024 * 1024)
          alert(`Plik "${file.name}" jest zbyt duży. Maksymalny rozmiar to ${maxSizeMb} MB.`)
          input.value = '' // Clear the input
          return // Stop checking
        }
      }
    }
  }
})



