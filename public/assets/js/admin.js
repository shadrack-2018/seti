document.addEventListener('DOMContentLoaded', function() {
  // Init sales chart (placeholder data)
  if (document.getElementById('salesChart')) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        datasets: [{
          label: 'Sales',
          data: [120, 200, 150, 220, 300, 250, 400],
          borderColor: '#007bff',
          backgroundColor: 'rgba(0,123,255,0.1)'
        }]
      },
      options: { responsive: true }
    });
  }

  // Init products DataTable
  if (window.jQuery && document.getElementById('productsTable')) {
    $('#productsTable').DataTable({
      ajax: {
        url: '/api/v1/products',
        dataSrc: ''
      },
      columns: [
        { data: 'id' },
        { data: 'sku' },
        { data: 'name' },
        { data: 'price' },
        { data: 'status' },
        { data: null, render: function() { return '<button class="btn btn-sm btn-primary">Edit</button>'; } }
      ]
    });
  }
});
