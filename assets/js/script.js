$(document).ready(function() {
  /*  // Bill Form Script
    if ($('#billForm').length) {
        const itemRowTemplate = $('#itemRowTemplate').html();
        const billItemsBody = $('#billItemsBody');

        function addNewItemRow() {
            billItemsBody.append(itemRowTemplate);
            updateEventListenersForRow(billItemsBody.find('tr:last-child'));
        }

        function updateEventListenersForRow(row) {
            row.find('.item-select').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const price = selectedOption.data('price') || 0;
                const hsn = selectedOption.data('hsn') || '';
                const unit = selectedOption.data('unit') || 'Nos';
                // const tax = selectedOption.data('tax') || 0; // For item-wise tax if needed later
                const stock = selectedOption.data('stock') || 0;


                const parentRow = $(this).closest('tr');
                parentRow.find('.item-price').val(parseFloat(price).toFixed(2));
                parentRow.find('.item-hsn').val(hsn);
                parentRow.find('.item-unit').val(unit);
                parentRow.find('.item-quantity').attr('max', stock); // Set max based on stock
                
                if(parseInt(parentRow.find('.item-quantity').val()) > stock && stock > 0){
                     parentRow.find('.item-quantity').val(stock);
                     alert('Quantity adjusted to available stock: ' + stock);
                } else if (stock <= 0 && selectedOption.val() !== "") {
                     alert('Item is out of stock!');
                     $(this).val(""); // Reset selection
                     parentRow.find('.item-price').val('');
                     parentRow.find('.item-hsn').val('');
                     parentRow.find('.item-unit').val('');
                }
                calculateRowAmount(parentRow);
            });

            row.find('.item-quantity').on('input change', function() {
                const parentRow = $(this).closest('tr');
                const stock = parseInt(parentRow.find('.item-select option:selected').data('stock')) || 0;
                let qty = parseInt($(this).val());

                if (qty > stock && stock > 0) {
                    $(this).val(stock);
                     alert('Quantity cannot exceed available stock: ' + stock);
                } else if (qty <= 0) {
                    $(this).val(1);
                }
                calculateRowAmount(parentRow);
            });

            row.find('.removeItemRow').on('click', function() {
                $(this).closest('tr').remove();
                calculateTotals();
            });
        }

        function calculateRowAmount(row) {
            const price = parseFloat(row.find('.item-price').val()) || 0;
            const quantity = parseInt(row.find('.item-quantity').val()) || 0;
            const amount = price * quantity;
            row.find('.item-amount').val(amount.toFixed(2));
            calculateTotals();
        }

        function calculateTotals() {
            let subTotal = 0;
            billItemsBody.find('tr').each(function() {
                subTotal += parseFloat($(this).find('.item-amount').val()) || 0;
            });

            const cgstRate = 0.09; // 9% - get from config if dynamic
            const sgstRate = 0.09; // 9%

            const cgstAmount = subTotal * cgstRate;
            const sgstAmount = subTotal * sgstRate;
            const grandTotal = subTotal + cgstAmount + sgstAmount;

            $('#subTotalDisplay').text(subTotal.toFixed(2));
            $('#cgstDisplay').text(cgstAmount.toFixed(2));
            $('#sgstDisplay').text(sgstAmount.toFixed(2));
            $('#grandTotalDisplay').text(grandTotal.toFixed(2));
        }

        $('#addItemRow').on('click', addNewItemRow);

        // Add one row by default if items_data is not empty
        }
       
    }
*/
});