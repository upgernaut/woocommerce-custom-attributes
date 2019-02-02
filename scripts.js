jQuery(document).ready(function($) 
{
    $('.wsrcCustomAttsCheck').click(function(e) 
    {
        // e.preventDefault();
		var $productId = $(this).attr('data-product-id');
		
		var $totalPrice = 0;
		$('.wsrcCustomAttsCheck:checked').each(function(){
			$totalPrice += parseFloat($(this).attr('data-price'))
		});
		
        var data = {
            action: 'wsrc_custom_attributes_ajax_retrieve',
            nonce: wsrc_custom_attributes_ajax.nonce,
			product_id: $productId,
			checked_sum: $totalPrice,
        };
		$('div.product .price').fadeTo('fast', 0.2);

        $.post( wsrc_custom_attributes_ajax.url, data, function( response ) 
        {			
			$('div.product .price').html(response.data).fadeTo('fast', 1);
			
        });
    });
});


