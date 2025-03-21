(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize charts if they exist
        if (typeof Chart !== 'undefined' && $('#payment-chart').length) {
            initializePaymentChart();
        }
        
        // Export button functionality
        $('#export-csv').on('click', function(e) {
            $('#export-form').submit();
        });
        
        // Date range picker
        if ($.fn.datepicker) {
            $('.date-picker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
        }
        
        // Filter functionality
        $('#filter-form').on('submit', function() {
            var date_from = $('#date_from').val();
            var date_to = $('#date_to').val();
            
            if (date_from && date_to) {
                if (new Date(date_from) > new Date(date_to)) {
                    alert('From date cannot be after To date');
                    return false;
                }
            }
            
            return true;
        });
    });
    
    function initializePaymentChart() {
        var ctx = document.getElementById('payment-chart').getContext('2d');
        var chartData = window.chartData || {
            labels: [],
            values: []
        };
        
        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Monthly Payments',
                    data: chartData.values,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.raw.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
})(jQuery);